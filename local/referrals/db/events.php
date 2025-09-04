<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_referrals\observer::on_user_enrolled',
        'includefile' => '/local/referrals/classes/observer.php',
        'priority'    => 500,
        'internal'    => false,
    ],
];
