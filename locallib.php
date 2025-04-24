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

global $CFG;

use stdClass;


require_once($CFG->dirroot . '/mod/mumie/lib.php');

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
        return $DB->get_record(MUMIE_TASK_TABLE, ['id' => $id]);
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
    public static function get_mumie_tasks_by_course($courseid) {
        global $DB;
        return $DB->get_records(MUMIE_TASK_TABLE, ["course" => $courseid]);
    }

    /**
     * The function is called whenever a MUMIE task is updated or created.
     * If a pending decision regarding gradepools was made, we need to update all other MUMIE Tasks in this course as well.
     * @param stdClass $mumietask The update we are processing
     */
    public static function update_pending_gradepool($mumietask) {
        global $DB;
        $update = false;
        if (!isset($mumietask->id)) {
            $update = true;
        } else {
            $oldrecord = $DB->get_record(MUMIE_TASK_TABLE, ['id' => $mumietask->id]);
            if ($oldrecord->privategradepool != $mumietask->privategradepool) {
                $update = true;
            }
        }
        if ($update) {
            $tasks = $DB->get_records(MUMIE_TASK_TABLE, ["course" => $mumietask->course]);
            foreach ($tasks as $task) {
                if (!isset($task->privategradepool)) {
                    $task->privategradepool = $mumietask->privategradepool;
                    $DB->update_record(MUMIE_TASK_TABLE, $task);
                }
            }
        }
    }

    /**
     * The function is called whenever a MUMIE task is created or updated.
     * Cleans up the submitted duedate and timelimit if not selected in the duration selector.
     * @param stdClass $mumietask the submitted MUMIE task that is supposed to be created or updated
     * @return stdClass the MUMIE task with cleaned duration values
     */
    public static function clean_up_duration_values(stdClass $mumietask): stdClass {
        $workingperiod = $mumietask->duration_selector;
        if (isset($workingperiod)) {
            if ($workingperiod != 'duedate') {
                $mumietask->duedate = 0;
            }
            if ($workingperiod != 'timelimit') {
                $mumietask->timelimit = 0;
            }
        }
        return $mumietask;
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
        $googlechrome = 'Google Chrome';
        $applesafari = 'Apple Safari';
        $internetexplorer = 'Internet Explorer';
        $edgereg = '/Edge/i';

        $browsers = [
            $googlechrome => '/Chrome/i',
            $applesafari => '/Safari/i',
            $internetexplorer => '/MSIE|Trident/i',
            'Edge' => $edgereg,
            'Opera' => '/OPR/i',
            'Mozilla Firefox' => '/Firefox/i',
            'Netscape' => '/Netscape/i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $useragent)) {
                // Spezialfall: Chrome wird auch von Edge/Safari erkannt.
                if ($name === $googlechrome && preg_match($edgereg, $useragent)) {
                    continue;
                }
                if ($name === $applesafari && preg_match($edgereg, $useragent)) {
                    continue;
                }
                if ($name === $internetexplorer && preg_match('/Opera/i', $useragent)) {
                    continue;
                }
                return $name;
            }
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
        $oldtask = $DB->get_record(MUMIE_TASK_TABLE, ['id' => $mumietaskupdate->id]);
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

    /**
     * Get the unique identifier for a MUMIE task
     *
     * @param stdClass $mumietask
     * @return string id for MUMIE task on MUMIE/LEMON server
     */
    public static function get_mumie_id($mumietask): string {
        $id = $mumietask->taskurl;
        $prefix = "link/";
        if (strpos($id, $prefix) !== false) {
            $id = substr($mumietask->taskurl, strlen($prefix));
        }
        $id = substr($id, 0, strpos($id, '?lang='));
        return $id;
    }

    /**
     * Update grades for MUMIE tasks, whenever a gradebook is opened
     */
    public static function callbackimpl_before_standard_top_of_body_html(): string {
        global $PAGE, $CFG;

        if (!strpos($PAGE->url, '/grade/report/')) {
            return "";
        }

        require_once($CFG->dirroot . '/mod/mumie/gradesync.php');
        gradesync::update();

        return "";
    }
}
