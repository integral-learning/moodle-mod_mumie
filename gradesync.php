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
use auth_mumie\user\mumie_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mumie/lib.php');
require_once($CFG->dirroot . '/auth/mumie/lib.php');
require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

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
            if (has_capability("mod/mumie:viewgrades", \context_course::instance($COURSE->id), $USER)) {
                // User id = 0 => update grades for all users!
                $userid = 0;
            }
            foreach (self::get_mumie_tasks_from_course($COURSE->id) as $mumie) {
                mumie_update_grades($mumie, $userid);
            }
        } else {
            if ($isoverviewpage) {
                foreach (self::get_all_mumie_tasks_for_user($USER->id) as $mumie) {
                    mumie_update_grades($mumie, $USER->id);
                }
            }
        }
        return;
    }

    /**
     * Get all graded MUMIE tasks that are used in this course
     * @param int $courseid
     * @return array All MUMIE tasks that are used in the given course
     */
    public static function get_mumie_tasks_from_course($courseid) {
        global $DB;
        return $DB->get_records(MUMIE_TASK_TABLE, array("course" => $courseid, "isgraded" => 1));
    }

    /**
     * Get all MUMIE tasks, that are in courses the user is enrolled in
     *
     * @param int $userid
     * @return array All MUMIE tasks that are in courses, the user is enrolled in
     */
    public static function get_all_mumie_tasks_for_user($userid) {
        $allmumietasks = array();
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
    public static function get_mumie_grades($mumie, $userid) {
        global $COURSE, $DB;
        $syncids = array();

        if ($userid == 0) {
            foreach (get_enrolled_users(\context_course::instance($COURSE->id)) as $user) {
                array_push($syncids, self::get_sync_id($user->id, $mumie));
            }
        } else {
            $syncids = array(self::get_sync_id($userid, $mumie));
        }

        $mumieids = array(self::get_mumie_id($mumie));
        $grades = array();
        $xapigrades = self::get_xapi_grades($mumie, $syncids, $mumieids);

        if (is_null($xapigrades)) {
            return null;
        }

        foreach ($xapigrades as $xapigrade) {
            $grade = self::xapi_to_moodle_grade($xapigrade, $mumie);
            if (self::include_grade($mumie, $grades, $grade)) {
                $grades[$grade->userid] = $grade;
            }
        }
        return $grades;
    }

    /**
     * Get all grades a user has archived for a given MUMIE Task
     *
     * @param  \stdClass $mumie
     * @param  int $userid
     * @return array
     */
    public static function get_all_grades_for_user($mumie, $userid) {
        global $COURSE, $DB;
        $syncids = array();

        array_push($syncids, self::get_sync_id($userid, $mumie));

        $mumieids = array(self::get_mumie_id($mumie));
        $grades = array();
        $xapigrades = self::get_xapi_grades($mumie, $syncids, $mumieids);

        if (is_null($xapigrades)) {
            return null;
        }

        foreach ($xapigrades as $xapigrade) {
            $grade = self::xapi_to_moodle_grade($xapigrade, $mumie);
            array_push($grades, $grade);
        }
        return $grades;
    }

    /**
     * Transform Xapi grade to moodle grade objects.
     *
     * @param  \stdClass $xapigrade
     * @param  \stdClass $mumie
     * @return \stdClass
     */
    private static function xapi_to_moodle_grade($xapigrade, $mumie) {
        $grade = new \stdClass();
        $grade->userid = self::get_moodle_user_id($xapigrade->actor->account->name, $mumie->use_hashed_id);
        $grade->rawgrade = 100 * $xapigrade->result->score->raw;
        $grade->timecreated = strtotime($xapigrade->timestamp);
        return $grade;
    }

    /**
     * Indicate whether a grade was archived before the task was due and is the latest one currently available
     *
     * @param stdClass $mumie instance of MUMIE task we want to get grades for
     * @param array $grades an array of all grades we have selected so far
     * @param stdClass $potentialgrade the grade in question
     * @return boolean Whether the grade should be added to $grades
     */
    public static function include_grade($mumie, $grades, $potentialgrade) {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/locallib.php");
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
     * @param  \stdClass $potentialgrade The grade we are testing
     * @return boolean
     */
    private static function is_latest_grade($grades, $potentialgrade) {
        return !isset($grades[$potentialgrade->userid])
        || $grades[$potentialgrade->userid]->timecreated < $potentialgrade->timecreated;
    }

    /**
     * Get xapi grades for a MUMIE task instance
     *
     * @param stdClass $mumie instance of MUMIE task we want to get grades for
     * @param array $syncids all users we want grades for
     * @param array $mumieids mumieid of mumie instance as an array
     * @return stdClass all requested grades for the given MUMIE task
     */
    public static function get_xapi_grades($mumie, $syncids, $mumieids) {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/payload.php");
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/xapi_request.php");
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/context_provider.php");
        require_once($CFG->dirroot . "/auth/mumie/classes/sso/user/mumie_user.php");
        $mumieserver = new \auth_mumie\mumie_server();
        $mumieserver->set_urlprefix($mumie->server);
        $payload = new payload($syncids, $mumie->mumie_coursefile, $mumieids, $mumie->lastsync, true);
        if(context_provider::has_context($mumie)) {
            $context = context_provider::get_context(array($mumie), array(new mumie_user("2", "2")));
            $payload->with_context($context);
        }
        debugging(json_encode($payload));
        $request = new xapi_request($mumieserver, $payload);
        return $request->send();
    }

    /**
     * Get a unique syncid from a userid that can be used on the MUMIE server as username
     * @param int $userid
     * @param int $mumie
     * @return string unique username for MUMIE servers derived from the moodle userid
     */
    public static function get_sync_id($userid, $mumie) {
        global $DB;
        $org = get_config('auth_mumie', 'mumie_org');
        if ($mumie->use_hashed_id == 1) {
            $hashidtable = 'auth_mumie_id_hashes';
            $hash = auth_mumie_get_hashed_id($userid);
            if ($mumie->privategradepool) {
                $hash .= '@gradepool' . $mumie->course . '@';
            }
            if (!$DB->get_record($hashidtable, array("the_user" => $userid, 'hash' => $hash))) {
                $DB->insert_record($hashidtable, array("the_user" => $userid, "hash" => $hash));
            }
            $userid = $hash;
        }
        return "GSSO_" . $org . "_" . $userid;
    }

    /**
     * Get moodleUserID from syncid
     * @param string $syncid
     * @param int $hashid indicates whether the id was hashed
     * @return string of moodle user
     */
    public static function get_moodle_user_id($syncid, $hashid) {
        $userid = substr(strrchr($syncid, "_"), 1);
        $hashidtable = 'auth_mumie_id_hashes';
        if ($hashid == 1) {
            global $DB;
            $userid = $DB->get_record($hashidtable, array("hash" => $userid))->the_user;
        }
        return $userid;
    }

    /**
     * Get the unique identifier for a MUMIE task
     *
     * @param stdClass $mumietask
     * @return string id for MUMIE task on MUMIE/LEMON server
     */
    public static function get_mumie_id($mumietask) {
        $id = $mumietask->taskurl;
        $prefix = "link/";
        if (strpos($id, $prefix) !== false) {
            $id = substr($mumietask->taskurl, strlen($prefix));
        }
        $id = substr($id, 0, strpos($id, '?lang='));
        return $id;
    }
}
