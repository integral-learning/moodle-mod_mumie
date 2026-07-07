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
 * Data-access layer for the class-table-inheritance split of the mumie table.
 *
 * @package   mod_mumie
 * @copyright 2026 integral-learning GmbH (https://www.integral-learning.de/)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

use stdClass;
use coding_exception;

/**
 * Reads and writes mumie activity rows across the parent table and its per-subtype extensions.
 */
class repository {
    private const SHARED_FIELDS = [
        'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'type',
    ];

    private const TASK_FIELDS = [
        'taskurl', 'launchcontainer', 'mumie_course', 'language', 'server',
        'mumie_coursefile', 'lastsync', 'points', 'use_hashed_id', 'duedate',
        'timelimit', 'privategradepool', 'isgraded', 'worksheet',
    ];

    private const TUTOR_FIELDS = [];

    /**
     * Load a merged mumie record by id, dispatching on its type.
     *
     * @param int $id
     * @return stdClass|null null if no row exists for $id
     */
    public static function get(int $id): ?stdClass {
        global $DB;
        $mumie = $DB->get_record('mumie', ['id' => $id]);
        if (!$mumie) {
            return null;
        }
        return self::merge_extension($mumie);
    }

    /**
     * Load a merged task record by id.
     *
     * @param int $id
     * @return stdClass|null null if no row exists for $id or the row is not a task
     */
    public static function get_task(int $id): ?stdClass {
        global $DB;
        $mumie = $DB->get_record('mumie', ['id' => $id, 'type' => 'task']);
        return $mumie ? self::merge_extension($mumie) : null;
    }

    /**
     * Load a merged tutor record by id.
     *
     * @param int $id
     * @return stdClass|null null if no row exists for $id or the row is not a tutor
     */
    public static function get_tutor(int $id): ?stdClass {
        global $DB;
        $mumie = $DB->get_record('mumie', ['id' => $id, 'type' => 'tutor']);
        return $mumie ? self::merge_extension($mumie) : null;
    }

    /**
     * Load all merged task records for a course, keyed by id.
     *
     * @param int $courseid
     * @return stdClass[]
     */
    public static function get_tasks_in_course(int $courseid): array {
        global $DB;
        $rows = [];
        foreach ($DB->get_records('mumie', ['course' => $courseid, 'type' => 'task']) as $mumie) {
            $rows[$mumie->id] = self::merge_extension($mumie);
        }
        return $rows;
    }

    /**
     * Insert a new mumie row plus its extension row in a single transaction.
     *
     * @param stdClass $mumie merged record; must set `type`
     * @return int the id of the newly inserted mumie row
     */
    public static function save_new(stdClass $mumie): int {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        [$shared, $extension] = self::split_fields($mumie);
        $id = $DB->insert_record('mumie', $shared);
        $extension->id = $id;
        // import_record instead of insert_record: the extension table's id is not auto-generated
        // and must match the parent's id. insert_record strips `id`, import_record preserves it.
        $DB->import_record(self::extension_table($mumie->type), $extension);
        $transaction->allow_commit();
        return $id;
    }

    /**
     * Update an existing mumie row plus its extension row in a single transaction.
     *
     * @param stdClass $mumie merged record; must set `id` and `type`
     * @return bool
     */
    public static function save_update(stdClass $mumie): bool {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        [$shared, $extension] = self::split_fields($mumie);
        $shared->id = $mumie->id;
        $extension->id = $mumie->id;
        $DB->update_record('mumie', $shared);
        $DB->update_record(self::extension_table($mumie->type), $extension);
        $transaction->allow_commit();
        return true;
    }

    /**
     * Delete a mumie row and its extension row.
     *
     * @param int $id
     * @return bool true if a row was deleted, false if none existed
     */
    public static function delete(int $id): bool {
        global $DB;
        $mumie = $DB->get_record('mumie', ['id' => $id]);
        if (!$mumie) {
            return false;
        }
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records(self::extension_table($mumie->type), ['id' => $id]);
        $DB->delete_records('mumie', ['id' => $id]);
        $transaction->allow_commit();
        return true;
    }

    /**
     * Return the extension table name for the given subtype.
     *
     * @param string $type
     * @return string
     * @throws coding_exception on unknown subtype
     */
    private static function extension_table(string $type): string {
        return match ($type) {
            'task' => 'mumie_task',
            'tutor' => 'mumie_tutor',
            default => throw new coding_exception("Unknown mumie type '{$type}'"),
        };
    }

    /**
     * Return the field list on the extension table for the given subtype.
     *
     * @param string $type
     * @return array
     * @throws coding_exception on unknown subtype
     */
    private static function extension_fields(string $type): array {
        return match ($type) {
            'task' => self::TASK_FIELDS,
            'tutor' => self::TUTOR_FIELDS,
            default => throw new coding_exception("Unknown mumie type '{$type}'"),
        };
    }

    /**
     * Merge the extension row into the given mumie parent row and return the merged object.
     *
     * @param stdClass $mumie loaded from the `mumie` table (must have `id` and `type`)
     * @return stdClass
     */
    private static function merge_extension(stdClass $mumie): stdClass {
        global $DB;
        $extension = $DB->get_record(self::extension_table($mumie->type), ['id' => $mumie->id], '*', MUST_EXIST);
        foreach (self::extension_fields($mumie->type) as $field) {
            $mumie->$field = $extension->$field ?? null;
        }
        return $mumie;
    }

    /**
     * Split a merged record into two stdClass objects, one for the parent table and one for
     * the extension table. `id` is omitted from both — callers set it explicitly after the
     * parent insert (for save_new) or reuse the existing id (for save_update).
     *
     * @param stdClass $mumie merged record
     * @return array{0: stdClass, 1: stdClass} [$shared, $extension]
     */
    private static function split_fields(stdClass $mumie): array {
        $shared = new stdClass();
        foreach (self::SHARED_FIELDS as $field) {
            if (property_exists($mumie, $field)) {
                $shared->$field = $mumie->$field;
            }
        }
        $extension = new stdClass();
        foreach (self::extension_fields($mumie->type) as $field) {
            if (property_exists($mumie, $field)) {
                $extension->$field = $mumie->$field;
            }
        }
        return [$shared, $extension];
    }
}
