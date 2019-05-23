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
 * This file defines the global mod_mumie settingspage. Here, the user can add, edit or delete mumie servers
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once ($CFG->dirroot . '/mod/mumie/locallib.php');

global $DB, $PAGE;

$mumieservers = mod_mumie\locallib::get_all_mumie_servers();

// Build html table containing all saved mumie servers.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index mumie_server_list_container';
$table->head = array(get_string("mumie_table_header_name", "mod_mumie"), get_string("mumie_table_header_url", "mod_mumie"),
    get_string("mumie_edit_button", "mod_mumie"), get_string("mumie_delete_button", "mod_mumie"));

foreach ($mumieservers as $server) {
    $id = "<span class='mumie_list_entry_id' hidden>" . $server->id . "</span>";
    $name = "<span class='mumie_list_entry_name'>" . $server->name . "</span>" . $id;
    $url = "<span class='mumie_list_entry_url'>" . $server->url_prefix . "</span>";
    $edit = "<a class = 'mumie_list_edit_button' title='" . get_string("mumie_edit_button", "mod_mumie") . "'>"
        . '<span class="icon fa fa-cog fa-fw " titel ="delete" aria-hidden="true" aria-label=""></span>'
        . "</a>";
    $deleteurl = "{$CFG->wwwroot}/mod/mumie/deletemumieserver.php?id={$server->id}&amp;sesskey={$USER->sesskey}";
    $delete = "<a class = 'mumie_list_delete_button' href='{$deleteurl}' title='"
    . get_string("mumie_delete_button", "mod_mumie")
        . "'>"
        . '<span class="icon fa fa-trash fa-fw " aria-hidden="true"></span>'
        . "</a>";
    $table->data[] = array($name, $url, $edit, $delete);
}

$addbutton = "<button class='btn mumie_add_server_button btn-primary' id='mumie_add_server_button'>"
. '<span class="icon fa fa-plus fa-fw " aria-hidden="true" aria-label=""></span>'
. get_string("mumie_add_server_button", "mod_mumie")
    . "</button>";

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_heading(
            'mumie_servers',
            get_string("mumie_server_list_heading", "mod_mumie"),
            html_writer::table($table) . $addbutton)
    );
}
$context = context_system::instance();
$PAGE->requires->js_call_amd('mod_mumie/settings', 'init', array(json_encode($context->id)));
