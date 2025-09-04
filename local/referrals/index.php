<?php
require('../../config.php');

require_login();

// Get filter and download parameters from the request.
$fromdate = optional_param('fromdate', 0, PARAM_INT);
$todate = optional_param('todate', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);

// Corrected lines
$PAGE->set_url(new moodle_url('/local/referrals/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_referrals'));
$PAGE->set_heading(get_string('pluginname', 'local_referrals'));

// Add a capability check for a more secure approach
require_capability('moodle/site:config', context_system::instance());

global $DB, $OUTPUT, $PAGE;

// Fetch data from our table.
$records = $DB->get_records('local_referrals', null, 'timecreated DESC', '*');

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_referrals'));

// Build a simple table.
$table = new html_table();
$table->head = [
    get_string('user'),
    get_string('course'),
    get_string('referralid', 'local_referrals'),
    get_string('studentid', 'local_referrals'),
    get_string('coursecode', 'local_referrals'),
    get_string('time')
];

foreach ($records as $r) {
    $user   = $DB->get_record('user', ['id' => $r->userid], 'id, firstname, lastname');
    $course = $DB->get_record('course', ['id' => $r->courseid], 'id, fullname');

    $table->data[] = [
        fullname($user),
        format_string($course->fullname),
        s($r->referralid),
        s($r->studentid),
        s($r->coursecode),
        userdate($r->timecreated)
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
