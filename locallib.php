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
 * Library of internal functions used in mod_mumie
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
 * Library of internal functions used in mod_mumie
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
        return $DB->get_record(MUMIE_TASK_TABLE, array('id' => $id));
    }

    /**
     * Check if there are any MUMIE Tasks in the given course.
     *
     * @param int $courseid The course to check
     * @return bool True, if there are MUMIE Tasks in the course
     */
    public static function course_contains_mumie_tasks($courseid) {
        return count(self::get_mumie_tasks_by_course($courseid)) > 0;
    }

    /**
     * Get all MUMIE Tasks for a course
     *
     * @param int $courseid The course to check
     * @return array array of MUMIE Tasks
     */
    public static function get_mumie_tasks_by_course($courseid){
        global $DB;
        return $DB->get_records(MUMIE_TASK_TABLE, array("course" => $courseid));
    }

     /**
     * Get all MUMIE Modules for a course
     *
     * @param int $courseid The course to check
     * @return array array of MUMIE Modules
     */
    public static function get_mumie_modules_by_course($courseid){
        global $DB;
        $mumiemodule = $DB->get_record(MODULE_TABLE, array("name" => 'mumie'));
        return $DB->get_records(COURSE_MODULE_TABLE, array("module" => $mumiemodule->id,  "course" => $courseid));
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
     *
     * The dropped MUMIE Task's name is automatically generated and does not look pretty.
     * Search its server for a better name.
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

    /**
     * Remove all parameters from a given URL
     *
     * @param string $url
     * @return string $url
     */
    public static function remove_params_from_url($url) {
        if (strpos($url, "?") !== false) {
            $url = substr($url, 0, strpos($url, "?"));
        }
        return $url;
    }

    /**
     * Check whether the used browser is Apple's Safari.
     *
     * Some functions of this plugin (like embedded MumieTasks)are not supported by Safari.
     * That's why we need to implement fallback solutions.
     *
     * @return boolean is Safari?
     */
    public static function is_safari_browser() {
        return self::get_browser_name() === "Apple Safari";
    }

    /**
     * Get the name of the browser used to open moodle.
     *
     * Adapted from https://www.php.net/manual/en/function.get-browser.php#101125.
     *
     * @return string name of the browser
     */
    private static function get_browser_name() {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/MSIE/i', $useragent) && !preg_match('/Opera/i', $useragent)) {
            return 'Internet Explorer';
        }
        if (preg_match('/Firefox/i', $useragent)) {
            return 'Mozilla Firefox';
        }
        if (preg_match('/OPR/i', $useragent)) {
            return 'Opera';
        }
        if (preg_match('/Chrome/i', $useragent) && !preg_match('/Edge/i', $useragent)) {
            return 'Google Chrome';
        }
        if (preg_match('/Safari/i', $useragent) && !preg_match('/Edge/i', $useragent)) {
            return 'Apple Safari';
        }
        if (preg_match('/Netscape/i', $useragent)) {
            return 'Netscape';
        }
        if (preg_match('/Edge/i', $useragent)) {
            return 'Edge';
        }
        if (preg_match('/Trident/i', $useragent)) {
            return 'Internet Explorer';
        }
        return '';
    }

    /**
     * Check whether a different problem was selected for an existing MUMIE Task.
     *
     * @param stdClass $mumietaskupdate the pending update.
     * @return boolean has a new problem been selected?
     */
    public static function has_problem_changed($mumietaskupdate) {
        global $DB;
        $oldtask = $DB->get_record(MUMIE_TASK_TABLE, array('id' => $mumietaskupdate->id));
        $oldurl = self::remove_params_from_url($oldtask->taskurl);
        $newurl = self::remove_params_from_url($mumietaskupdate->taskurl);
        return $oldurl != $newurl;
    }

    /**
     * Get the effective duedate for a student.
     *
     * Individual due date extensions always overrule general due date settings.
     *
     * @param  int $userid
     * @param  \stdClass $mumie
     * @return int
     */
    public static function get_effective_duedate($userid, $mumie) {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/mumie_duedate_extension.php");
        $extension = new mumie_duedate_extension($userid, $mumie->id);
        $extension->load();
        if ($extension->get_duedate()) {
            return $extension->get_duedate();
        }
        return $mumie->duedate;
    }
}
