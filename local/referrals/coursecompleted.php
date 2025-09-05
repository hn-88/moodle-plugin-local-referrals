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
$PAGE->set_url(new moodle_url('/local/referrals/coursecompleted.php', ['fromdate' => $fromdate, 'todate' => $todate]));
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'local_referrals'));
$PAGE->set_title('Earlier referrals who completed courses in this interval');

require_capability('moodle/site:config', context_system::instance());

global $DB, $OUTPUT, $PAGE;

// --- Main SQL Query Logic ---
// Select all course completions that fall within the filtered date range.
// LEFT JOIN with local_referrals to include referral data if available.
// The WHERE clause filters completions by date and also ensures the referral time is before the filtered range.
$sql = 'SELECT cc.userid, cc.course AS courseid, cc.timecompleted AS completiondate, lr.id, lr.referralid, lr.studentid, lr.coursecode, lr.timecreated FROM {course_completions} cc LEFT JOIN {local_referrals} lr ON cc.userid = lr.userid AND cc.course = lr.courseid';
$params = [];
$where = [];

// Filter by completion date range.
if (!empty($fromdate)) {
    $where[] = 'cc.timecompleted >= :fromdate';
    $params['fromdate'] = $fromdate;
}
if (!empty($todate)) {
    $where[] = 'cc.timecompleted <= :todate';
    $params['todate'] = $todate;
}

// Add the condition that referral time must be before the completion date range.
$where[] = '(lr.timecreated IS NULL OR lr.timecreated < :referraldate)';
$params['referraldate'] = $fromdate;

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY cc.timecompleted ASC';

// Fetch the filtered data.
$records = $DB->get_records_sql($sql, $params);

// Step 5: Handle CSV download and exit
if ($download) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="referrals_report_cc.csv"');
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
        get_string('coursecompletiondate', 'local_referrals'),
        get_string('certificatestatus', 'local_referrals'),
        get_string('certificateissueddate', 'local_referrals'),
	get_string('referralid', 'local_referrals')
    ];
    fputcsv($output, $headers);

    $serial = 1;

    foreach ($records as $r) {
	$user = $DB->get_record('user', ['id' => $r->userid], 'id, firstname, lastname, email');
	$course = $DB->get_record('course', ['id' => $r->courseid], 'id, fullname');

	// Determine completion status and date
	 $completionstatus = !empty($r->completiondate) ? 'Yes' : 'No';
 	 $completiondate = !empty($r->completiondate) ? date('d/m/Y', $r->completiondate) : '';

        $data = [
         $serial++,
         s($user->email),
         fullname($user),
         date('d/m/Y', $r->timecreated),
         s($r->coursecode),
         format_string($course->fullname),
         'No',
         'Free',
         'NA',
         $completionstatus,
         $completiondate,
         $completionstatus,
         $completiondate,
         s($r->referralid)
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
$downloadurl = new moodle_url('/local/referrals/coursecompleted.php', ['fromdate' => $fromdate, 'todate' => $todate, 'download' => 1]);
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

    // Determine completion status and date
     $completionstatus = !empty($r->completiondate) ? 'Yes' : 'No';
     $completiondate = !empty($r->completiondate) ? date('d/m/Y', $r->completiondate) : '';

    $table->data[] = [$serial++,
	 s($user->email),
	 fullname($user),
	 date('d/m/Y', $r->timecreated),
	 s($r->coursecode),
	 format_string($course->fullname),
	 'No',
	 'Free',
	 'NA',
	 $completionstatus,
	 $completiondate,
         $completionstatus,
         $completiondate,
	 s($r->referralid)
	 ];
} 
echo html_writer::table($table);
echo $OUTPUT->footer();
