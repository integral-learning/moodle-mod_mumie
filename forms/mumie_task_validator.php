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
 * This file contains a validator used to check mod_form for MUMIE Tasks
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

/**
 * This class is a validator used to check mod_form for MUMIE Tasks
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_task_validator {
    /**
     * Get validation errors for POST Data. Will return an empty array, if no errors were found.
     * @param array     $data
     * @param \stdClass $current
     * @return array
     * @throws \coding_exception
     */
    public static function get_errors(array $data, \stdClass $current) : array {
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

        if (self::has_duedate($data) && time() - $data['duedate'] > 0) {
            $errors['duedate'] = get_string('mumie_form_due_date_must_be_future', 'mod_mumie');
        }

        if (array_key_exists('instance', $data) && $data['instance']) {
            $mumie = locallib::get_mumie_task($data['instance']);
            if ($mumie && $mumie->isgraded !== $data['isgraded']) {
                $errors['prb_selector_btn'] = get_string('mumie_form_cant_change_isgraded', 'mod_mumie');
            }
        }

        if (self::is_worksheet($data)
            && self::is_correction_trigger_after_deadline($data['worksheet'])
            && (!self::has_duedate($data) && !self::has_timelimit($data))) {
            $errors['duedate'] = get_string('mumie_form_deadline_required_for_trigger_after_deadline', 'mod_mumie');
        }

        if (self::is_worksheet($data)
            && !self::is_correction_trigger_after_deadline($data['worksheet'])
            && (self::has_duedate($data) || self::has_timelimit($data))) {
            $errors['duedate'] = get_string('mumie_form_deadline_prohibited_for_worksheet_without_trigger_after_deadline', 'mod_mumie');
        }

        return $errors;
    }

    /**
     * Check whether a deadline was selected
     * @param array $data POST data
     * @return bool
     */
    private static function has_duedate(array $data) : bool {
        return $data['duedate'];
    }

    /**
     * Check whether a deadline was selected
     * @param array $data POST data
     * @return bool
     */
    private static function has_timelimit(array $data) : bool {
        return $data['timelimit'];
    }

    /**
     * Check whether the resulting MUMIE Task is a worksheet
     *
     * @param array $data POST data
     * @return bool
     */
    private static function is_worksheet(array $data) : bool {
        return array_key_exists('worksheet', $data) && !empty($data['worksheet']);
    }

    /**
     * Check whether the worksheet is configured to be corrected after a given deadline is reached
     *
     * @param string $worksheet The json string representing the worksheet configuration
     * @return bool
     */
    private static function is_correction_trigger_after_deadline(string $worksheet) : bool {
        return json_decode($worksheet, true)['configuration']['correction']['correctorType'] == "AFTER_DEADLINE";
    }
}
