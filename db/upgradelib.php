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
 * Helper functions used by db/upgrade.php.
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backfill privategradepool for existing MUMIE Tasks.
 *
 * Called during update to 2020011702. In older versions all grades were shared between courses,
 * so pre-existing tasks need privategradepool set to 0 to keep that behaviour.
 *
 * @return void
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

/**
 * Populate the mumie_task table from the task-only columns still living on the mumie table.
 *
 * Called during upgrade to 2026070700, after mumie_task has been created and before the //todo update version before release
 * task-only columns are dropped from mumie. NOT EXISTS-guarded so a re-run does not create
 * duplicate mumie_task rows.
 *
 * @return void
 */
function mumie_migrate_task_fields_to_task_table() {
    global $DB;
    $DB->execute("
        INSERT INTO {mumie_task}
            (id, taskurl, launchcontainer, mumie_course, language, server, mumie_coursefile,
             lastsync, points, use_hashed_id, duedate, timelimit, privategradepool, isgraded, worksheet)
        SELECT m.id, m.taskurl, m.launchcontainer, m.mumie_course, m.language, m.server, m.mumie_coursefile,
               m.lastsync, m.points, m.use_hashed_id, m.duedate, m.timelimit, m.privategradepool, m.isgraded, m.worksheet
        FROM {mumie} m
        WHERE m.type = 'task'
          AND NOT EXISTS (SELECT 1 FROM {mumie_task} t WHERE t.id = m.id)
    ");
}
