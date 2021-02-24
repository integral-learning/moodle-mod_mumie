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
 * This class provides an interface to export and delete user data.
 *
 * @package auth_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * This class provides an interface to export and delete user data.
 *
 * @package auth_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
        /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised item collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'mumie_duedate',
            [
                'mumie' => 'privacy:metadata:mod_mumie_duedate_extensions:mumie',
                'duedate' => 'privacy:metadata:mod_mumie_duedate_extensions:duedate',
                'userid' => 'privacy:metadata:mod_mumie_duedate_extensions:userid'
            ],
            'privacy:metadata:mod_mumie_duedate_extensions:tableexplanation'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB, $CFG;
        $contextlist = new contextlist();
        $contextlist->set_component('mod_mumie');

        debugging("get contexts for userid");
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');
        $mumieids = array_map(
            function($extension) {
                return $extension->get_mumie();
            },
            \mod_mumie\mumie_duedate_extension::get_all_for_user($userid)
        );

        if (count($mumieids) > 0) {    
            $sql = "SELECT c.id FROM {context} c 
                    JOIN {course_modules} cm ON c.instanceid = cm.id
                    JOIN {mumie_duedate} duedate ON cm.instance = duedate.mumie 
                    WHERE c.contextlevel = :contextlevel AND duedate.userid = :userid";

            debugging(json_encode(array("contextlevel" => CONTEXT_MODULE, "userid" => $userid)));
            $contextlist->add_from_sql(
                $sql,
                array("contextlevel" => CONTEXT_MODULE, "userid" => $userid)
            );
        }
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        debugging("getting users in context");
        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "SELECT userid
            FROM {mumie_duedate}
            WHERE mumie = :mumieid
        ";
        $userlist->add_from_sql('userid', $sql, array('mumieid' => $context->instance));
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param  approved_contextlist $contextlist The list of approved contexts for a user.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB, $CFG;
        debugging("exporting userdata");
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a course context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            debugging("exporting for context: - " . $context->__get("instanceid"));
            require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');
            $sql = "SELECT duedate.duedate FROM {mumie_duedate} duedate
                    JOIN {course_modules} cm on cm.instance = duedate.mumie
                    JOIN {context} ctx ON ctx.instanceid = cm.id
                    WHERE ctx.instanceid = :instanceid
                    ";
            $record = $DB->get_record_sql($sql, array('instanceid' => $context->__get("instanceid")));

            if ($record) {
                writer::with_context($context)->export_data(
                    [
                        get_string('mumie_duedate_extension', 'mod_mumie')
                    ],
                    $record
                );
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');
        $userid = $contextlist->get_user()->id;

        debugging("deleting data for user");

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $sql = "id IN (SELECT duedate.id
                    FROM {mumie_duedate} duedate
                    JOIN {mumie} mumie
                    ON duedate.mumie = mumie.id
                    WHERE mumie.course = :courseid))
                ";

                // $records = $DB->get_records_sql($sql, array("courseid" => $context->instance));
                $DB->delete_records_select('mumie_duedate', $sql, array('courseid' => $context->instance));
            }
        }
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param \context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        debugging("delete_data_for_all_users_in_context");

        // if ($context->contextlevel == CONTEXT_USER) {
        //     $DB->get_records('auth_mumie_id_hashes');
        //     $DB->delete_records('auth_mumie_sso_tokens');
        // } else if ($context->contextlevel == CONTEXT_COURSE) {
        //     $courseid = $context->__get("instanceid");
        //     $sql = "SELECT * FROM {auth_mumie_id_hashes} WHERE 'hash' LIKE = ':gradepool'";
        //     $DB->get_records_sql($sql, array("gradepool" => "%@gradepool{$courseid}@"));
        //     foreach ($records as $record) {
        //         $DB->delete_records('auth_mumie_id_hashes', array('the_user', $userid));
        //         $DB->delete_records('auth_mumie_sso_tokens', array('the_user', $record->hash));
        //     }
        // }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;


        debugging("delete_data_for_users");
        // $context = $userlist->get_context();

        // if ($context instanceof \context_user) {
        //     self::delete_in_user_context($context, $userlist->get_userids());
        // } else if ($context instanceof \context_course) {
        //     self::delete_in_course_context($context, $userlist->get_userids());
        // }
    }
}