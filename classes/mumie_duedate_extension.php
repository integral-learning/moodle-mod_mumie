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
 * This file describes a class used to execute CRUD operations for individual due dates for students.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;


/**
 * A MUMIE due date extensions is an due date extension for a single student. These objects must be saved in the database.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_duedate_extension {
    /**
     * Primary key for object in the database.
     *
     * @var int
     */
    private $id;
        
    /**
     * ID the student who was granted an extension.
     *
     * @var int
     */
    private $userid;
        
    /**
     * Id of the MUMIE activity the extension was granted for.
     *
     * @var int
     */
    private $mumie;
        
    /**
     * Timestamp of the due date.
     *
     * @var int
     */
    private $duedate;

    const MUMIE_DUEDATE_TABLE = "mumie_duedate" ;

    /**
     * Constructor.
     *
     * @param  int $userid The user who is granted an extension.
     * @param  int $mumie The activity the extension is granted for.
     * @return void
     */
    public function __construct($userid = null, $mumie = null) {
        $this->userid = $userid;
        $this->mumie = $mumie;
    }
    
    /**
     * Load an entry from the database.
     *
     * @return void
     */
    public function load() {
        global $DB;
        if ($record = $DB->get_record("mumie_duedate", array("userid" => $this->userid, "mumie" => $this->mumie))) {
            $this->duedate = $record->duedate;
            $this->id = $record->id;
        }
    }
    
    /**
     * Upsert the due date in the database.
     *
     * @return void
     */
    public function upsert() {
        global $DB;
        if (isset($this->id) && $this->id > 0) {
            $this->update();
        } else {
            $this->create();
        }
    }
    
    /**
     * Update an existing record.
     *
     * @return void
     */
    private function update() {
        global $DB;
        $DB->update_record(
            self::MUMIE_DUEDATE_TABLE, 
            array(
                "id" => $this->id,
                "userid" => $this->userid,
                "mumie" => $this->mumie,
                "duedate" => $this->duedate
            )
        );
    }
    
    /**
     * Create a new record in the database.
     *
     * @return void
     */
    private function create() {
        global $DB;
        $DB->insert_record(
            self::MUMIE_DUEDATE_TABLE, 
            array(
                "userid" => $this->userid,
                "mumie" => $this->mumie,
                "duedate" => $this->duedate
            )
        );
    }
    
    /**
     * Delete a record from the database.
     *
     * @return void
     */
    private function delete() {
        global $DB;
        $DB->delete_records(self::MUMIE_DUEDATE_TABLE, array("id" => $this->id));
    }
    
    /**
     * Construct class instance from stdClass.
     *
     * @param  \stdClass $object
     * @return mumie_duedate_extension
     */
    public static function from_object($object) {        
        $duedate = new mumie_duedate_extension($object->userid, $object->mumie);
        $duedate->set_duedate($object->duedate);
        if ($object->id != 0) {
            $duedate->set_id($object->id);
        }
        return $duedate;
    }
    
    /**
     * Delete a mumie_duedate_extension by id.
     *
     * @param  int $id of the entry we want to delete.
     * @return void
     */
    public static function delete_by_id($id) {
        $duedate = new mumie_duedate_extension();
        $duedate->set_id($id);
        $duedate->delete();
    }
    
    /**
     * Get the effective duedate for a student.
     * 
     * Individual due date extensions always overrule general due date settings.
     *
     * @param  int $userid
     * @param  \stdClass $mumie
     * @return int
     */
    public static function get_effective_duedate($userid, $mumie) {
        $extension = new mumie_duedate_extension($userid, $mumie->id);
        $extension->load();
        if ($extension->get_duedate()) {
            return $extension->get_duedate();
        }
        return $mumie->duedate;
    }
        
    /**
     * Get id.
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set the value of id.
     *
     * @param  int $id
     * @return self
     */
    public function set_id($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of userid.
     *
     * @return int
     */
    public function get_userid()
    {
        return $this->userid;
    }

    /**
     * Set the value of userid.
     *
     * @param  int $userid
     * @return self
     */
    public function set_userid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get the value of mumie.
     *
     * @return int
     */
    public function get_mumie()
    {
        return $this->mumie;
    }

    /**
     * Set the value of mumie.
     *
     * @return  self
     */ 
    public function set_mumie($mumie)
    {
        $this->mumie = $mumie;

        return $this;
    }

    /**
     * Get the value of duedate
     *
     * @return int
     */
    public function get_duedate()
    {
        return $this->duedate;
    }

    /**
     * Set the value of duedate.
     *
     * @param  int $duedate
     * @return void
     */
    public function set_duedate($duedate)
    {
        $this->duedate = $duedate;

        return $this;
    }
}