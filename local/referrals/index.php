<?php
require('../../config.php');

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/classes/filter_form.php');

require_login();

// Step 1: Instantiate the form and get validated data.
$form = new local_referrals_filter_form();
$data = $form->get_data();

// Initialize variables based on form data or URL parameters.
if ($data) {
    // If the form was submitted, use the cleaned data from the form.
    $fromdate = $data->fromdate;
    $todate = $data->todate;
    $download = $data->download;
} else {
    // If it's a new page load, get parameters directly from the URL.
    $fromdate = optional_param('fromdate', 0, PARAM_INT);
    $todate = optional_param('todate', 0, PARAM_INT);
    $download = optional_param('download', 0, PARAM_BOOL);

    // If no dates are set in the URL, calculate and set the previous month's dates as defaults.
    if (empty($fromdate) && empty($todate)) {
        // Get the first day of the current month.
        $firstdayofcurrentmonth = strtotime(date('Y-m-01'));

        // Subtract one day to get the last day of the previous month.
        $lastdayofpreviousmonth = strtotime('-1 day', $firstdayofcurrentmonth);

        // Get the first day of the previous month.
        $firstdayofpreviousmonth = strtotime(date('Y-m-01', $lastdayofpreviousmonth));

        $fromdate = $firstdayofpreviousmonth;
        $todate = $firstdayofcurrentmonth;
    }

    // Set form data for initial display (important for retaining filter values).
    $form->set_data(['fromdate' => $fromdate, 'todate' => $todate, 'download' => $download]);
}

// Set up the page, context, and capability check.
$PAGE->set_url(new moodle_url('/local/referrals/index.php', ['fromdate' => $fromdate, 'todate' => $todate]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_referrals'));
$PAGE->set_heading(get_string('pluginname', 'local_referrals'));

require_capability('moodle/site:config', context_system::instance());

global $DB, $OUTPUT, $PAGE;

// Step 2: Build the SQL query using the cleaned variables.
$sql = 'SELECT * FROM {local_referrals}';
$params = [];
$where = [];

if (!empty($fromdate)) {
    $where[] = 'timecreated >= :fromdate';
    $params['fromdate'] = $fromdate;
}
if (!empty($todate)) {
    $where[] = 'timecreated <= :todate';
    $params['todate'] = $todate; // The form handles adding the full day
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY timecreated ASC';

// Fetch the filtered data.
$records = $DB->get_records_sql($sql, $params);

// Step 5: Handle CSV download and exit
if ($download) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="referrals_report.csv"');
    $output = fopen('php://output', 'w');

    $headers = [
	get_string('slno', 'local_referrals'),
 	get_string('learneremailid', 'local_referrals'),
	get_string('learnername', 'local_referrals'),

	get_string('regdate', 'local_referrals'),
        get_string('coursecode', 'local_referrals'),
        get_string('coursename', 'local_referrals'),

	get_string('ncrfaligned', 'local_referrals'),
	get_string('coursetype', 'local_referrals'),
	get_string('paymentstatus', 'local_referrals'),
	get_string('coursecompletionstatus', 'local_referrals'),
	get_string('referralid', 'local_referrals')
    ];
    fputcsv($output, $headers);

    $serial = 1;

    foreach ($records as $r) {
	$user = $DB->get_record('user', ['id' => $r->userid], 'id, firstname, lastname, email');
	$course = $DB->get_record('course', ['id' => $r->courseid], 'id, fullname');

        $data = [
        $serial++,
        fullname($user), // Display the full name
        s($user->email), // Display the email

            format_string($course->fullname, true),
            s($r->referralid),
            s($r->studentid),
            s($r->coursecode),
            userdate($r->timecreated)
        ];
        fputcsv($output, $data);
    }
    fclose($output);
    exit;
}

// Start HTML output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_referrals'));

// Display the form.
$form->display();

// Display the download link.
$downloadurl = new moodle_url('/local/referrals/index.php', ['fromdate' => $fromdate, 'todate' => $todate, 'download' => 1]);
echo html_writer::link($downloadurl, get_string('downloadascsv', 'local_referrals'));

// Build and display the table
$table = new html_table();
$table->head = [
	get_string('slno', 'local_referrals'),
        get_string('learneremailid', 'local_referrals'),
        get_string('learnername', 'local_referrals'),

        get_string('regdate', 'local_referrals'),
        get_string('coursecode', 'local_referrals'),
        get_string('coursename', 'local_referrals'),

        get_string('ncrfaligned', 'local_referrals'),
        get_string('coursetype', 'local_referrals'),
        get_string('paymentstatus', 'local_referrals'),
        get_string('coursecompletionstatus', 'local_referrals'),
        get_string('coursecompletiondate', 'local_referrals'),
        get_string('certificatestatus', 'local_referrals'),
        get_string('certificateissueddate', 'local_referrals'),
        get_string('referralid', 'local_referrals')
	];
$serial = 1; // Initialize the counter

foreach ($records as $r) {
    $user = $DB->get_record('user', ['id' => $r->userid], 'id, firstname, lastname, email');
    $course = $DB->get_record('course', ['id' => $r->courseid], 'id, fullname');
    $table->data[] = [$serial++,
	 s($user->email),
	 fullname($user),
	 userdate($r->timecreated),
	 s($r->coursecode),
	 format_string($course->fullname),
	 'No',
	 'Free',
	 'NA',
	'  ',
	' ',
        '  ',
        ' ',
	 s($r->referralid)
	 ];
} 
echo html_writer::table($table);
echo $OUTPUT->footer();
