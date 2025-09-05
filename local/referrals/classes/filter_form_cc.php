<?php
defined('MOODLE_INTERNAL') || die();


class local_referrals_filter_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // Add a date selector for the "from" date.
        $mform->addElement('date_selector', 'fromdate', get_string('from', 'local_referrals'), ['start_date' => 0, 'allow_any' => true]);

        // Add a date selector for the "to" date.
        $mform->addElement('date_selector', 'todate', get_string('to', 'local_referrals'), ['start_date' => 0, 'allow_any' => true]);

        // Add a submit button.
        $mform->addElement('submit', 'submitbutton', get_string('filter'));

        // Add a hidden element for the download flag.
        $mform->addElement('hidden', 'download', 0);
        $mform->setType('download', PARAM_BOOL);
    }
}
