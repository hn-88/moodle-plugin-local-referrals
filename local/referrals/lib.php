<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Capture referral info from URL into session as early as possible.
 * This runs on every page load - callbacks are defined in db/hooks.php.
 */
