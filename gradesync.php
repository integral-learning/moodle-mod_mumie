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
 * This file defines the class gradesync used to synchronize grades from MUMIE servers with the MOODLE gradebook
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;


use mod_mumie\synchronization\payload;
use mod_mumie\synchronization\xapi_request;
use mod_mumie\synchronization\context\context_provider;
use auth_mumie\user\mumie_user_service;
use stdClass;
use context_course;
use auth_mumie\mumie_server;
use auth_mumie\user\mumie_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/mumie/lib.php');
require_once($CFG->dirroot . "/mod/mumie/locallib.php");
require_once($CFG->dirroot . '/auth/mumie/lib.php');
require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');
require_once($CFG->dirroot . '/auth/mumie/classes/sso/user/mumie_user_service.php');

/**
 * This file defines the class gradesync
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradesync {
    /**
     * Update grades for all MUMIE tasks that are currently displayed in the gradebook
     * @return void
     */
    public static function update() {
        global $PAGE, $USER, $COURSE;

        $isreportpage = strpos($PAGE->url, 'grader/index.php') !== false || strpos($PAGE->url, 'user/index.php') !== false;
        $isoverviewpage = (strpos($PAGE->url, 'overview/index.php') !== false);

        if ($isreportpage) {
            $userid = $USER->id;
            if (has_capability("mod/mumie:viewgrades", context_course::instance($COURSE->id), $USER)) {
                foreach (self::get_mumie_tasks_from_course($COURSE->id) as $mumie) {
                    mumie_update_grades_all_user($mumie);
                }
            }
        } else {
            if ($isoverviewpage) {
                foreach (self::get_all_mumie_tasks_for_user($USER->id) as $mumie) {
                    mumie_update_grades($mumie, $USER->id);
                }
            }
        }
    }

    /**
     * Get all graded MUMIE tasks that are used in this course
     * @param int $courseid
     * @return array All MUMIE tasks that are used in the given course
     */
    public static function get_mumie_tasks_from_course(int $courseid): array {
        global $DB;
        return $DB->get_records(MUMIE_TASK_TABLE, ["course" => $courseid, "isgraded" => 1]);
    }

    /**
     * Get all MUMIE tasks, that are in courses the user is enrolled in
     *
     * @param int $userid
     * @return array All MUMIE tasks that are in courses, the user is enrolled in
     */
    public static function get_all_mumie_tasks_for_user(int $userid): array {
        $allmumietasks = [];
        foreach (enrol_get_all_users_courses($userid) as $course) {
            $mumietasks = self::get_mumie_tasks_from_course($course->id);
            if (count($mumietasks) > 0) {
                $allmumietasks = array_merge($allmumietasks, $mumietasks);
            }
        }

        return $allmumietasks;
    }

    /**
     * Get grades from MUMIE server for a given MUMIE task
     *
     * @param stdClass $mumie instance of MUMIE task
     * @param int $userid If userid = 0, update all users
     * @return array grades for the given MUMIE task
     */
    public static function get_mumie_grades(stdClass $mumie, int $userid): ?array {
        $mumieusers = self::get_mumie_users($mumie, $userid);
        $mumieids = [locallib::get_mumie_id($mumie)];
        $xapigrades = self::get_xapi_grades($mumie, $mumieusers, $mumieids);

        if (empty($xapigrades)) {
            return null;
        }

        $grades = [];
        foreach ($xapigrades as $xapigrade) {
            $grade = self::xapi_to_moodle_grade($xapigrade);
            if (self::include_grade($mumie, $grades, $grade)) {
                $grades[$grade->userid] = $grade;
            }
        }
        return $grades;
    }

    /**
     * Get an array of MUMIE users that can submit answers to a given MUMIE Task.
     *
     * If $userid is 0, all possible students are returned. Otherwise, the array will only contain the user with the given $userid.
     * @param stdClass $mumie
     * @param int      $userid
     * @return array
     */
    private static function get_mumie_users(stdClass $mumie, int $userid): array {
        global $COURSE;
        if ($userid == 0) {
            $mumieusers = [];
            foreach (get_enrolled_users(context_course::instance($COURSE->id)) as $user) {
                $mumieusers[] = mumie_user_service::get_user($user->id, $mumie);
            }
            return $mumieusers;
        } else {
            return [mumie_user_service::get_user($userid, $mumie)];
        }
    }

    /**
     * Get all grades a user has archived for a given MUMIE Task
     *
     * @param  stdClass $mumie
     * @param  int $userid
     * @return array
     */
    public static function get_all_grades_for_user(stdClass $mumie, int $userid): ?array {

        $mumieusers = [mumie_user_service::get_user($userid, $mumie)];
        $mumieids = [locallib::get_mumie_id($mumie)];
        $grades = [];
        $xapigrades = self::get_xapi_grades($mumie, $mumieusers, $mumieids);

        if (empty($xapigrades)) {
            return null;
        }

        foreach ($xapigrades as $xapigrade) {
            $grades[] = self::xapi_to_moodle_grade($xapigrade);
        }
        return $grades;
    }

    /**
     * Transform Xapi grade to moodle grade objects.
     *
     * @param  stdClass $xapigrade
     * @return stdClass
     */
    private static function xapi_to_moodle_grade($xapigrade): stdClass {
        $grade = new stdClass();
        $syncid = self::get_mumie_user_from_sync_id($xapigrade->actor->account->name);
        $grade->userid = $syncid->get_moodle_id();
        $grade->rawgrade = 100 * $xapigrade->result->score->raw;
        $grade->timecreated = strtotime($xapigrade->timestamp);
        return $grade;
    }

    /**
     * Get a mumie_user from a syncid.
     * @param string $syncid
     * @return mumie_user|null
     * @throws \dml_exception
     */
    private static function get_mumie_user_from_sync_id(string $syncid): ?mumie_user {
        $mumieid = substr(strrchr($syncid, "_"), 1);
        return mumie_user_service::get_user_from_mumie_id($mumieid);
    }

    /**
     * Indicate whether a grade was archived before the task was due and is the latest one currently available
     *
     * @param stdClass $mumie instance of MUMIE task we want to get grades for
     * @param array $grades an array of all grades we have selected so far
     * @param stdClass $potentialgrade the grade in question
     * @return boolean Whether the grade should be added to $grades
     */
    public static function include_grade(stdClass $mumie, array $grades, stdClass $potentialgrade): bool {
        $duedate = locallib::get_effective_duedate($potentialgrade->userid, $mumie);
        if (!isset($duedate) || $duedate == 0) {
            return true;
        }

        if ($duedate < $potentialgrade->timecreated) {
            return false;
        }

        return self::is_latest_grade($grades, $potentialgrade);
    }

    /**
     * True, if the given grade is currently the latest available one.
     *
     * @param  array $grades List of the latest grades so far by user.
     * @param  stdClass $potentialgrade The grade we are testing
     * @return boolean
     */
    private static function is_latest_grade(array $grades, stdClass $potentialgrade): bool {
        return !isset($grades[$potentialgrade->userid])
        || $grades[$potentialgrade->userid]->timecreated < $potentialgrade->timecreated;
    }

    /**
     * Get xapi grades for a MUMIE task instance
     *
     * @param stdClass $mumie instance of MUMIE task we want to get grades for
     * @param array $mumieusers all users we want grades for
     * @param array $mumieids mumieid of mumie instance as an array
     * @return array all requested grades for the given MUMIE task
     */
    public static function get_xapi_grades(stdClass $mumie, array $mumieusers, array $mumieids): array {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/payload.php");
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/xapi_request.php");
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/context_provider.php");
        require_once($CFG->dirroot . "/auth/mumie/classes/sso/user/mumie_user.php");
        $mumieserver = new mumie_server();
        $mumieserver->set_urlprefix($mumie->server);
        $syncids = array_map(
            function ($user) {
                return $user->get_sync_id();
            },
            $mumieusers
        );
        $payload = new payload($syncids, $mumie->mumie_coursefile, $mumieids, $mumie->lastsync, true);
        if (context_provider::requires_context($mumie)) {
            $context = context_provider::get_context([$mumie], $mumieusers);
            $payload->with_context($context);
        }
        $request = new xapi_request($mumieserver, $payload);
        return $request->send();
    }
}
