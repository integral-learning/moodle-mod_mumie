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
        
        $mform->addElement("hidden", "mumie");
        $mform->setType("mumie", PARAM_INT);
        
        $mform->addElement("hidden", "userid");
        $mform->setType("userid", PARAM_INT);

        $mform->addElement("hidden", "id");
        $mform->setType("id", PARAM_INT);
    }

    /**
     * Validate the form data
     * @param array $data form data
     * @param array $files files uploaded
     * @return array associative array of errors
     */
    public function validation($data, $files) {
        $errors = array();
        if ($data['duedate']) {
            if (time() - $data['duedate'] > 0) {
                $errors['duedate'] = get_string('mumie_form_due_date_must_be_future', 'mod_mumie');
            }
        }
        return $errors;
    }
}