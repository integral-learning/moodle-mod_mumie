<?php
namespace mod_mumie;
require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');

class mumie_dndupload_processor
{
    private $courseid;
    private $section;
    private $type;
    private $upload;
    private $coursecontext;
    private $course;

    private $gradepoolsettings;

    public function __construct($courseid, $section, $type, $upload)
    {
        $this->courseid = $courseid;
        $this->section = $section;
        $this->type = $type;
        $this->upload = $upload;
        $this->gradepoolsettings = $this->get_course_gradepool_setting();
        $this->coursecontext = \context_course::instance($courseid);
        global $COURSE;
        $this->course = $COURSE;
    }

    public function process()
    {
        require_capability('moodle/course:manageactivities', $this->coursecontext);
        require_sesskey();

        debugging('_____PROCESSING UPLOAD');
        debugging(json_encode($this->upload));
        if ($this->type == 'mumie/json') {
            return $this->handle_single_upload();
        } elseif ($this->type == 'mumie/jsonArray') {
            return $this->handle_multi_upload();
        }
        return false;
    }

    private function handle_single_upload()
    {
        global $CFG;
        require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');
        $server = new \auth_mumie\mumie_server();
        $server->set_urlprefix($this->upload->server);

        $this->validate_upload_params($this->upload);
        $this->validate_servers([$server]);
        $this->create_missing_servers([$server]);

        $mumie = $this->create_mumie_from_uploadinstance($this->upload, $server);
        $mumie->server = $server->get_urlprefix();


        return mumie_add_instance($mumie, null);
    }

    private function handle_multi_upload()
    {
        // This array holds unique server objects;
        $servers = array();
        $this->upload = (array) $this->upload;
        debugging(json_encode($this->upload));
        foreach ($this->upload as $uploadinstance) {
            $uploadinstance = json_decode($uploadinstance);
            $this->validate_upload_params($uploadinstance);
            $server = new \auth_mumie\mumie_server();
            $server->set_urlprefix($uploadinstance->server);
            $servers[$uploadinstance->server] = $server;
        }

        debugging("SERVERS ARE");
        debugging(json_encode($servers));

        $this->validate_servers(array_values($servers));
        $this->create_missing_servers(array_values($servers));

        $result;
        debugging("TPYEOF is: " . gettype($this->upload));
        debugging("COUNT IS: " . count($this->upload));
        for ($i = 0; $i < count($this->upload); $i++) {
            $uploadinstance = json_decode($this->upload[$i]);
            $mumie = $this->create_mumie_from_uploadinstance($uploadinstance, $servers[$uploadinstance->server]);
            $mumie->server = $servers[$uploadinstance->server]->get_urlprefix();
            if ($i == 0) {
                $result = mumie_add_instance($mumie, null);
            } else {
                $module = $this->create_mumie_course_module($mumie);
            }
        }

        // Rebuild the course cache after update action
        rebuild_course_cache($this->course->id, true);

        // return id of first mumie instance created.
        return $result;
    }

    private function validate_servers($servers)
    {
        foreach ($servers as $server) {
            if (!$server->is_valid_mumie_server()) {
                throw new \moodle_exception('mumie_form_server_not_existing', 'auth_mumie');
            }
        }
    }

    private function create_missing_servers($servers)
    {
        $missingservers = array_filter(
            $servers,
            function ($server) {
                return !$server->config_exists_for_url();
            }
        );

        if (empty($missingservers)) {
            return;
        }

        if (!has_capability("auth/mumie:addserver", \context_course::instance($this->course->id), $USER)) {
            throw new \moodle_exception(get_string('server_config_missing', 'mod_mumie', $server->get_urlprefix()));
        }
        foreach ($missingservers as $server) {
            $server->set_name($server->get_urlprefix());
            $server->upsert();
        }
    }

    private function get_course_gradepool_setting()
    {
        global $DB, $COURSE;
        $privategradepool;
        $exitingtasks = array_values(
            $DB->get_records(MUMIE_TASK_TABLE, array("course" => $COURSE->id))
        );
        debugging("\n________________EXISTING TASKS: " . count($exitingtasks));
        if (count($exitingtasks) > 0) {
            $privategradepool = $exitingtasks[0]->privategradepool;
        }
        //TODO: check if this actually works;
        return $privategradepool;
    }

    private function validate_upload_params($uploadinstance)
    {
        if (empty($uploadinstance->link) || empty($uploadinstance->path_to_coursefile)
            || empty($uploadinstance->language) || empty($uploadinstance->name) || empty($uploadinstance->server) || empty($uploadinstance->course)) {
            throw new \moodle_exception('parameter_missing', 'mod_mumie');
        }
    }

    private function create_mumie_from_uploadinstance($uploadinstance)
    {
        $mumie = new \stdClass();
        $mumie->taskurl = $uploadinstance->link . '?lang=' . $uploadinstance->language;
        $mumie->mumie_coursefile = $uploadinstance->path_to_coursefile;
        $mumie->language = $uploadinstance->language;
        $mumie->course = $this->courseid;
        $mumie->mumie_course = $uploadinstance->course;
        $mumie->intro = '';
        $mumie->points = 100;
        $mumie->name = \mod_mumie\locallib::get_default_name($uploadinstance) ?? $uploadinstance->name;
        $mumie->privategradepool = $this->gradepoolsettings;
        return $mumie;
    }

    private function create_mumie_course_module($mumie)
    {
        global $CFG, $DB, $COURSE;
        
        $mumieinstance = mumie_add_instance($mumie, null);
        $cm = $this->create_course_module();
        $this->finish_setup_course_module($mumieinstance, $cm);
    }

    private function create_course_module()
    {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/modlib.php');
        list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($this->course, 'mumie', $this->section);
        $data->coursemodule = $data->id = add_course_module($data);
        return $data;
    }

    protected function finish_setup_course_module($instanceid, $cm)
    {
        global $DB, $USER;

        if (!$instanceid) {
            // Something has gone wrong - undo everything we can.
            course_delete_module($cm->id);
            throw new \moodle_exception('errorcreatingactivity', 'moodle', '', $this->module->name);
        }

        // Note the section visibility
        $visible = get_fast_modinfo($this->course)->get_section_info($this->section)->visible;

        $DB->set_field('course_modules', 'instance', $instanceid, array('id' => $cm->id));
        // Rebuild the course cache after update action
        //rebuild_course_cache($this->course->id, true);

        $sectionid = course_add_cm_to_section($this->course, $cm->id, $this->section);

        set_coursemodule_visible($cm->id, $visible);
        if (!$visible) {
            $DB->set_field('course_modules', 'visibleold', 1, array('id' => $cm->id));
        }

        // retrieve the final info about this module.
        $info = get_fast_modinfo($this->course);
        if (!isset($info->cms[$cm->id])) {
            // The course module has not been properly created in the course - undo everything.
            course_delete_module($cm->id);
            //throw new \moodle_exception('errorcreatingactivity', 'moodle', '', $this->module->name);
            throw new \moodle_exception('errorcreatingactivity', 'moodle', '', "TODO");

        }
        $mod = $info->get_cm($cm->id);

        // Trigger course module created event.
        $event = \core\event\course_module_created::create_from_cm($mod);
        $event->trigger();
    }
}
