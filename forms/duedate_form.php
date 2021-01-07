<?php

require_once($CFG->dirroot.'/lib/formslib.php');
class duedate_form extends moodleform {
    /**
     * Define fields and default values for the mumie server form
     * @return void
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('date_time_selector', 'duedate', get_string("mumie_due_date", "mod_mumie"));
        $this->add_action_buttons();

        $mform->addElement("hidden", "mumie");
        $mform->setType("mumie", PARAM_INT);

        $mform->addElement("hidden", "userid");
        $mform->setType("userid", PARAM_INT);

        $mform->addElement("hidden", "cmid");
        $mform->setType("cmid", PARAM_INT);
    }

    /**
     * Validate the form data
     * @param array $data form data
     * @param array $files files uploaded
     * @return array associative array of errors
     */
    public function validation($data, $files) {
    }
}