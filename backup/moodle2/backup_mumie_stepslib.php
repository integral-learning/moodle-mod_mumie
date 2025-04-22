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
 * Defines backup_mumie_activity_task class
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mumie_structure_step extends backup_activity_structure_step {

    /**
     * Defines structure of MUMIE tasks in the backup file
     */
    protected function define_structure() {
        $mumie = new backup_nested_element('mumie', ['id'], [
            'name', 'intro', 'introformat', 'timecreated',
            'timemodified', 'taskurl', 'launchcontainer',
            'mumie_course', 'language', 'server', 'mumie_coursefile',
            'lastsync', 'points', 'completionpass', 'privategradepool', 'duedate', 'isgraded']);

        $serverconfig = new backup_nested_element("serverconfig", ['id'], ['name', 'url_prefix']);
        $mumie->add_child($serverconfig);
        $serverconfig->set_source_sql('
            SELECT *
                FROM {auth_mumie_servers}
            WHERE url_prefix =
                (SELECT server
                    FROM {mumie}
                WHERE id = ?
                )', [backup::VAR_ACTIVITYID]);

        $mumie->set_source_table('mumie', ['id' => backup::VAR_ACTIVITYID]);

        return $this->prepare_activity_structure($mumie);
    }
}
