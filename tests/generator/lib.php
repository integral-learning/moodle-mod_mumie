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

defined('MOODLE_INTERNAL') || die();

class mod_mumie_generator extends testing_module_generator {
    public function create_instance($record = null, array $options = null) {
        global $CFG;

        $record = (object)(array)$record;

        $defaultmumiesettings = array(
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
            'isgraded' => 1
        );

        foreach ($defaultmumiesettings as $property => $value) {
            if (!isset($record->{$property})) {
                $record->{$property} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    public function create_login($mumie, $userid) {

    }
}
