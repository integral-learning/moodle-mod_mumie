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
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot . '/course/moodleform_mod.php');
require_once ($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');

/**
 * This moodle form is used to insert or update MumieServer in the database
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mumie_mod_form extends moodleform_mod {
    /**
     * Define fields and default values for the mumie server form
     * @return void
     */
    public function definition() {
        global $PAGE, $OUTPUT, $COURSE, $CFG, $USER;

        $mform = &$this->_form;

        $coursesforserver = auth_mumie\mumie_server::get_all_servers_with_structure();
        $serveroptions = array();
        $courseoptions = array();
        $problemoptions = array();
        $languageoptions = array();

        self::populate_options($serveroptions, $courseoptions, $problemoptions, $languageoptions);

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('mumie_form_activity_header', 'mod_mumie'));

        $this->standard_intro_elements(get_string('mumieintro', 'mumie'));
        $mform->setAdvanced('introeditor');

        $mform->addElement("text", "name", get_string("mumie_form_activity_name", "mod_mumie"));
        $mform->setType('name', PARAM_TEXT);

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

        $mform->addElement("select", "mumie_course", get_string('mumie_form_activity_course', "mod_mumie"), $courseoptions);

        $mform->addElement("select", "language", get_string('mumie_form_activity_language', "mod_mumie"), $languageoptions);
        $mform->addHelpButton("language", 'mumie_form_activity_language', 'mumie');

        $mform->addElement("select", "taskurl", get_string('mumie_form_activity_problem', "mod_mumie"), $problemoptions);
        $mform->addHelpButton("taskurl", 'mumie_form_activity_problem', 'mumie');

        $mform->addElement('html', '<div id="mumie_filter_section" class="form-group row  fitem" hidden>
        <div class="col-md-3"></div><span id="mumie_filter_header" class="mumie-collapsable felement col-md-9">
        <i class="fa fa-caret-down mumie-icon"></i>Filter MUMIE problems</span>
        <div class="col-md-3"></div><div id="mumie_filter_wrapper" hidden class="col-md-9  felement">
        </div></div>');

        $launchoptions = array();
        $launchoptions[MUMIE_LAUNCH_CONTAINER_EMBEDDED] = get_string("mumie_form_activity_container_embedded", "mod_mumie");
        $launchoptions[MUMIE_LAUNCH_CONTAINER_WINDOW] = get_string("mumie_form_activity_container_window", "mod_mumie");

        $mform->addElement("select", "launchcontainer", get_string('mumie_form_activity_container', "mod_mumie"), $launchoptions);
        $mform->addHelpButton("launchcontainer", "mumie_form_activity_container", "mumie");

        $mform->addElement("hidden", "mumie_coursefile", "");
        $mform->setType("mumie_coursefile", PARAM_TEXT);

        $mform->addElement("hidden", "mumie_missing_config", null);
        $mform->setType("mumie_missing_config", PARAM_TEXT);

        // Add standard course module grading elements.
        $this->standard_grading_coursemodule_elements();

        $mform->removeElement('grade');
        $mform->addElement("text", "points", get_string("mumie_form_points", "mod_mumie"));
        $mform->setDefault("points", 100);
        $mform->setType("points", PARAM_INT);
        $mform->addHelpButton("points", "mumie_form_points", "mumie");

        $mform->addElement('date_time_selector', 'duedate', get_string("mumie_due_date", "mod_mumie"), array('optional' => true));
        $mform->addHelpButton("duedate", 'mumie_form_due_date', 'mumie');

        $radioarray = array();
        $isfirsttask = $this->is_first_task_of_course($COURSE->id);
        if (!$isfirsttask) {
            $attributes = array('disabled' => '');
        } else {
            $attributes = array();
        }
        $radioarray[] = $mform->createElement('radio', 'privategradepool', '', get_string('yes'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'privategradepool', '', get_string('no'), 1, $attributes);
        $mform->addGroup($radioarray, 'privategradepool', get_string('mumie_form_grade_pool', 'mod_mumie'), array(' '), false);
        $mform->addHelpButton('privategradepool', 'mumie_form_grade_pool', 'mumie');
        $mform->addElement('html', '<div class="form-group row  fitem ">'
            . '<div class="col-md-3"></div><div class="col-md-9">'
            . get_string('mumie_form_grade_pool_warning', 'mod_mumie')
            . '</div></div>');
        if ($isfirsttask) {
            $mform->addRule('privategradepool', get_string('mumie_form_required', 'mod_mumie'), 'required', null, 'client');
        }

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $mform->setAdvanced('cmidnumber');

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
        $context = context_course::instance($COURSE->id);
        $PAGE->requires->js_call_amd('mod_mumie/mod_form', 'init', array(json_encode($context->id)));
    }

    /**
     * Valdiate the form data
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

        if (!isset($data["taskurl"]) && !isset($data["mumie_missing_config"])) {
            $errors["taskurl"] = get_string('mumie_form_required', 'mod_mumie');
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

        return $errors;
    }

    /**
     * For the form to work, we need to populate all option fields before javascript can modify them.
     * If a option is not defined before js is started, it won't be saved by moodle.
     *
     * @param stdClass $serveroptions pointer to the array containing all available servers
     * @param stdClass $courseoptions pointer to the array containing all available courses
     * @param stdClass $problemoptions pointer to the array containing all available tasks
     * @param stdClass $languageoptions pointer to the array containing all available languages
     * @return void
     */
    private function populate_options(&$serveroptions, &$courseoptions, &$problemoptions, &$languageoptions) {

        $servers = auth_mumie\mumie_server::get_all_servers_with_structure();

        foreach ($servers as $server) {
            $serveroptions[$server->get_urlprefix()] = $server->get_name();
            self::populate_course_options(
                $server->get_courses(),
                $courseoptions,
                $problemoptions,
                $languageoptions
            );
        }
    }

    /**
     * Populate course option list and then populate task options for all given courses of a MUMIE server
     *
     * @param array $courses array containing a list of courses
     * @param array $courseoptions pointer to the array containing all available courses
     * @param array $problemoptions pointer to the array containing all available tasks
     * @param array $languageoptions pointer to the array containing all available languages
     * @return void
     */
    private function populate_course_options($courses, &$courseoptions, &$problemoptions, &$languageoptions) {
        foreach ($courses as $course) {
            $courseoptions[$course->get_name()] = $course->get_name();
            self::populate_problem_options($course, $problemoptions, $languageoptions);
        }
    }

    /**
     * Populate task and language option list for a given course
     *
     * @param stdClass $course single isntance of MUMIE course containing a list of tasks
     * @param array $problemoptions pointer to the array containing all available tasks
     * @param array $languageoptions pointer to the array containing all available languages
     * @return void
     */
    private function populate_problem_options($course, &$problemoptions, &$languageoptions) {
        foreach ($course->get_tasks() as $problem) {
            $link = $problem->get_link();

            foreach ($problem->get_headline() as $headline) {
                $languagelink = $link . '?lang=' . $headline->language;
                $languageoptions[$headline->language] = $headline->language;
                $problemoptions[$languagelink] = $headline->name;
            }
        }
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
        $group[] = $mform->createElement('advcheckbox', 'completionpass', null, get_string('completionpass', 'mumie'),
            array('group' => 'cpass'));
        $mform->disabledIf('completionpass', 'completionusegrade', 'notchecked');
        $mform->addGroup($group, 'completionpassgroup', get_string('completionpass', 'mumie'), ' &nbsp; ', false);
        $mform->addHelpButton('completionpassgroup', 'completionpass', 'mumie');
        $items[] = 'completionpassgroup';
        return $items;
    }

    /**
     * Set a hidden value, if the MUMIE server configuration, which has been used in this MUMIE task, has been deleted.
     * Javascript uses this information to display an error message.
     *
     * This function is called, when a MUMIE task is edited.
     * @param stdClass $data instance of MUMIE task, that is being edited
     * @return void
     */
    public function set_data($data) {
        global $COURSE, $DB;
        if ($this->is_first_task_of_course($COURSE->id)) {
            $data->privategradepool = -1;
        } else if (!isset($data->privategradepool)) {
            $data->privategradepool = array_values(
                $DB->get_records(MUMIE_TASK_TABLE, array("course" => $COURSE->id))
            )[0]->privategradepool;
        }

        $filter = array_filter(auth_mumie\mumie_server::get_all_servers(), function ($server) use ($data) {
            if (!isset($data->server)) {
                return false;
            }

            return $server->get_urlprefix() === $data->server;
        });

        if (count($filter) < 1 && isset($data->server)) {
            $data->mumie_missing_config = $data->server;
        }

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
     * Check if there are already any MUMIE Tasks in the given course.
     *
     * @param int $courseid The course to check
     * @return bool True, if there are no MUMIE Tasks in the course yet
     */
    private function is_first_task_of_course($courseid) {
        global $DB;
        return count($DB->get_records(MUMIE_TASK_TABLE, array("course" => $courseid))) < 1;
    }
}
