<?php

namespace mod_mumie\synchronization\context;

use mod_mumie\locallib;
use auth_mumie\user\mumie_user;

class context_provider
{
    public static function get_context(array $mumies, array $users): context {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/context.php");
        $context = new context();
        foreach ($mumies as $mumie) {
            if(self::has_context($mumie)) {
                $context->add_object_context(
                    self::get_mumie_id($mumie),
                    self::get_object_context($mumie, $users)
                );
            }
        }
        return $context;
    }


    //TODO: This is copied from gradesync.php
    //Refactor so that we don't have duplicate code
    /**
     * Get the unique identifier for a MUMIE task
     *
     * @param stdClass $mumietask
     * @return string id for MUMIE task on MUMIE/LEMON server
     */
    private static function get_mumie_id($mumietask): string {
        $id = $mumietask->taskurl;
        $prefix = "link/";
        if (strpos($id, $prefix) !== false) {
            $id = substr($mumietask->taskurl, strlen($prefix));
        }
        $id = substr($id, 0, strpos($id, '?lang='));
        return $id;
    }

    public static function has_context($mumie): bool {
        return str_starts_with($mumie->taskurl, "worksheet_")
            && $mumie->duedate > 0;
    }

    private static function get_object_context($mumie, array $users): object_context {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/object_context.php");
        $context = new object_context();
        foreach ($users as $user) {
            $context->add_user_context($user->get_sync_id(), self::get_user_context($mumie, $user));
        }
        return $context;
    }

    private static function get_user_context($mumie, mumie_user $user): user_context {
        global $CFG;
        require_once($CFG->dirroot . "/mod/mumie/classes/grades/synchronization/context/user_context.php");
        return new user_context(locallib::get_effective_duedate($user->get_moodle_id(), $mumie));
    }
}