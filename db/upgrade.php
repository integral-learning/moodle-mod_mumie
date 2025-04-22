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
    global $DB;
    $dbman = $DB->get_manager();

    $addtableifmissing = function($tablename, xmldb_key $primary) use ($dbman) {
        if (!$dbman->table_exists($tablename)) {
            $dbman->create_table($tablename);
            $dbman->add_key($tablename, $primary);
        }
    };

    $addfieldifmissing = function($tablename, $fieldname, $type, $precision = null, $notnull = null, $sequence = null, $default = null) use ($dbman) {
        $table = new xmldb_table($tablename);
        $field = new xmldb_field($fieldname, $type, $precision, null, $notnull, $sequence, $default);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    };

    $addfieldifmissing('mumie', 'use_hashed_id', XMLDB_TYPE_INTEGER, '1', null, null, '0');
    $addfieldifmissing('mumie', 'duedate', XMLDB_TYPE_INTEGER, '10', null, null, '0');
    $addfieldifmissing('mumie', 'privategradepool', XMLDB_TYPE_INTEGER, '1');
    mumie_set_privategradepool_default();
    $addfieldifmissing('mumie', 'isgraded', XMLDB_TYPE_INTEGER, '1', 1, null, '0');

    $addtableifmissing('mumie_duedate', 'id');
    $addfieldifmissing('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $addfieldifmissing('mumie', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $addfieldifmissing('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
    $addfieldifmissing('duedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

    $addfieldifmissing('mumie', 'worksheet', XMLDB_TYPE_TEXT);
    $addfieldifmissing('mumie', 'timelimit', XMLDB_TYPE_INTEGER, '10');

    upgrade_plugin_savepoint(true, 2025031200, 'mod', 'mumie');
    return true;
}

