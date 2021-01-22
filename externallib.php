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
 * redirect the user to the SSO plugin auth_mumie to view MUMIE content
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

class mod_mumie_external extends external_api {
    /**
     * Describes the parameters for submit_mumieduedate_form webservice.
     * @return external_function_parameters
     */
    public static function submit_mumieduedate_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for action'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the mumie duedate form, encoded as a json array'),
            )
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

        $data = array();
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
        $mform = new duedate_form(null, array('editoroptions' => $editoroptions), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        if ($validateddata) {
            $duedate = mod_mumie\mumie_duedate_extension::from_object((object) $validateddata);
            $duedate->upsert();
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
}