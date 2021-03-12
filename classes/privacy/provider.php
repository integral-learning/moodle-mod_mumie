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
 * @package mod_mumie
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
 * @package mod_mumie
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
     * @return  collection A listing of user data stored through this system.
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

        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

        $sql = "SELECT c.id FROM {mumie_duedate} duedate
                JOIN {course_modules} cm ON cm.instance = duedate.mumie
                JOIN {context} c ON c.instanceid = cm.id
                WHERE c.contextlevel = :contextlevel AND duedate.userid = :userid
                ";

        $contextlist->add_from_sql(
            $sql,
            array("contextlevel" => CONTEXT_MODULE, "userid" => $userid)
        );
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "SELECT duedate.userid AS userid
                FROM {mumie_duedate} duedate
                JOIN {course_modules} cm ON cm.instance = duedate.mumie
                JOIN {context} ctx ON ctx.instanceid = cm.id
                WHERE ctx.instanceid = :instanceid
                ";
        $userlist->add_from_sql('userid', $sql, array('instanceid' => $context->__get("instanceid")));
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param  approved_contextlist $contextlist The list of approved contexts for a user.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB, $CFG;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a course context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');
            $sql = "SELECT duedate.duedate FROM {mumie_duedate} duedate
                    JOIN {course_modules} cm ON cm.instance = duedate.mumie
                    JOIN {context} ctx ON ctx.instanceid = cm.id
                    JOIN {modules} modules ON modules.id = cm.module
                    WHERE ctx.instanceid = :instanceid
                    AND duedate.userid = :userid
                    AND modules.name = :mumie
                    AND ctx.contextlevel = :ctxlvl
                    ";
            $record = $DB->get_record_sql(
                $sql,
                array(
                    'instanceid' => $context->__get("instanceid"),
                    'userid' => $userid,
                    'mumie' => 'mumie',
                    'ctxlvl' => CONTEXT_MODULE
                )
            );

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

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $sql = "id IN (SELECT duedate.id
                    FROM {mumie_duedate} duedate
                    JOIN {course_modules} cm ON cm.instance = duedate.mumie
                    WHERE cm.id = :instanceid
                    AND duedate.userid = :userid
                    )
                ";

                $DB->delete_records_select(
                    'mumie_duedate',
                    $sql,
                    array('instanceid' => $context->__get("instanceid"), 'userid' => $userid)
                );
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

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = $DB->get_record('course_modules', array('id' => $context->__get("instanceid")));

        $DB->delete_records('mumie_duedate', array('mumie' => $cm->instance));
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if ($context instanceof \context_module && count($userids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($userids);

            $sql = "id IN (
                SELECT duedate.id FROM {mumie_duedate} duedate
                    JOIN {course_modules} cm ON cm.instance = duedate.mumie
                    WHERE cm.id = ?
                    AND duedate.userid $insql
            )";

            $params = array_merge(array($context->__get("instanceid")), $inparams);

            $DB->delete_records_select('mumie_duedate', $sql, $params);
        }
    }
}