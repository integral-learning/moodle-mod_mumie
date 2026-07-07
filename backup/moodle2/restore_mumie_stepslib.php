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
 * Structure step to restore one mumie activity
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mumie_activity_structure_step extends restore_activity_structure_step {
    /**
     * define the structure for restoration process
     */
    protected function define_structure() {
        $paths = [];

        // We need to concat this path or moodle code checker will display a false error.
        $path = '/activity' . '/mumie';
        $paths[] = new restore_path_element('mumie', $path);
        $paths[] = new restore_path_element('task', $path . '/task');
        $paths[] = new restore_path_element('tutor', $path . '/tutor');
        $paths[] = new restore_path_element('serverconfig', $path . '/task/serverconfig');
        // Older backups placed serverconfig directly under mumie (before task fields moved to mumie_task).
        $paths[] = new restore_path_element('serverconfig_legacy', $path . '/serverconfig');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the shared mumie row. For older backups that predate the task/tutor split,
     * task fields sit flat under <mumie> with no separate <task> element to trigger
     * process_task, so this handler also creates the mumie_task row in that case.
     *
     * @param mixed $data parsed from the backup file
     */
    protected function process_mumie($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $flattask = null;
        if (!property_exists($data, 'type')) {
            // Older backup: the type column did not exist yet (every row was a task) and
            // task fields sit flat under <mumie> with no separate <task> element.
            $data->type = 'task';
            $flattask = clone $data;
        }

        $shared = self::pick_fields($data, [
            'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'type',
        ]);
        $newitemid = $DB->insert_record('mumie', $shared);
        $this->apply_activity_instance($newitemid);

        if ($flattask !== null) {
            $flattask->id = $newitemid;
            $this->insert_task_row($flattask);
        }
    }

    /**
     * Restore the mumie_task extension row.
     *
     * @param mixed $data parsed from the backup file
     */
    protected function process_task($data) {
        $data = (object) $data;
        $data->id = $this->get_new_parentid('mumie');
        $this->insert_task_row($data);
    }

    /**
     * Restore the mumie_tutor extension row.
     *
     * @param mixed $data parsed from the backup file
     */
    protected function process_tutor($data) {
        global $DB;
        $data = (object) $data;
        $data->id = $this->get_new_parentid('mumie');
        $DB->import_record('mumie_tutor', $data);
    }

    /**
     * Insert a MUMIE server configuration if there is no conflict.
     *
     * @param mixed $data parsed from the backup file
     */
    protected function process_serverconfig($data) {
        global $DB;

        $data = (object) $data;

        // Only insert record, if there are no configurations for name or prefix.
        // This means that a missing server is not always automatically restored
        // and needs to be added manually before the task can be edited.
        $recordnameexists = $DB->record_exists("auth_mumie_servers", ["name" => $data->name]);
        $recordurlexists = $DB->record_exists("auth_mumie_servers", ["url_prefix" => $data->url_prefix]);

        if (!$recordnameexists && !$recordurlexists) {
            $DB->insert_record('auth_mumie_servers', $data);
        }
    }

    /**
     * Legacy path: older backups placed serverconfig directly under <mumie> instead of under <task>.
     *
     * @param mixed $data parsed from the backup file
     */
    protected function process_serverconfig_legacy($data) {
        $this->process_serverconfig($data);
    }

    /**
     * Insert a mumie_task row. Applies the course-wide "inherit privategradepool from an
     * existing task in the same course" rule — the pool decision is shared across a course,
     * so a newly-restored task should match any sibling task already in the course.
     *
     * @param stdClass $data merged data (may contain extra fields; insert_record ignores unknowns).
     */
    private function insert_task_row(stdClass $data) {
        global $DB;

        $data->use_hashed_id = 1;

        $courseid = $this->get_courseid();
        $existing = $DB->get_records_sql(
            "SELECT mt.privategradepool
               FROM {mumie_task} mt
               JOIN {mumie} m ON m.id = mt.id
              WHERE m.course = ? AND m.id != ?",
            [$courseid, $data->id],
            0,
            1
        );
        if ($existing) {
            $data->privategradepool = array_values($existing)[0]->privategradepool;
        } else {
            $data->privategradepool = $data->privategradepool ?? null;
        }

        $DB->import_record('mumie_task', $data);
    }

    /**
     * Copy the named fields from $source into a new stdClass. Absent fields are skipped.
     *
     * @param stdClass $source
     * @param string[] $fields
     * @return stdClass
     */
    private static function pick_fields(stdClass $source, array $fields): stdClass {
        $target = new stdClass();
        foreach ($fields as $field) {
            if (property_exists($source, $field)) {
                $target->$field = $source->$field;
            }
        }
        return $target;
    }
}
