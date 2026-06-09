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
 * External library for mod_mumie.
 *
 * @package mod_mumie
 * @copyright  2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * External library for mod_mumie.
 *
 * @package mod_mumie
 * @copyright  2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mumie_external extends external_api {
    /**
     * Describes the parameters for submit_mumieduedate_form webservice.
     * @return external_function_parameters
     */
    public static function submit_mumieduedate_form_parameters() {
        return new external_function_parameters(
            [
                'contextid' => new external_value(PARAM_INT, 'The context id for action'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the mumie duedate form, encoded as a json array'),
            ]
        );
    }

    /**
     * Submit the mumieduedate form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new mumieduedate id.
     */
    public static function submit_mumieduedate_form($contextid, $jsonformdata) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/mumie/lib.php');
        require_once($CFG->dirroot . '/mod/mumie/forms/duedate_form.php');
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_calendar_service/mumie_individual_calendar_service.php');
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::submit_mumieduedate_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        require_capability('mod/mumie:grantduedateextension', $context);

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = [];
        parse_str($serialiseddata, $data);

        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'trust' => false,
            'context' => $context,
            'noclean' => true,
            'subdirs' => false,
        ];
        $mumieduedate = new stdClass();
        $mumieduedate = file_prepare_standard_editor(
            $mumieduedate,
            'description',
            $editoroptions,
            $context,
            'mumie',
            'description',
            null
        );

        // The last param is the ajax submitted data.
        $mform = new duedate_form(null, ['editoroptions' => $editoroptions], 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        if ($validateddata) {
            $duedate = mod_mumie\mumie_duedate_extension::from_object((object) $validateddata);
            $duedate->upsert();
            $calendarservice = new mod_mumie\mumie_individual_calendar_service(
                $duedate->get_mumie(),
                $duedate->get_userid()
            );
            $calendarservice->update();
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'mumieduedate');
        }
        return $duedate->get_id();
    }

    /**
     * Describes the parameters for submit_mumieduedate_form webservice.
     * @return external_function_parameters
     */
    public static function submit_mumieduedate_form_returns() {
        return new external_value(PARAM_INT, 'duedate id');
    }

    /**
     * Describes the parameters for create_multiple_tasks webservice.
     * @return external_function_parameters
     */
    public static function create_multiple_tasks_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id of the course'),
            'section'   => new external_value(PARAM_INT, 'Section number to add tasks to'),
            'tasks'     => new external_value(PARAM_RAW, 'JSON array of task objects'),
            'formdata'  => new external_value(PARAM_RAW, 'URL-encoded form data for settings'),
        ]);
    }

    /**
     * Create multiple MUMIE tasks at once with shared settings.
     *
     * @param int $contextid Course context id
     * @param int $section Section number
     * @param string $tasks JSON array of task objects
     * @param string $formdata URL-encoded serialized form data
     * @return array Array of created course module ids
     */
    public static function create_multiple_tasks($contextid, $section, $tasks, $formdata) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/mumie/lib.php');
        require_once($CFG->dirroot . '/mod/mumie/locallib.php');

        $params = self::validate_parameters(
            self::create_multiple_tasks_parameters(),
            [
                'contextid' => $contextid,
                'section'   => $section,
                'tasks'     => $tasks,
                'formdata'  => $formdata,
            ]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        require_capability('mod/mumie:addinstance', $context);

        $course = $DB->get_record('course', ['id' => $context->instanceid], '*', MUST_EXIST);
        $mumiemodule = $DB->get_record('modules', ['name' => 'mumie'], '*', MUST_EXIST);
        $taskarray = json_decode($params['tasks'], true);
        if (!is_array($taskarray) || empty($taskarray)) {
            throw new invalid_parameter_exception('tasks must be a non-empty JSON array');
        }
        if (count($taskarray) > 50) {
            throw new invalid_parameter_exception('Cannot create more than 50 tasks at once');
        }

        $formfields = [];
        parse_str($params['formdata'], $formfields);

        $points = (int)($formfields['points'] ?? 100);
        $gradepass = (float)($formfields['gradepass'] ?? 0);
        $gradecat = (int)($formfields['gradecat'] ?? 0);
        $launchcontainer = (int)($formfields['launchcontainer'] ?? 0);
        $privategradepool = (int)(isset($formfields['privategradepool']) ? $formfields['privategradepool'] : 0);
        $completionpass = (int)(isset($formfields['completionpass']) ? $formfields['completionpass'] : 0);
        $completionview = (int)($formfields['completionview'] ?? 0);
        $isgraded = (int)($formfields['isgraded'] ?? 1);
        $durationselector = $formfields['duration_selector'] ?? 'unlimited';

        $duedate = 0;
        if ($durationselector === 'duedate' && !empty($formfields['duedate']['year'])) {
            $duedate = make_timestamp(
                $formfields['duedate']['year'],
                $formfields['duedate']['month'],
                $formfields['duedate']['day'],
                $formfields['duedate']['hour'] ?? 0,
                $formfields['duedate']['minute'] ?? 0
            );
        }

        $timelimit = 0;
        if ($durationselector === 'timelimit' && !empty($formfields['timelimit']['timeunit'])) {
            $timelimit = (int)$formfields['timelimit']['number'] * (int)$formfields['timelimit']['timeunit'];
        }

        $createdids = [];
        foreach ($taskarray as $taskdata) {
            $moduleinfo = new stdClass();
            $moduleinfo->modulename    = 'mumie';
            $moduleinfo->module        = $mumiemodule->id;
            $moduleinfo->course        = $course->id;
            $moduleinfo->section       = $params['section'];
            $moduleinfo->visible       = 1;
            $moduleinfo->intro         = '';
            $moduleinfo->introformat   = FORMAT_HTML;

            $tasklink = clean_param($taskdata['link'] ?? '', PARAM_TEXT);
            $taskserver = clean_param($taskdata['server'] ?? '', PARAM_URL);
            if (empty($tasklink) || empty($taskserver)) {
                throw new invalid_parameter_exception('Each task must have a valid link and server');
            }

            $moduleinfo->name             = clean_param($taskdata['name'] ?? '', PARAM_TEXT);
            $moduleinfo->language         = clean_param($taskdata['language'] ?? '', PARAM_TEXT);
            $moduleinfo->taskurl          = $tasklink . '?lang=' . $moduleinfo->language;
            $moduleinfo->mumie_coursefile = clean_param($taskdata['path_to_coursefile'] ?? '', PARAM_TEXT);
            $moduleinfo->mumie_course     = clean_param($taskdata['course'] ?? '', PARAM_TEXT);
            $moduleinfo->server           = $taskserver;

            $moduleinfo->points           = $points;
            $moduleinfo->launchcontainer  = $launchcontainer;
            $moduleinfo->privategradepool = $privategradepool;
            $moduleinfo->completionpass   = $completionpass;
            $moduleinfo->duration_selector = $durationselector;
            $moduleinfo->duedate          = $duedate;
            $moduleinfo->timelimit        = $timelimit;
            $moduleinfo->isgraded         = $isgraded;

            $moduleinfo->grade            = $points;
            $moduleinfo->gradepass        = $gradepass;
            $moduleinfo->gradecat         = $gradecat;
            $moduleinfo->completionview   = $completionview;

            $moduleinfo = add_moduleinfo($moduleinfo, $course);
            $createdids[] = $moduleinfo->coursemodule;
        }

        return $createdids;
    }

    /**
     * Describes the return value for create_multiple_tasks webservice.
     * @return external_multiple_structure
     */
    public static function create_multiple_tasks_returns() {
        return new external_multiple_structure(
            new external_value(PARAM_INT, 'course module id')
        );
    }
}
