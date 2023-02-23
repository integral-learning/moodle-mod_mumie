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
    // Currently there is no need for an upgrade.php, but this file is necessary.

    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019110100) {
        $table = new xmldb_table('mumie');
        $field = new xmldb_field('use_hashed_id', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019110100, 'mod', 'mumie');
    }

    if ($oldversion < 2020011702) {
        $table = new xmldb_table('mumie');
        $field = new xmldb_field('duedate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('mumie');
        $field = new xmldb_field('privategradepool', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        require_once($CFG->dirroot . '/mod/mumie/db/upgradelib.php');
        mumie_set_privategradepool_default();
        upgrade_plugin_savepoint(true, 2020011702, 'mod', 'mumie');
    }

    if ($oldversion < 2020040700) {
        $table = new xmldb_table('mumie');
        $field = new xmldb_field('isgraded', XMLDB_TYPE_INTEGER, '1', 1, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2020040700, 'mod', 'mumie');
    }

    if ($oldversion < 2021011303) {
        $table = new xmldb_table('mumie_duedate');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mumie', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('duedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2021011303, 'mod', 'mumie');
    }
    if ($oldversion < 2023022301) {
        $table = new xmldb_table('mumie');
        $field = new xmldb_field('worksheet', XMLDB_TYPE_TEXT, null, null, false, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2023022301, 'mod', 'mumie');
    }
    return true;
}
