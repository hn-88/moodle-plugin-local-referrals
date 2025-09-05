<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage(
        'local_referrals_report',
        get_string('pluginname', 'local_referrals'),
        new moodle_url('/local/referrals/index.php')
    ));
    $ADMIN->add('reports', new admin_externalpage(
        'local_referrals_completion_report',
        get_string('completionreport', 'local_referrals'),
        new moodle_url('/local/referrals/coursecompleted.php')
    ));
}
