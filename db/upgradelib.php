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
 * A library of functions used in the upgrade process.
 * This function is called during update to 2020011701.
 * In older versions all grades where shared between courses. We need to enable this for
 * all existing MUMIE Tasks.
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function mumie_set_privategradepool_default() {
    global $DB;
    $tasks = $DB->get_records('mumie', []);

    foreach ($tasks as $task) {
        if (!isset($task->privategradepool)) {
            $task->privategradepool = 0;
            $DB->update_record('mumie', $task);
        }
    }
}
