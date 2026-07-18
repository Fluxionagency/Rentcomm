<?php
// Handles the free-report download form (Homepage + Report Page).
require __DIR__ . '/db.php';

[$name, $email, $whatsapp] = require_lead_fields();

$stmt = db()->prepare(
    'INSERT INTO report_leads (name, email, whatsapp, created_at) VALUES (?, ?, ?, NOW())'
);
$stmt->execute([$name, $email, $whatsapp]);

notify_team(
    'New report download lead: ' . $name,
    "The Lagos Rent Report 2026 was requested.\n\nName: $name\nEmail: $email\nWhatsApp: $whatsapp"
);

// TODO: attach the actual report PDF to a confirmation email to the lead once
// the final report file is available (drop it in /downloads and mail() it here).

json_response(200, ['ok' => true]);
