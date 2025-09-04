<?php
namespace local_referrals;

//use core\hook\output\before_standard_top_of_body_html;
use core\hook\after_config;
use core\hook\event\hook_event;

defined('MOODLE_INTERNAL') || die();

class hooks {
    /**
     * Capture referral params early and persist in session.
     */
public static function after_config(\core\hook\after_config $hook): void {
    // debugging
    // error_log('[local_referrals] REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? ''));
    // error_log('[local_referrals] QUERY_STRING=' . ($_SERVER['QUERY_STRING'] ?? ''));
    // error_log('[local_referrals] $_GET=' . json_encode($_GET));

    // Set cookie expiry to 2 hours from now
    $expiry = time() + 2 * 3600;

    // Only process if at least one referral parameter is present
    if (!empty($_GET['referralid']) || !empty($_GET['student_id']) || !empty($_GET['code'])) {

        // Sanitize inputs: only allow alphanumeric for referralid and coursecode
        if (!empty($_GET['referralid'])) {
            $referralid = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['referralid']);
            $current = isset($_COOKIE['referralidlocal']) ? $_COOKIE['referralidlocal'] : null;
            if ($current !== $referralid) {
                setcookie('referralidlocal', $referralid, $expiry, '/', '', true, true);
                $_COOKIE['referralidlocal'] = $referralid;
            }
        }

        if (!empty($_GET['student_id'])) {
            $studentid = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['student_id']);
            $current = isset($_COOKIE['student_idlocal']) ? $_COOKIE['student_idlocal'] : null;
            if ($current !== $studentid) {
                setcookie('student_idlocal', $studentid, $expiry, '/', '', true, true);
                $_COOKIE['student_idlocal'] = $studentid;
            }
        }

        if (!empty($_GET['code'])) {
            $coursecode = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['code']); // allow underscore
            $current = isset($_COOKIE['coursecodelocal']) ? $_COOKIE['coursecodelocal'] : null;
            if ($current !== $coursecode) {
                setcookie('coursecodelocal', $coursecode, $expiry, '/', '', true, true);
                $_COOKIE['coursecodelocal'] = $coursecode;
            }
        }
    }

    // Log the current cookie values for debugging
    //error_log('[local_referrals] Cookies after storing: ' . json_encode([
    //    'referralidlocal' => $_COOKIE['referralidlocal'] ?? null,
    //    'student_idlocal' => $_COOKIE['student_idlocal'] ?? null,
    //    'coursecodelocal' => $_COOKIE['coursecodelocal'] ?? null,
    //]));
 } // function after_config


} // class hooks
