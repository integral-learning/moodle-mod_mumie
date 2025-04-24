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
 * MUMIE Task module test data generator class
 *
 * @package mod_mumie
 * @copyright  2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mumie_generator extends testing_module_generator {
    /**
     * Create a mumie instance.
     *
     * @param array|stdClass $record data for module being generated. Requires 'course' key (an id or the full object). Also can have any fields from add module form.
     * @param null|array $options general options for course module. Since 2.6 it is possible to omit this argument by merging options into $record
     * @return stdClass mumie instance
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;
        $defaultmumiesettings = [
            'grade' => 100,
            'timecreated' => time(),
            'timemodified' => time(),
            'taskurl' => '',
            'launchcontainer' => 1,
            'mumie_course' => '',
            'lastsync' => 0,
            'points' => 100,
            'completionpass' => 0,
            'use_hashed_id' => 1,
            'duedate' => 0,
            'privategradepool' => 1,
            'isgraded' => 1,
            'timelimit' => 0,
            'duration_selector' => 'unlimited',
        ];

        foreach ($defaultmumiesettings as $property => $value) {
            if (!isset($record->{$property})) {
                $record->{$property} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
