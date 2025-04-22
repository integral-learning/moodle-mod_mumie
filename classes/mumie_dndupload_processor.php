<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file describes a class used to create MUMIE Tasks via drag & drop.
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');

/**
 * This processor is responsible for the creation of MUMIE Task that are being imported via dnd_handler.
 *
 * Some functions are taken and adapted from course/dnduploadlib.php.
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_dndupload_processor {
    /**
     * The number of the sections we want to add MUMIE Tasks to.
     * @var int
     */
    private $section;

    /**
     * The data type of the uploaded file.
     * @var string
     */
    private $type;

    /**
     * The uploaded file.
     * @var \stdClass
     */
    private $upload;

    /**
     * The context of the current course.
     * @var \context_course
     */
    private $coursecontext;

    /**
     * The course we want to create the MUMIE Tasks in.
     * @var \stdClass
     */
    private $course;

    /**
     * The gradepool settings we're going to use for the new MUMIE Tasks.
     * @var int
     */
    private $gradepoolsettings;

    /**
     * Constructor
     * @param int $courseid Id of course we want to create Tasks in
     * @param int $section The section number we are uploading to
     * @param string $type The type identifier of the uploaded data
     * @param stdClass $upload The object that has been uploaded
     */
    public function __construct($courseid, $section, $type, $upload) {
        $this->section = $section;
        $this->type = $type;
        $this->upload = $upload;
        $this->gradepoolsettings = $this->get_course_gradepool_setting();
        $this->coursecontext = \context_course::instance($courseid);
        global $COURSE;
        $this->course = $COURSE;
    }

    /**
     * Start the creation of MUMIE Tasks for the uploaded data.
     *
     * We're hooking into the regular dnd_upload process.
     * That's why the first uploaded problem needs to be handled differently form any further instances.
     * @return int id of newly added grade item or false
     */
    public function process() {
        require_capability('moodle/course:manageactivities', $this->coursecontext);
        require_sesskey();

        if ($this->type == 'mumie/json') {
            return $this->handle_single_upload();
        }
        if ($this->type == 'mumie/jsonArray') {
            return $this->handle_multi_upload();
        }
        return false;
    }

    /**
     * Handle uploads of type mumie/json. Only a single Mumie Task will be created.
     * This format is used by MUMIE servers.
     * @return int id of newly added grade item
     */
    private function handle_single_upload() {
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

    /**
     * Handle uploads of type mumie/jsonArray. This function creates one or more instances of MUMIE Task.
     * This format is used by LEMON servers.
     * @return int id of newly added grade item
     */
    private function handle_multi_upload() {
        // This array holds unique server objects.
        $servers = [];
        $this->upload = (array) $this->upload;
        foreach ($this->upload as $uploadinstance) {
            $uploadinstance = json_decode($uploadinstance);
            $this->validate_upload_params($uploadinstance);
            $server = new \auth_mumie\mumie_server();
            $server->set_urlprefix($uploadinstance->server);
            $servers[$uploadinstance->server] = $server;
        }

        $this->validate_servers(array_values($servers));
        $this->create_missing_servers(array_values($servers));

        $result;
        $uploadlength = count($this->upload);
        for ($i = 0; $i < $uploadlength; $i++) {
            $uploadinstance = json_decode($this->upload[$i]);
            $mumie = $this->create_mumie_from_uploadinstance($uploadinstance, $servers[$uploadinstance->server]);
            $mumie->server = $servers[$uploadinstance->server]->get_urlprefix();
            if ($i == 0) {
                $result = mumie_add_instance($mumie, null);
            } else {
                $module = $this->create_mumie_course_module($mumie);
            }
        }

        // Rebuild the course cache after update action.
        rebuild_course_cache($this->course->id, true);

        // Return id of first mumie instance created.
        return $result;
    }

    /**
     * Check whether all servers are valid MUMIE/Lemon servers.
     *
     * If a URL is not valid, an exception is thrown.
     *
     * @param \auth_mumie\mumie_server[] $servers The list of server we want to validate
     */
    private function validate_servers($servers) {
        foreach ($servers as $server) {
            if (!$server->is_valid_mumie_server()) {
                throw new \moodle_exception('mumie_form_server_not_existing', 'auth_mumie');
            }
        }
    }

    /**
     * Make sure that there is a server configuration for every server used. If there aren't, create them.
     *
     * @param \auth_mumie\mumie_server[] $servers The list of servers for which we need server configurations.
     */
    private function create_missing_servers($servers) {
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

    /**
     * Get the value for MUMIE Task's gradepool setting in this course.
     *
     * @return int gradepool value for the imported MUMIE Tasks.
     */
    private function get_course_gradepool_setting() {
        global $DB, $COURSE;
        $exitingtasks = array_values(
            $DB->get_records(MUMIE_TASK_TABLE, ["course" => $COURSE->id])
        );
        $adminsetting = get_config('auth_mumie', 'defaultgradepool');
        if (count($exitingtasks) > 0) {
            return $exitingtasks[0]->privategradepool;
        }
        if ($adminsetting != -1) {
            return $adminsetting;
        }
        return null;
    }

    /**
     * Make sure that there are all necessary parameters set in the uploaded problem instance.
     *
     * @param \stdClass $uploadinstance a problem instance that's being imported
     */
    private function validate_upload_params($uploadinstance) {
        if (empty($uploadinstance->link) || empty($uploadinstance->path_to_coursefile)
            || empty($uploadinstance->language) || empty($uploadinstance->name)
            || empty($uploadinstance->server) || empty($uploadinstance->course)) {
            throw new \moodle_exception('parameter_missing', 'mod_mumie');
        }
    }

    /**
     * Get a mumie object from the uploaded problem instance.
     *
     * @param \stdClass $uploadinstance The uploaded problem.
     * @return \stdClass The mumie object that's going to be created.
     */
    private function create_mumie_from_uploadinstance($uploadinstance) {
        $mumie = new \stdClass();
        $mumie->taskurl = $uploadinstance->link . '?lang=' . $uploadinstance->language;
        $mumie->mumie_coursefile = $uploadinstance->path_to_coursefile;
        $mumie->language = $uploadinstance->language;
        $mumie->course = $this->course->id;
        $mumie->mumie_course = $uploadinstance->course;
        $mumie->intro = '';
        $mumie->points = 100;
        $mumie->name = \mod_mumie\locallib::get_default_name($uploadinstance) ?? $uploadinstance->name;
        $mumie->privategradepool = $this->gradepoolsettings;
        $mumie->isgraded = $uploadinstance->isGraded;
        return $mumie;
    }

    /**
     * Create database entries for the new mumie instance and the linked course module.
     *
     * @param \stdClass $mumie The mumie object we want to add to the course.
     */
    private function create_mumie_course_module($mumie) {
        $mumieinstance = mumie_add_instance($mumie, null);
        $cm = $this->create_course_module();
        $this->finish_setup_course_module($mumieinstance, $cm);
    }

    /**
     * Create a new course module in the database.
     */
    private function create_course_module() {
        global $CFG;
        require_once($CFG->dirroot.'/course/modlib.php');
        list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($this->course, 'mumie', $this->section);
        $data->coursemodule = $data->id = add_course_module($data);
        return $data;
    }

    /**
     * Set missing values for course_module and finish the setup.
     *
     * @param int $instanceid id of newly created mumie instance
     * @param \stdClass $cm Newly created coursemodule instance
     */
    protected function finish_setup_course_module($instanceid, $cm) {
        global $DB;

        if (!$instanceid) {
            // Something has gone wrong - undo everything we can.
            course_delete_module($cm->id);
            throw new \moodle_exception('errorcreatingactivity', 'moodle', '', $this->module->name);
        }

        // Note the section visibility.
        $visible = get_fast_modinfo($this->course)->get_section_info($this->section)->visible;

        $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cm->id]);

        course_add_cm_to_section($this->course, $cm->id, $this->section);

        set_coursemodule_visible($cm->id, $visible);
        if (!$visible) {
            $DB->set_field('course_modules', 'visibleold', 1, ['id' => $cm->id]);
        }

        // Retrieve the final info about this module.
        $info = get_fast_modinfo($this->course);
        if (!isset($info->cms[$cm->id])) {
            // The course module has not been properly created in the course - undo everything.
            course_delete_module($cm->id);
            throw new \moodle_exception('errorcreatingactivity', 'moodle', '', "mod_mumie");
        }
        $mod = $info->get_cm($cm->id);

        // Trigger course module created event.
        $event = \core\event\course_module_created::create_from_cm($mod);
        $event->trigger();
    }
}
