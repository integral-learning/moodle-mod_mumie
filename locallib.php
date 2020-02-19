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
 * Libary of internal functions used in mod_mumie
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/mumie/lib.php');

global $CFG;
global $DB;

define("MUMIE_LAUNCH_CONTAINER_WINDOW", 0);
define("MUMIE_LAUNCH_CONTAINER_EMBEDDED", 1);

/**
 * Libary of internal functions used in mod_mumie
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locallib {

    /**
     * Get instance of mumie task with its id
     * @param int $id id of the mumie task
     * @return stdClass instance of mumie task
     */
    public static function get_mumie_task($id) {
        global $DB;
        return $DB->get_record(MUMIE_TASK_TABLE_NAME, array('id' => $id));
    }

    /**
     * Check if there are any MUMIE Tasks in the given course.
     *
     * @param int $courseid The course to check
     * @return bool True, if there are no MUMIE Tasks in the course yet
     */
    public static function course_contains_mumie_tasks($courseid) {
        global $DB;
        return count($DB->get_records(MUMIE_TASK_TABLE, array("course" => $courseid))) < 1;
    }

    /**
     * The function is called whenever a MUMIE task is updated or created.
     * If a pending decision regarding gradepools was made, we need to update all other MUMIE Tasks in this course as well.
     * @param stcClass $mumietask The update we are processing
     */
    public static function update_pending_gradepool($mumietask) {
        global $DB;
        $update = false;
        if (!isset($mumie->id)) {
            $update = true;
        } else {
            $oldrecord = $DB->get_record(MUMIE_TASK_TABLE, array('id' => $mumietask->id));
            if ($oldrecord->privategradepool != $mumietask->privategradepool) {
                $update = true;
            }
        }

        if ($update) {
            $tasks = $DB->get_records(MUMIE_TASK_TABLE, array("course" => $mumietask->course));
            foreach ($tasks as $task) {
                if (!isset($task->privategradepool)) {
                    $task->privategradepool = $mumietask->privategradepool;
                    $DB->update_record(MUMIE_TASK_TABLE, $task);
                }
            }
        }
    }

    /**
     * Get a default name for the uploaded MumieTask, if available.
     * The droped MUMIE task's name is generated and does not look pretty. Search its server for a better name.
     * @param stdClass $uploadedtask
     * @return string
     */
    public static function get_default_name($uploadedtask) {
        global $CFG;
        require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');
        $server = \auth_mumie\mumie_server::get_by_urlprefix($uploadedtask->server);
        $server->load_structure();
        $course = $server->get_course_by_coursefile($uploadedtask->path_to_coursefile);
        $task = $course->get_task_by_link($uploadedtask->link);
        if (is_null($task)) {
            return;
        }
        return $task->get_headline_by_language($uploadedtask->language);
    }
}
