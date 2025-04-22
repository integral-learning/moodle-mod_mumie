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
    public static function get_errors(array $data, \stdClass $current): array {
        $errors = [];
        $errors = array_merge($errors, self::check_required($data));
        $errors = array_merge($errors, self::check_completion($data, $current));
        $errors = array_merge($errors, self::check_isgraded($data));
        $errors = array_merge($errors, self::check_duration($data));
        $errors = array_merge($errors, self::check_worksheet($data));
        return $errors;
    }

    private static function check_required(array $data): array {
            $errors = [];
            if(empty($data["mumie_missing_config"])) {
                $errors["server"] = get_string('mumie_form_required', 'mod_mumie');
            }
            if (empty($data["taskurl"])) {
                $errors["prb_selector_btn"] = get_string('mumie_form_required', 'mod_mumie');
            }
            foreach (['server', 'mumie_course'] as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = get_string('mumie_form_required', 'mod_mumie');
                }
            }
            return $errors;
        }

        private static function check_completion(array $data, \stdClass $current): array {
            $errors = [];
            if (($data['completion'] ?? null) == COMPLETION_TRACKING_AUTOMATIC) {
                $completionpass = $data['completionpass'] ?? $current->completionpass;
                $gradepass = grade_floatval($data['gradepass'] ?? 0);

                if ($completionpass && $gradepass == 0) {
                    $key = isset($data['completionpass']) ? 'completionpassgroup' : 'gradepass';
                    $stringkey = isset($data['completionpass']) ? 'gradetopassnotset' : 'gradetopassmustbeset';
                    $errors[$key] = get_string($stringkey, 'mumie');
                }
            }
            return $errors;

        }

        private static function check_isgraded(array $data): array {
            $errors = [];
            if (!empty($data['instance'])) {
                $mumie = locallib::get_mumie_task($data['instance']);
                if ($mumie && $mumie->isgraded !== $data['isgraded']) {
                    $errors['prb_selector_btn'] = get_string('mumie_form_cant_change_isgraded', 'mod_mumie');
                }
            }
            return $errors;
        }

        private static function check_duration(array $data): array {
            $errors = [];
            $workingperiod = $data['duration_selector'];
            if ($workingperiod !== 'duedate') {
                $data['duedate'] = 0;
            }
            if ($workingperiod !== 'timelimit') {
                $data['timelimit'] = 0;
            }
            if ($workingperiod === 'duedate' && self::has_duedate($data) && time() > $data['duedate']) {
                $errors['duedate'] = get_string('mumie_form_due_date_must_be_future', 'mod_mumie');
            }
            return $errors;
        }
        private static function check_worksheet(array $data): array {
            $errors = [];
            $isworksheet = self::is_worksheet($data);
            $triggerafterdeadline = $isworksheet && self::is_correction_trigger_after_deadline($data['worksheet']);
            $hasdeadline = self::has_duedate($data) || self::has_timelimit($data);

            if ($triggerafterdeadline && !$hasdeadline) {
                $errors['duration_selector'] = get_string('mumie_form_deadline_required_for_trigger_after_deadline', 'mod_mumie');
            } else if ($isworksheet && !$triggerafterdeadline && $hasdeadline) {
                $errors['duration_selector'] = get_string('mumie_form_deadline_prohibited_for_worksheet_without_trigger_after_deadline', 'mod_mumie');
            }
            return $errors;
        }

    /**
     * Check whether a duedate was set
     * @param array $data POST data
     * @return bool
     */
    private static function has_duedate(array $data): bool {
        return $data['duedate'] > 0;
    }

    /**
     * Check whether a timelimit was set
     * @param array $data POST data
     * @return bool
     */
    private static function has_timelimit(array $data): bool {
        return $data['timelimit'] > 0;
    }

    /**
     * Check whether the resulting MUMIE Task is a worksheet
     *
     * @param array $data POST data
     * @return bool
     */
    private static function is_worksheet(array $data): bool {
        return array_key_exists('worksheet', $data) && !empty($data['worksheet']);
    }

    /**
     * Check whether the worksheet is configured to be corrected after a given deadline is reached
     *
     * @param string $worksheet The json string representing the worksheet configuration
     * @return bool
     */
    private static function is_correction_trigger_after_deadline(string $worksheet): bool {
        return json_decode($worksheet, true)['configuration']['correction']['correctorType'] == "AFTER_DEADLINE";
    }
}
