<?php
// Rentcom — configuration template.
// Copy this file to config.php and fill in your Qserver/cPanel MySQL details.
// config.php is gitignored — never commit real credentials.

return [
    // MySQL (create the database + user in cPanel, then run schema.sql)
    'db_host' => 'localhost',
    'db_name' => 'rentcom_site',
    'db_user' => 'rentcom_user',
    'db_pass' => 'CHANGE-ME',

    // Where lead notifications are sent (uses PHP mail(); most cPanel hosts
    // support it out of the box). Leave empty to disable email notifications.
    'notify_email' => 'team@rentcom.com',

    // From-address for outgoing notifications (use a mailbox on your own
    // domain so shared-hosting mail isn't flagged as spoofed).
    'mail_from' => 'no-reply@rentcom.com',
];
