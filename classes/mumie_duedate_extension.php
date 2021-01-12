<?php

namespace mod_mumie;
class mumie_duedate_extension {
    private $id;
    private $userid;
    private $mumie;
    private $duedate;

    const MUMIE_DUEDATE_TABLE = "mumie_duedate" ;

    function __construct($userid, $mumie) {
        $this->userid = $userid;
        $this->mumie = $mumie;
    }

    public function load() {
        global $DB;
        if($record = $DB->get_record("mumie_duedate", array("userid" => $this->userid, "mumie" => $this->mumie))) {
            $this->duedate = $record->duedate;
            $this->id = $record->id;
        }
    }

    public function upsert() {
        global $DB;
        if (isset($this->id) && $this->id > 0) {
            $this->update();
        } else {
            $this->create();
        }
    }

    private function create() {
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

    private function update() {
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

    private function delete($id) {
        global $DB;
        $DB->delete_records(self::MUMIE_DUEDATE_TABLE, array("id" => $this->id));
    }

    public static function from_object($object) {
        $duedate = new mumie_duedate_extension();
        $duedate->set_mumie($object->mumie);
        $duedate->set_userid($object->userid);
        $duedate->set_duedate($object->duedate);
        if ($object->id != 0) {
            $duedate->set_id($object->id);
        }
        return $duedate;
    }

    public static function delete_by_id($id) {
        $duedate = new mumie_duedate_extension();
        $duedate->set_id($id);
        $duedate->delete();
    }

    /**
     * Get the value of id
     */ 
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function set_id($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of userid
     */ 
    public function get_userid()
    {
        return $this->userid;
    }

    /**
     * Set the value of userid
     *
     * @return  self
     */ 
    public function set_userid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get the value of mumie
     */ 
    public function get_mumie()
    {
        return $this->mumie;
    }

    /**
     * Set the value of mumie
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
     */ 
    public function get_duedate()
    {
        return $this->duedate;
    }

    /**
     * Set the value of duedate
     *
     * @return  self
     */ 
    public function set_duedate($duedate)
    {
        $this->duedate = $duedate;

        return $this;
    }
}