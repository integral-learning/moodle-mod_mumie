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
require_once($CFG->dirroot . '/mod/mumie/forms/mumie_task_validator.php');


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
    public function definition(): void {
        global $PAGE, $COURSE, $USER;

        $mform = &$this->_form;

        $this->servers = $this->get_valid_servers_with_structure();
        $serveroptions = $this->get_server_options();

        $this->add_general_fields($mform, $serveroptions);
        $this->standard_grading_coursemodule_elements();
        $this->set_grade_fields($mform);
        $this->set_applyto_fields($mform);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $mform->setAdvanced('cmidnumber');

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
        $context = context_course::instance($COURSE->id);
        $this->disable_grade_rules();

        $jsparams = [
            json_encode($context->id),
            get_config('auth_mumie', 'mumie_problem_selector_url'),
            $USER->lang,
        ];
        $PAGE->requires->js_call_amd('mod_mumie/mod_form', 'init', $jsparams);
    }

    /**
     * Define general fields and default values
     *
     * @param MoodleQuickForm $mform
     * @param array $serveroptions
     * @return void
     */
    private function add_general_fields(\MoodleQuickForm $mform, $serveroptions): void {
        global $COURSE, $USER;
        $mform->addElement('header', 'mumie_multi_edit', get_string('mumie_form_activity_header', 'mod_mumie'));

        $mform->addElement(
            "text",
            "name",
            get_string("mumie_form_activity_name", "mod_mumie"),
            ["class" => "mumie_text_input"]
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null);

        $this->standard_intro_elements(get_string('mumieintro', 'mumie'));
        $mform->setAdvanced('introeditor');
        $mform->setAdvanced('showdescription');

        $mform->addElement("select", "server", get_string('mumie_form_activity_server', "mod_mumie"), $serveroptions);
        $mform->addHelpButton("server", 'mumie_form_activity_server', 'mumie');

        if (has_capability("auth/mumie:addserver", \context_course::instance($COURSE->id), $USER)) {
            $mform->addElement(
                'button',
                'add_server_button',
                get_string("mumie_form_add_server_button", "mod_mumie"),
                []
            );
        }

        $mform->addElement(
            "text",
            "mumie_course",
            get_string('mumie_form_activity_course', "mod_mumie"),
            ["disabled" => true, "class" => "mumie_text_input"]
        );
        $mform->setType("mumie_course", PARAM_TEXT);

        $mform->addElement("hidden", "language", $USER->lang, ["id" => "id_language"]);
        $mform->setType("language", PARAM_TEXT);

        $mform->addElement("hidden", "taskurl", null);
        $mform->setType("taskurl", PARAM_TEXT);

        $mform->addElement(
            "text",
            "task_display_element",
            get_string('mumie_form_activity_problem', "mod_mumie"),
            ["disabled" => true, "class" => "mumie_text_input"]
        );
        $mform->addHelpButton("task_display_element", 'mumie_form_activity_problem', 'mumie');
        $mform->setType("task_display_element", PARAM_TEXT);

        $mform->addElement('button', 'prb_selector_btn', get_string('mumie_form_prb_selector_btn', 'mod_mumie'));
        $wiki = 'https://wiki.mumie.net/wiki/MUMIE-Moodle-integration-for-teachers#how-to-create-mumie-tasks-with-drag-and-drop';
        $this->add_info_box(
            get_string(
                "mumie_multi_problem_selector",
                "mod_mumie",
                $wiki));
        $mform->addElement('button', 'multi_problem_selector_btn', get_string('mumie_form_multi_prb_selector_btn', 'mod_mumie'));

        $launchoptions = [];
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

        $mform->addElement('hidden', 'isgraded', null, ["id" => "id_mumie_isgraded"]);
        $mform->setType("isgraded", PARAM_TEXT);

        $mform->addElement('hidden', 'worksheet', null, ["id" => "id_mumie_worksheet"]);
        $mform->setType("worksheet", PARAM_TEXT);
    }

    /**
     * Define grade fields and default values
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    private function set_grade_fields(\MoodleQuickForm $mform): void {
        global $COURSE;
        $mform->removeElement('grade');
        $mform->addElement("text", "points", get_string("mumie_form_points", "mod_mumie"));
        $mform->setDefault("points", 100);
        $mform->setType("points", PARAM_INT);
        $mform->addHelpButton("points", "mumie_form_points", "mumie");

        $duration = [
            'unlimited' => get_string('mumie_unlimited', 'mod_mumie'),
            'duedate' => get_string('mumie_due_date', 'mod_mumie'),
            'timelimit' => get_string('mumie_timelimit', 'mod_mumie'),
        ];
        $mform->addElement('select', 'duration_selector', get_string('mumie_duration_selector', 'mod_mumie'), $duration);
        $mform->addHelpButton('duration_selector', 'mumie_duration_selector', 'mod_mumie');
        $mform->setDefault('duration_selector', 'unlimited');

        $mform->addElement('static', 'unlimited_info', '', get_string('mumie_unlimited_help', 'mod_mumie'));
        $mform->addElement('static', 'duedate_info', '', get_string('mumie_due_date_help', 'mod_mumie'));
        $mform->addElement('static', 'timelimit_info', '', get_string('mumie_timelimit_help', 'mod_mumie'));
        $mform->addElement('date_time_selector', 'duedate', '');
        $mform->addElement('duration', 'timelimit', '');

        $mform->hideIf('unlimited_info', 'duration_selector', 'neq', 'unlimited');
        $mform->hideIf('duedate_info', 'duration_selector', 'neq', 'duedate');
        $mform->hideIf('duedate', 'duration_selector', 'neq', 'duedate');
        $mform->hideIf('timelimit_info', 'duration_selector', 'neq', 'timelimit');
        $mform->hideIf('timelimit', 'duration_selector', 'neq', 'timelimit');

        $radioarray = [];
        $disablegradepool = $this->disable_gradepool_selection($COURSE->id);
        $gradepoolmsg = '';
        if ($disablegradepool) {
            $attributes = ['disabled' => ''];
            $gradepoolmsg = get_string('mumie_form_grade_pool_note', 'mod_mumie');
        } else {
            $attributes = [];
            $gradepoolmsg = get_string('mumie_form_grade_pool_warning', 'mod_mumie');
        }
        $radioarray[] = $mform->createElement('radio', 'privategradepool', '', get_string('yes'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'privategradepool', '', get_string('no'), 1, $attributes);
        $mform->addGroup($radioarray, 'privategradepool', get_string('mumie_form_grade_pool', 'mod_mumie'), ['<br>'], false);
        $mform->addHelpButton('privategradepool', 'mumie_form_grade_pool', 'mumie');
        $this->add_info_box($gradepoolmsg);

        if (!$disablegradepool) {
            $mform->addRule('privategradepool', get_string('mumie_form_required', 'mod_mumie'), 'required', null, 'client');
        }
    }

    /**
     * Define apply to fields and default values
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    private function set_applyto_fields(\MoodleQuickForm $mform): void {
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
    }

    /**
     * Validate the form data
     * @param array $data form data
     * @param array $files files uploaded
     * @return array associative array of errors
     */
    public function validation($data, $files): array {
        return \mod_mumie\mumie_task_validator::get_errors($data, $this->current);
    }

    /**
     * Get all options for server drop-down menu
     *
     * @return array
     */
    private function get_server_options(): array {
        $serveroptions = [];
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
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $items = [];

        $group = [];
        $completionpasselement = $this->get_completion_rule_element_name('completionpass');
        $group[] = $mform->createElement(
            'advcheckbox',
            $completionpasselement,
            null,
            get_string('completionpass', 'mumie'),
            ['group' => 'cpass']
        );
        $completionusegradeelement = $this->get_completion_rule_element_name('completionusegrade');
        $mform->disabledIf($completionpasselement, $completionusegradeelement, 'notchecked');
        $completionpassgroupelement = $this->get_completion_rule_element_name('completionpassgroup');
        $mform->addGroup($group, $completionpassgroupelement, get_string('completionpass', 'mumie'), ' &nbsp; ', false);
        $mform->addHelpButton($completionpassgroupelement, 'completionpass', 'mumie');
        $items[] = $completionpassgroupelement;
        return $items;
    }

    /**
     * Get the completion rule's element name.
     *
     * Conditionally add suffix for Moodle >= 4.3.
     *
     * @param string $rawname The raw name of the completion rule.
     * @return string The properly suffixed element name.
     */
    private function get_completion_rule_element_name($rawname): string {
        global $CFG;
        if ($CFG->branch < 403) {
            $suffix = '';
        } else {
            $suffix = $this->get_suffix();
        }
        return $rawname . $suffix;
    }

    /**
     * Disable all options for grades if the user has chosen to link a course instead of a problem.
     */
    private function disable_grade_rules(): void {
        $mform = $this->_form;
        $gradeelements = [
            'gradepass', 'duration_selector', 'duedate_info', 'duedate',
            'timelimit_info', 'timelimit', 'points', 'gradecat',
        ];
        foreach ($gradeelements as $element) {
            $mform->disabledIf($element, 'isgraded', 'eq', '0');
        }
    }

    /**
     * Adds the property selection, which is needed for the multi editing, to the form.
     */
    private function add_property_selection(): void {
        $mform = $this->_form;
        $mform->addElement("hidden", "mumie_selected_task_properties", "[]");
        $mform->setType("mumie_selected_task_properties", PARAM_RAW);
        $taskproperties = [
            [get_string('mumie_form_activity_container', 'mod_mumie'), "launchcontainer"],
            [get_string('mumie_form_points', 'mod_mumie'), "points"],
        ];
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mumie_table';
        $table->head = [get_string('mumie_form_properties', 'mod_mumie'), ' '];

        foreach ($taskproperties as $taskproperty) {
            $label = $taskproperty[0];
            $value = $taskproperty[1];
            $checkboxhtml = html_writer::checkbox("mumie_multi_edit_property", $value, false);
            $table->data[] = [$label, $checkboxhtml];
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
    private function add_task_selection(): void {
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
                ["style" => "margin-left: 10px;"]
            );
            $mform->addElement(
                'html',
                get_string('mumie_form_tasks_selection_info', 'mod_mumie')
                . '<div class="mumie_table_wrapper">'
                . $notfound
                . '</div>' );
            return;
        }

        $tables = [];
        foreach ($modules as $module) {
            $section = $module->section;
            if (!array_key_exists($section, $tables)) {
                $table = new \html_table;
                $table->attributes['class'] = 'generaltable mumie_table';
                $table->head = [
                    get_string(
                        'mumie_form_topic',
                        'mod_mumie',
                        get_section_name($COURSE->id, $section)
                    ),
                    html_writer::checkbox(
                        "mumie_multi_edit_section",
                        $section,
                        false
                    ),
                ];
                $tables[$section] = $table;
            } else {
                $table = $tables[$section];
            }
            $checkboxhtml = html_writer::checkbox(
                "mumie_multi_edit_task",
                $module->id,
                false,
                '',
                [
                    "section" => $section,
                ]);
            $table->data[] = [$module->name, $checkboxhtml];
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
     * Preselect the working period.
     *
     * This function is called, when a MUMIE task is edited.
     * @param stdClass $data instance of MUMIE task, that is being edited
     * @return void
     */
    public function set_data($data): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/locallib.php');
        $mform = &$this->_form;
        $this->set_data_general($data, $mform);
        // This option must not be changed to avoid messing with grades in the database.
        $mform->updateElementAttr("mumie_complete_course", ["disabled" => "disabled"]);
        $this->set_data_grade($data, $mform);
        parent::set_data($data);
    }

    /**
     * Sets data to grade elements
     *
     * @param stdClass $data instance of MUMIE task, that is being edited
     * @param MoodleQuickForm $mform
     * @return void
     */
    private function set_data_grade($data, $mform): void {
        // Preselect the correct duration option. An empty task as no duedate or timelimit property.
        if (isset($data->duedate) && $data->duedate > 0) {
            $mform->setDefault('duration_selector', 'duedate');
        } else if (isset($data->duedate) && $data->timelimit > 0) {
            $mform->setDefault('duration_selector', 'timelimit');
        } else {
            $mform->setDefault('duration_selector', 'unlimited');
        }
        $mform->disabledIf('duration_selector', null);
    }

    /**
     * Sets data to general server elements
     *
     * @param stdClass $data instance of MUMIE task, that is being edited
     * @param MoodleQuickForm $mform
     * @return void
     */
    private function set_data_general_server($data, $mform): void {
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
    }

    /**
     * Sets data to general elements, includes set_data_general_server.
     *
     * @param stdClass $data instance of MUMIE task, that is being edited
     * @param MoodleQuickForm $mform
     * @return void
     */
    private function set_data_general($data, $mform): void {
        global $COURSE, $DB;
        // Decisions about gradepools are final. Don't preselect an option is the decision is still pending!
        if (!mod_mumie\locallib::course_contains_mumie_tasks($COURSE->id)) {
            $data->privategradepool = get_config('auth_mumie', 'defaultgradepool');
        } else {
            if (!isset($data->privategradepool)) {
                $data->privategradepool = array_values(
                    $DB->get_records(MUMIE_TASK_TABLE, ["course" => $COURSE->id])
                )[0]->privategradepool ?? -1;
            }
        }

        $this->set_data_general_server($data, $mform);

    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data): bool {
        $completionpasselement = $this->get_completion_rule_element_name('completionpass');
        return !empty($data[$completionpasselement]);
    }


    /**
     * The decision regarding gradepools is final. We need to know whether we should disable the selection boxes.
     *
     * @param int $courseid id of the current course
     * @return bool whether to disable gradepool selection
     */
    private function disable_gradepool_selection($courseid): bool {
        global $DB;
        $records = $DB->get_records(MUMIE_TASK_TABLE, ["course" => $courseid]);
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
    private function add_info_box($message): void {
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
        $validservers = [];
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
