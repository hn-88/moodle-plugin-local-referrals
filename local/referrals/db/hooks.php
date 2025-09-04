<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook'     => \core\hook\after_config::class,
        'callback' => [\local_referrals\hooks::class, 'after_config'],
        'priority' => 500,
    ],
];
