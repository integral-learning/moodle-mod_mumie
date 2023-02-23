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
 * This moodle form is used to insert or update MumieServer in the database
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use mod_mumie\locallib;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');

/**
 * This moodle form is used to insert or update MumieServer in the database
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mumie_mod_form extends moodleform_mod {

    /** All valid MUMIE servers (including course structure) available.
     * @var array
     */
    private $servers;

    /**
     * Define fields and default values for the mumie server form
     * @return void
     */
    public function definition() {
        global $PAGE, $COURSE, $USER;

        $mform = &$this->_form;

        $this->servers = $this->get_valid_servers_with_structure();
        $serveroptions = $this->get_server_options();

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'mumie_multi_edit', get_string('mumie_form_activity_header', 'mod_mumie'));

        $mform->addElement(
            "text",
            "name",
            get_string("mumie_form_activity_name", "mod_mumie"),
            array("class" => "mumie_text_input")
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null);

        $this->standard_intro_elements(get_string('mumieintro', 'mumie'));
        $mform->setAdvanced('introeditor');
        $mform->setAdvanced('showdescription');

        $mform->addElement("select", "server", get_string('mumie_form_activity_server', "mod_mumie"), $serveroptions);
        $mform->addHelpButton("server", 'mumie_form_activity_server', 'mumie');

        if (has_capability("auth/mumie:addserver", \context_course::instance($COURSE->id), $USER)) {
            $contentbutton = $mform->addElement(
                'button',
                'add_server_button',
                get_string("mumie_form_add_server_button", "mod_mumie"),
                array()
            );
        }

        $mform->addElement(
            "text",
            "mumie_course",
            get_string('mumie_form_activity_course', "mod_mumie"),
            array("disabled" => true, "class" => "mumie_text_input")
        );
        $mform->setType("mumie_course", PARAM_TEXT);

        $mform->addElement("hidden", "language", $USER->lang, array("id" => "id_language"));
        $mform->setType("language", PARAM_TEXT);

        $mform->addElement("text", "taskurl", get_string('mumie_form_activity_problem', "mod_mumie"), array("disabled" => true, "class" => "mumie_text_input"));
        $mform->setType("taskurl", PARAM_TEXT);

        $mform->addHelpButton("taskurl", 'mumie_form_activity_problem', 'mumie');
        $mform->setType("task_display_element", PARAM_TEXT);

        $contentbutton = $mform->addElement('button', 'prb_selector_btn', get_string('mumie_form_prb_selector_btn', 'mod_mumie'));

        $launchoptions = array();
        $launchoptions[MUMIE_LAUNCH_CONTAINER_EMBEDDED] = get_string("mumie_form_activity_container_embedded", "mod_mumie");
        $launchoptions[MUMIE_LAUNCH_CONTAINER_WINDOW] = get_string("mumie_form_activity_container_window", "mod_mumie");

        $mform->addElement("select", "launchcontainer", get_string('mumie_form_activity_container', "mod_mumie"), $launchoptions);
        $mform->setDefault("launchcontainer", MUMIE_LAUNCH_CONTAINER_WINDOW);
        $mform->addHelpButton("launchcontainer", "mumie_form_activity_container", "mumie");
        $this->add_info_box(get_string('mumie_form_launchcontainer_info', 'mod_mumie'));

        $mform->addElement("html", "<br><div><i>" . get_string('mumie_form_wiki_link', 'mod_mumie') . "</i></div><br>");

        $mform->addElement("hidden", "mumie_coursefile", "");
        $mform->setType("mumie_coursefile", PARAM_TEXT);

        $mform->addElement("hidden", "mumie_missing_config", null);
        $mform->setType("mumie_missing_config", PARAM_TEXT);

        $mform->addElement("hidden", "mumie_server_structure", json_encode($this->servers));
        $mform->setType("mumie_server_structure", PARAM_RAW);

        $mform->addElement("hidden", "mumie_org", get_config("auth_mumie", "mumie_org"));
        $mform->setType("mumie_org", PARAM_TEXT);

        $mform->addElement('hidden', 'isgraded', null, array("id" => "id_mumie_isgraded"));
        $mform->setType("isgraded", PARAM_TEXT);

        $mform->addElement('hidden', 'worksheet', null, array("id" => "id_mumie_worksheet"));
        $mform->setType("worksheet", PARAM_TEXT);

        // Add standard course module grading elements.
        $this->standard_grading_coursemodule_elements();

        $mform->removeElement('grade');
        $mform->addElement("text", "points", get_string("mumie_form_points", "mod_mumie"));
        $mform->setDefault("points", 100);
        $mform->setType("points", PARAM_INT);
        $mform->addHelpButton("points", "mumie_form_points", "mumie");

        $mform->addElement('date_time_selector', 'duedate', get_string("mumie_due_date", "mod_mumie"), array('optional' => true));
        $mform->addHelpButton("duedate", 'mumie_due_date', 'mumie');

        $radioarray = array();
        $disablegradepool = $this->disable_gradepool_selection($COURSE->id);
        $gradepoolmsg = '';
        if ($disablegradepool) {
            $attributes = array('disabled' => '');
            $gradepoolmsg = get_string('mumie_form_grade_pool_note', 'mod_mumie');
        } else {
            $attributes = array();
            $gradepoolmsg = get_string('mumie_form_grade_pool_warning', 'mod_mumie');
        }
        $radioarray[] = $mform->createElement('radio', 'privategradepool', '', get_string('yes'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'privategradepool', '', get_string('no'), 1, $attributes);
        $mform->addGroup($radioarray, 'privategradepool', get_string('mumie_form_grade_pool', 'mod_mumie'), array('<br>'), false);
        $mform->addHelpButton('privategradepool', 'mumie_form_grade_pool', 'mumie');
        $this->add_info_box($gradepoolmsg);

        if (!$disablegradepool) {
            $mform->addRule('privategradepool', get_string('mumie_form_required', 'mod_mumie'), 'required', null, 'client');
        }

        $mform->addElement('header', 'general', get_string('mumie_form_tasks_edit', 'mod_mumie'));

        $mform->addElement(
            'html',
            '<div>'
            . get_string('mumie_form_tasks_edit_info', 'mod_mumie')
            . '<br><br>'
            . '</div>'
        );

        $this->add_property_selection();
        $this->add_task_selection();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $mform->setAdvanced('cmidnumber');

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
        $context = context_course::instance($COURSE->id);
        $this->disable_grade_rules();

        $jsparams = array(
            json_encode($context->id),
            get_config('auth_mumie', 'mumie_problem_selector_url'),
            $USER->lang
        );
        $PAGE->requires->js_call_amd('mod_mumie/mod_form', 'init', $jsparams);
    }

    /**
     * Validate the form data
     * @param array $data form data
     * @param array $files files uploaded
     * @return array associative array of errors
     */
    public function validation($data, $files) {
        $errors = array();

        if (!isset($data["server"]) && !isset($data["mumie_missing_config"])) {
            $errors["server"] = get_string('mumie_form_required', 'mod_mumie');
        }

        if (!isset($data["mumie_course"]) && !isset($data["mumie_missing_config"])) {
            $errors["mumie_course"] = get_string('mumie_form_required', 'mod_mumie');
        }

        $taskurlvalid = isset($data["taskurl"]) && $data["taskurl"] !== "";
        if (!$taskurlvalid && (!isset($data["mumie_missing_config"]) ||$data["mumie_missing_config"] === "" )) {
            $errors["prb_selector_btn"] = get_string('mumie_form_required', 'mod_mumie');
        }

        if (array_key_exists('completion', $data) && $data['completion'] == COMPLETION_TRACKING_AUTOMATIC) {
            $completionpass = isset($data['completionpass']) ? $data['completionpass'] : $this->current->completionpass;

            // Show an error if require passing grade was selected and the grade to pass was set to 0.
            if ($completionpass && (empty($data['gradepass']) || grade_floatval($data['gradepass']) == 0)) {
                if (isset($data['completionpass'])) {
                    $errors['completionpassgroup'] = get_string('gradetopassnotset', 'mumie');
                } else {
                    $errors['gradepass'] = get_string('gradetopassmustbeset', 'mumie');
                }
            }
        }

        if ($data['duedate']) {
            if (time() - $data['duedate'] > 0) {
                $errors['duedate'] = get_string('mumie_form_due_date_must_be_future', 'mod_mumie');
            }
        }

        if (array_key_exists('instance', $data) && $data['instance']) {
            $mumie = locallib::get_mumie_task($data['instance']);
            if ($mumie && $mumie->isgraded !== $data['isgraded']) {
                $errors['prb_selector_btn'] = get_string('mumie_form_cant_change_isgraded', 'mod_mumie');
            }
        }

        return $errors;
    }

    /**
     * Get all options for server drop-down menu
     *
     * @return array
     */
    private function get_server_options() {
        $serveroptions = array();
        foreach ($this->servers as $server) {
            $serveroptions[$server->get_urlprefix()] = $server->get_name();
        }
        return $serveroptions;
    }

    /**
     * Provide option to mark an activity automatically as completed once a passing grade was archived
     *
     * This function is copied from mod_quiz version 2018051400
     * @return array containing the name of the mform group that has been added to the form
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $items = array();

        $group = array();
        $group[] = $mform->createElement(
            'advcheckbox',
            'completionpass',
            null,
            get_string('completionpass', 'mumie'),
            array('group' => 'cpass')
        );
        $mform->disabledIf('completionpass', 'completionusegrade', 'notchecked');
        $mform->addGroup($group, 'completionpassgroup', get_string('completionpass', 'mumie'), ' &nbsp; ', false);
        $mform->addHelpButton('completionpassgroup', 'completionpass', 'mumie');
        $items[] = 'completionpassgroup';
        return $items;
    }

    /**
     * Disable all options for grades if the user has chosen to link a course instead of a problem.
     */
    private function disable_grade_rules() {
        $mform = $this->_form;
        $mform->disabledIf('gradepass', 'isgraded', 'eq', '0');
        $mform->disabledIf('duedate[enabled]', 'isgraded', 'eq', '0');
        $mform->disabledIf('points', 'isgraded', 'eq', '0');
        $mform->disabledIf('gradecat', 'isgraded', 'eq', '0');
    }

    /**
     * Adds the property selection, which is needed for the multi editing, to the form.
     */
    private function add_property_selection() {
        $mform = $this->_form;
        $mform->addElement("hidden", "mumie_selected_task_properties", "[]");
        $mform->setType("mumie_selected_task_properties", PARAM_RAW);
        $taskproperties = array(
            array(get_string('mumie_due_date', 'mod_mumie'), "duedate"),
            array(get_string('mumie_form_activity_container', 'mod_mumie'), "launchcontainer"),
            array(get_string('mumie_form_points', 'mod_mumie'), "points")
        );
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mumie_table';
        $table->head = array(get_string('mumie_form_properties', 'mod_mumie'), ' ');

        foreach ($taskproperties as $taskproperty) {
            $label = $taskproperty[0];
            $value = $taskproperty[1];
            $checkboxhtml = html_writer::checkbox("mumie_multi_edit_property", $value, false);
            $table->data[] = array($label, $checkboxhtml);
        }

        $htmltable = html_writer::table($table);

        $mform->addElement(
            'html',
            '<div>'
            . get_string('mumie_form_task_properties_selection_info', 'mod_mumie')
            .'</div>'
            .'<div class="mumie_table_wrapper">'
            . $htmltable
            . '</div>' );

    }

    /**
     * Adds the task selection, which is needed for the multi editing, to the form.
     */
    private function add_task_selection() {
        global $COURSE;
        $cm = &$this->_cm;
        $mform = $this->_form;

        $mform->addElement("hidden", "mumie_selected_tasks", "[]");
        $mform->setType("mumie_selected_tasks", PARAM_RAW);
        $modules = get_all_instances_in_course("mumie", $COURSE);

        if (!is_null($cm)) {
            $modules = array_filter($modules, function($elem) use($cm){
                return !($elem->id === $cm->instance);
            });
        }

        if (count($modules) < 1) {
            $notfound = html_writer::tag(
                'i',
                "- " . get_string("mumie_no_other_task_found", "mod_mumie") ." -",
                array("style" => "margin-left: 10px;")
            );
            $mform->addElement(
                'html',
                get_string('mumie_form_tasks_selection_info', 'mod_mumie')
                . '<div class="mumie_table_wrapper">'
                . $notfound
                . '</div>' );
            return;
        }

        $tables = array();
        foreach ($modules as $module) {
            $section = $module->section;
            if (!array_key_exists($section, $tables)) {
                $table = new \html_table;
                $table->attributes['class'] = 'generaltable mumie_table';
                $table->head = array(
                    get_string(
                        'mumie_form_topic',
                        'mod_mumie',
                        get_section_name($COURSE->id, $section)
                    ),
                    html_writer::checkbox(
                        "mumie_multi_edit_section",
                        $section,
                        false
                    )
                );
                $tables[$section] = $table;
            } else {
                $table = $tables[$section];
            }
            $checkboxhtml = html_writer::checkbox(
                "mumie_multi_edit_task",
                $module->id,
                false,
                '',
                array(
                    "section" => $section
            ));
            $table->data[] = array($module->name, $checkboxhtml);
        }

        $htmltables = "";
        foreach ($tables as $a) {
            $htmltables = $htmltables . html_writer::table($a);
        }

        $mform->addElement(
            'html',
            get_string('mumie_form_tasks_selection_info', 'mod_mumie')
            .'<div class="mumie_table_wrapper">'
            . $htmltables
            . '</div>' );
    }

    /**
     * Make some changes to the loaded data and the form.
     *
     * In some cases (e.g. drag&drop, changes in json coming from the MUMIE-Backend),
     * users have added MUMIE problems that are not officially part of the server structure.
     * We need to add those problems to the server structure or the user will not be able
     * to edit the MUMIE task.
     *
     * Set a hidden value, if the MUMIE server configuration that has been used in this MUMIE task has been deleted.
     * Javascript uses this information to display an error message.
     *
     * Remove pre-selection for gradepools, if the decision about it is still pending.
     *
     * This function is called, when a MUMIE task is edited.
     * @param stdClass $data instance of MUMIE task, that is being edited
     * @return void
     */
    public function set_data($data) {
        global $COURSE, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/mumie/locallib.php');

        // Decisions about gradepools are final. Don't preselect an option is the decision is still pending!
        if (!mod_mumie\locallib::course_contains_mumie_tasks($COURSE->id)) {
            $data->privategradepool = get_config('auth_mumie', 'defaultgradepool');
        } else {
            if (!isset($data->privategradepool)) {
                $data->privategradepool = array_values(
                    $DB->get_records(MUMIE_TASK_TABLE, array("course" => $COURSE->id))
                )[0]->privategradepool ?? -1;
            }
        }
        // The following changes only apply to edits, so skip them if not necessary.
        if (!isset($data->server)) {
            parent::set_data($data);
            return;
        }

        // Set a flag, if the server configuration is missing!
        $filter = array_filter(
            auth_mumie\mumie_server::get_all_servers(),
            function ($server) use ($data) {
                if (!isset($data->server)) {
                    return false;
                }
                return $server->get_urlprefix() === $data->server;
            }
        );

        if (count($filter) < 1 && isset($data->server)) {
            $data->mumie_missing_config = $data->server;
        }

        // Check whether the task represents an entire course. If so, check the responding box in the form.
        $mform = &$this->_form;
        $server = \auth_mumie\mumie_server::get_by_urlprefix($data->server);
        if ($server->get_id()) {
            $server->load_structure();
            $course = $server->get_course_by_coursefile($data->mumie_coursefile);
            $completecourse = $data->taskurl == $course->get_link() . "?lang=" . $data->language;
            if ($completecourse) {
                $data->mumie_complete_course = 1;
            }
            // Check, whether we need to add a custom problem to the server structure.
            $task = $course->get_task_by_link($data->taskurl);
            if (is_null($task) && !$completecourse) {
                // Add a problem derived from the edited task's taskurl to the server structure.
                $serverstructure = $this->get_valid_servers_with_structure();
                $filteredservers = array_filter($serverstructure, function ($s) use ($data) {
                    return $s->get_urlprefix() == $data->server;
                });
                $filteredserver = array_values($filteredservers)[0];

                $filteredserver->add_custom_problem_to_structure($data);

                // Save the updated server structure in the hidden input field.
                $mform->removeElement('mumie_server_structure');
                $mform->addElement("hidden", "mumie_server_structure", json_encode($serverstructure));
                $mform->setType("mumie_server_structure", PARAM_RAW);
            }
        }
        // This option must not be changed to avoid messing with grades in the database.
        $mform->updateElementAttr("mumie_complete_course", array("disabled" => "disabled"));

        parent::set_data($data);
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionpass']);
    }


    /**
     * The decision regarding gradepools is final. We need to know whether we should disable the selection boxes.
     *
     * @param int $courseid id of the current course
     * @return bool whether to disable gradepool selection
     */
    private function disable_gradepool_selection($courseid) {
        global $DB;
        $records = $DB->get_records(MUMIE_TASK_TABLE, array("course" => $courseid));
        if (get_config('auth_mumie', 'defaultgradepool') != -1) {
            return true;
        }
        if (count($records) < 1) {
            return false;
        }
        return isset(array_values($records)[0]->privategradepool);
    }

    /**
     * Add a custom information box to the form.
     *
     * @param String $message The message to display
     */
    private function add_info_box($message) {
        $mform = &$this->_form;
        $mform->addElement(
            'html',
            '<div class="form-group row  fitem ">'
            . '<div class="col-md-3"></div>'
            . '<div class="col-md-9 felement">'
            . $message
            . '</div></div>'
        );
    }

    /**
     * Get all MUMIE servers that have courses.
     *
     * Display a warning message if a course is invalid.
     *
     * @return array
     */
    private function get_valid_servers_with_structure(): array {
        global $CFG;
        require_once($CFG->dirroot . '/lib/classes/notification.php');

        $servers = auth_mumie\mumie_server::get_all_servers_with_structure();
        $validservers = array();
        foreach ($servers as $server) {
            if (count($server->get_courses()) === 0) {
                \core\notification::warning(get_string(
                    'mumie_form_no_course_on_server',
                    'mod_mumie',
                    $server->get_name())
                );
            } else {
                array_push($validservers, $server);
            }
        }
        return $validservers;
    }
}
