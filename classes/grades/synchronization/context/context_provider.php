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

namespace mod_mumie\synchronization\context;

use mod_mumie\locallib;
use auth_mumie\user\mumie_user;
use stdClass;

/**
 * This class is used to create the context that is required for some XAPI requests.
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_provider {
    /**
     * Get context for a given list of MUMIE Tasks and users.
     *
     * @param array $mumietasks
     * @param array $users
     * @return context
     */
    public static function get_context(array $mumietasks, array $users): context {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/context.php");
        $context = new context();
        foreach ($mumietasks as $mumietask) {
            if (self::requires_context($mumietask)) {
                $context->add_object_context(
                    locallib::get_mumie_id($mumietask),
                    self::create_object_context($mumietask, $users)
                );
            }
        }
        return $context;
    }

    /**
     * Check whether a MUMIE Task requires context for XAPI requests.
     * @param stdClass $mumie
     * @return bool
     */
    public static function requires_context(stdClass $mumie): bool {
        return (substr( $mumie->taskurl, 0, 10 ) === "worksheet_")
            && $mumie->duedate > 0;
    }

    /**
     * Create a new object_context instance for a given list of users.
     *
     * @param stdClass $mumie
     * @param array    $users
     * @return object_context
     */
    private static function create_object_context(stdClass $mumie, array $users): object_context {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/object_context.php");
        $context = new object_context($mumie->language);
        foreach ($users as $user) {
            $context->add_user_context($user->get_sync_id(), self::create_user_context($mumie, $user));
        }
        return $context;
    }

    /**
     * Create a new user_context instance for a given user and MUMIE Task
     * @param stdClass   $mumie
     * @param mumie_user $user
     * @return user_context
     */
    private static function create_user_context(stdClass $mumie, mumie_user $user): user_context {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/user_context.php");
        require_once($CFG->dirroot . '/mod/mumie/lib.php');
        return new user_context(mumie_get_deadline_in_ms(locallib::get_effective_duedate($user->get_moodle_id(), $mumie)));
    }
}
