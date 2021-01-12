<?php

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
        require_once($CFG->dirroot . '/mod/mumie/mumieduedate_form.php');
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::submit_mumieduedate_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        require_capability('mod/mumie:grantextension', $context);

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