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
 * Decode raw mod_form POST fields into a flat $data object.
 *
 * @package mod_mumie
 * @copyright  2017-2026 integral-learning GmbH (https://www.integral-learning.de/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

use stdClass;

/**
 * Decode raw mod_form POST fields into a flat $data object.
 *
 * Mimics the per-element conversion that moodleform::get_data() does
 * (date_selector → timestamp, duration → seconds), without instantiating
 * the form. Used by the multi-task creation external function so it can
 * share validation/postprocessing/persistence with the single-task path.
 */
class form_field_decoder {
    /**
     * Form-internals and form-side helper fields that are never instance data.
     * Submission protocol markers, hidden JSON holders, display-only labels, config echoes.
     */
    private const FORM_MECHANIC_FIELDS = [
        '_qf__mod_mumie_mod_form',
        'sesskey',
        'submitbutton',
        'cancel',
        'mumie_multi_tasks',
        'mumie_server_structure',
        'task_display_element',
        'mumie_org',
    ];

    /**
     * Real form fields that the multi-create flow must NOT carry into per-task $instancedata.
     *
     * mumie_selected_tasks / mumie_selected_task_properties drive the single-task "apply
     * settings to other MUMIE tasks" sidebar. If they leak into a per-task moduleinfo,
     * mumie_add_instance → mumie_update_multiple_tasks would cascade the per-task settings
     * onto the listed tasks once per created instance.
     */
    private const MULTI_CREATE_SUPPRESSED_FIELDS = [
        'mumie_selected_tasks',
        'mumie_selected_task_properties',
    ];

    /**
     * Build a $data stdClass from raw form fields (post-parse_str).
     *
     * Scalar fields are copied as-is. The date_selector group `duedate` is converted to a
     * unix timestamp and the duration group `timelimit` is converted to seconds. Other array
     * fields (e.g. tags, competencies — multi-select autocompletes) are passed through, with
     * the '_qf__force_multiselect_submission' sentinel normalised to an empty array, matching
     * what MoodleQuickForm_autocomplete::exportValue() does for empty multi-selects.
     *
     * @param array $formfields Result of parse_str on the serialized form.
     * @return stdClass
     */
    public static function from_form_fields(array $formfields): stdClass {
        $excluded = array_merge(self::FORM_MECHANIC_FIELDS, self::MULTI_CREATE_SUPPRESSED_FIELDS);
        $data = new stdClass();
        foreach ($formfields as $key => $value) {
            if (in_array($key, $excluded, true)) {
                continue;
            }
            if ($key === 'duedate' || $key === 'timelimit') {
                continue;
            }
            $data->$key = self::normalize_multiselect_sentinel($value);
        }
        $data->duedate = self::decode_duedate($formfields);
        $data->timelimit = self::decode_timelimit($formfields);
        return $data;
    }

    /**
     * Mimic what MoodleQuickForm_autocomplete::exportValue() does for empty multi-selects:
     * the form element emits '_qf__force_multiselect_submission' as a marker that the field
     * was rendered but nothing was picked. moodleform normalizes that to an empty array
     * before validation; we have to do the same since we parse the raw POST.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function normalize_multiselect_sentinel($value) {
        if ($value === '_qf__force_multiselect_submission') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_filter(
                $value,
                static fn($v) => $v !== '_qf__force_multiselect_submission'
            ));
        }
        return $value;
    }

    /**
     * Convert a date_selector group `duedate[year|month|day|hour|minute]` to a unix timestamp.
     *
     * @param array $formfields
     * @return int
     */
    private static function decode_duedate(array $formfields): int {
        $duedate = $formfields['duedate'] ?? null;
        if (!is_array($duedate) || empty($duedate['year'])) {
            return 0;
        }
        return make_timestamp(
            $duedate['year'],
            $duedate['month'],
            $duedate['day'],
            $duedate['hour'] ?? 0,
            $duedate['minute'] ?? 0
        );
    }

    /**
     * Convert a duration group `timelimit[number|timeunit]` to seconds.
     *
     * @param array $formfields
     * @return int
     */
    private static function decode_timelimit(array $formfields): int {
        $timelimit = $formfields['timelimit'] ?? null;
        if (!is_array($timelimit) || empty($timelimit['timeunit'])) {
            return 0;
        }
        return (int)$timelimit['number'] * (int)$timelimit['timeunit'];
    }
}
