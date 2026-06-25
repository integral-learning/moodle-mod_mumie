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

use mod_mumie\locallib;
use mod_mumie\form_field_decoder;
use mod_mumie\mumie_task_validator;

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
     * Describes the parameters for create_multiple_mumie_tasks webservice.
     * @return external_function_parameters
     */
    public static function create_multiple_mumie_tasks_parameters() {
        return new external_function_parameters([
            'contextid'     => new external_value(PARAM_INT, 'Context id of the course'),
            'section'       => new external_value(PARAM_INT, 'Section number to add tasks to'),
            'tasksformdata' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'URL-encoded form POST for one task'),
                'One serialized form POST per task to create'
            ),
        ]);
    }

    /**
     * Create multiple MUMIE tasks at once.
     *
     * Each entry of tasksformdata is the same payload the form would submit
     * for a single task. The picker-payload → form-field mapping lives in JS
     * (applyPickerPayloadToForm) and is shared with the single-task path, so
     * adding a new picker field doesn't require parallel PHP changes.
     *
     * @param int $contextid Course context id
     * @param int $section Section number
     * @param string[] $tasksformdata One URL-encoded form POST per task
     * @return array Array of created course module ids
     */
    public static function create_multiple_mumie_tasks($contextid, $section, $tasksformdata) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/mumie/lib.php');
        require_once($CFG->dirroot . '/mod/mumie/locallib.php');
        require_once($CFG->dirroot . '/mod/mumie/forms/form_field_decoder.php');
        require_once($CFG->dirroot . '/mod/mumie/forms/mumie_task_validator.php');

        $params = self::validate_parameters(
            self::create_multiple_mumie_tasks_parameters(),
            [
                'contextid'     => $contextid,
                'section'       => $section,
                'tasksformdata' => $tasksformdata,
            ]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        require_capability('mod/mumie:addinstance', $context);

        if (empty($params['tasksformdata'])) {
            throw new invalid_parameter_exception('tasksformdata must be a non-empty array');
        }
        if (count($params['tasksformdata']) > 50) {
            throw new invalid_parameter_exception('Cannot create more than 50 tasks at once');
        }

        $course = $DB->get_record('course', ['id' => $context->instanceid], '*', MUST_EXIST);
        $mumiemodule = $DB->get_record('modules', ['name' => 'mumie'], '*', MUST_EXIST);

        $createdids = [];
        foreach ($params['tasksformdata'] as $taskpost) {
            $formfields = [];
            parse_str($taskpost, $formfields);

            $instancedata = form_field_decoder::from_form_fields($formfields);
            locallib::clean_up_duration_values($instancedata);

            $instancedata->modulename  = 'mumie';
            $instancedata->module      = $mumiemodule->id;
            $instancedata->course      = $course->id;
            $instancedata->section     = $params['section'];
            $instancedata->visible     = 1;
            $instancedata->intro       = '';
            $instancedata->introformat = FORMAT_HTML;
            if (isset($instancedata->points)) {
                $instancedata->grade = (int)$instancedata->points;
            }

            $errors = mumie_task_validator::get_errors((array)$instancedata, new stdClass());
            if (!empty($errors)) {
                throw new moodle_exception('mumie_multi_task_validation_error', 'mod_mumie', '', reset($errors));
            }

            $created = add_moduleinfo($instancedata, $course);
            $createdids[] = $created->coursemodule;
        }

        return $createdids;
    }

    /**
     * Describes the return value for create_multiple_mumie_tasks webservice.
     * @return external_multiple_structure
     */
    public static function create_multiple_mumie_tasks_returns() {
        return new external_multiple_structure(
            new external_value(PARAM_INT, 'course module id')
        );
    }
}
