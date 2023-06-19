<?php
/**
 * This validator is used to check mod_form for MUMIE Tasks
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

class mumie_task_validator {
    public static function get_errors(array $data, $current) : array {
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
            $completionpass = $data['completionpass'] ?? $current->completionpass;

            // Show an error if require passing grade was selected and the grade to pass was set to 0.
            if ($completionpass && (empty($data['gradepass']) || grade_floatval($data['gradepass']) == 0)) {
                if (isset($data['completionpass'])) {
                    $errors['completionpassgroup'] = get_string('gradetopassnotset', 'mumie');
                } else {
                    $errors['gradepass'] = get_string('gradetopassmustbeset', 'mumie');
                }
            }
        }

        if (self::has_duedate($data)) {
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

        if (self::is_worksheet($data) && self::is_correction_trigger_after_deadline($data['worksheet']) && !self::has_duedate($data)) {
            $errors['duedate'] = get_string('mumie_form_deadline_required_for_trigger_after_deadline', 'mod_mumie');
        }
        return $errors;
    }

    private static function has_duedate($data) : bool {
        return $data['duedate'];
    }

    private static function is_worksheet(array $data) : bool {
        return array_key_exists('worksheet', $data);
    }

    private static function is_correction_trigger_after_deadline(string $worksheet) : bool {
        return json_decode($worksheet, true)['configuration']['correction']['correctorType'] == "AFTER_DEADLINE";
    }
}