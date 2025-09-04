<?php
namespace local_referrals;
defined('MOODLE_INTERNAL') || die();

class observer {

public static function on_user_enrolled(\core\event\user_enrolment_created $event) {
    global $DB;

    //error_log('[local_referrals] function on_user_enrolled() ... ');

    $userid = $event->relateduserid ?? $event->objectid ?? null; // enrolled user
    $courseid = $event->courseid ?? null; // Moodle numeric course id

    if (!$userid || !$courseid) {
        return; // safety check
    }

    // Retrieve referral info from cookies
    $referralid = $_COOKIE['referralidlocal'] ?? null;
    $studentid = $_COOKIE['student_idlocal'] ?? null;
    $coursecode = $_COOKIE['coursecodelocal'] ?? null;

    if (!$referralid) {
        // No referral, skip
        return;
    }

    // Insert into your local_referrer table
    $record = new \stdClass();
    $record->userid = $userid;
    $record->referralid = $referralid;
    $record->studentid = $studentid;
    $record->courseid = $courseid;
    $record->coursecode = $coursecode;
    $record->timecreated = time();

    try {
        $DB->insert_record('local_referrals', $record, false); // false => no overwrite
    } catch (\Exception $e) {
        debugging('[local_referrals] Error inserting referral: ' . $e->getMessage());
        // error_log('[local_referrals] Error inserting referral: ' . $e->getMessage());

    }

    // Optionally, clear cookies after insertion
    setcookie('referralidlocal', '', time() - 3600, '/', '', true, true);
    setcookie('student_idlocal', '', time() - 3600, '/', '', true, true);
    setcookie('coursecodelocal', '', time() - 3600, '/', '', true, true);

} // function on_user_renrolled

} // class observer
