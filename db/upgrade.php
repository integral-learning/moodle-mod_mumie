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
 * This file keeps track of upgrades to the mod_mumie module
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/mumie/db/upgradelib.php');

/**
 * xmldb_mumie_upgrade is the function that upgrades
 * the mod_mumie database when it's needed
 *
 * This function is automatically called when version number in
 * version.php changes.
 *
 * @param int $oldversion New old version number.
 *
 * @return boolean
 */
function xmldb_mumie_upgrade($oldversion) {

    if ($oldversion < 2019110100) {
        addfieldifmissing('mumie', 'use_hashed_id', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', null);
        upgrade_plugin_savepoint(true, 2019110100, 'mod', 'mumie');
    }
    if ($oldversion < 2020011702) {
        addfieldifmissing('mumie', 'duedate', XMLDB_TYPE_INTEGER, '10', null, null,  null, '0', null);
        addfieldifmissing('mumie', 'privategradepool', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null);
        mumie_set_privategradepool_default();
        upgrade_plugin_savepoint(true, 2020011702, 'mod', 'mumie');
    }
    if ($oldversion < 2020040700) {
        addfieldifmissing('mumie', 'isgraded', XMLDB_TYPE_INTEGER, '1', 1, null, null, '0', null);
        upgrade_plugin_savepoint(true, 2020040700, 'mod', 'mumie');
    }

    if ($oldversion < 2021011303) {
        addtableifmissing('mumie_duedate', 'id');
        addfieldifmissing('mumie_duedate', 'id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
        XMLDB_SEQUENCE, null, null);
        addfieldifmissing('mumie_duedate', 'mumie', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
        null, null, null);
        addfieldifmissing('mumie_duedate', 'userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        addfieldifmissing('mumie_duedate', 'duedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        upgrade_plugin_savepoint(true, 2021011303, 'mod', 'mumie');
    }

    if ($oldversion < 2023050900) {
        addfieldifmissing('mumie', 'worksheet', XMLDB_TYPE_TEXT, null, null, false, null, null, null);
        upgrade_plugin_savepoint(true, 2023050900, 'mod', 'mumie');
    }
    if ($oldversion < 2025031200) {
        addfieldifmissing('mumie', 'timelimit', XMLDB_TYPE_INTEGER, '10', null, false, null, null, null);
        upgrade_plugin_savepoint(true, 2025031200, 'mod', 'mumie');
    }

    return true;
}

/**
 * Creates table if doesn't exist, with given primary
 * @param string $tablename
 * @param string $primaryname
 * @return void
 */
function addtableifmissing(string $tablename, string $primaryname): void {
    global $DB;
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists($tablename)) {
        $table = new xmldb_table ($tablename);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, [$primaryname]);
        $dbman->create_table($table);
    }
}

/**
 * Creates field if doesn't exist
 * @param string $tablename
 * @param string $fieldname — of field
 * @param null|int $type XMLDB_TYPE_INTEGER, XMLDB_TYPE_NUMBER, XMLDB_TYPE_CHAR, XMLDB_TYPE_TEXT, XMLDB_TYPE_BINARY
 * @param null|string $precision length for integers and chars, two-comma separated numbers for numbers
 * @param null|bool $unsigned — XMLDB_UNSIGNED or null (or false)
 * @param null|bool $notnull — XMLDB_NOTNULL or null (or false)
 * @param null|bool $sequence — XMLDB_SEQUENCE or null (or false)
 * @param $default — meaningful default o null (or false)
 * @param null|string $previous
 * @return void
 */
function addfieldifmissing(string $tablename, string $fieldname, ?int $type, ?string $precision,
    ?bool $unsigned, ?bool $notnull, ?bool $sequence, $default = null, ?string $previous): void {
    global $DB;
    $dbman = $DB->get_manager();
    $table = new xmldb_table($tablename);
    $field = new xmldb_field($fieldname, $type, $precision, $unsigned, $notnull, $sequence, $default, $previous);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}
