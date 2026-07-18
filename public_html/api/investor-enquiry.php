<?php
// Handles investor enquiries (Investor Journey form + Portfolio Detail form).
require __DIR__ . '/db.php';

[$name, $email, $whatsapp] = require_lead_fields();

$source = trim($_POST['source'] ?? 'investor-journey');
if ($source === '' || mb_strlen($source) > 80) {
    $source = 'investor-journey';
}

$stmt = db()->prepare(
    'INSERT INTO investors (name, email, whatsapp, status, source, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
);
$stmt->execute([$name, $email, $whatsapp, 'New', $source]);

notify_team(
    'New investor enquiry: ' . $name,
    "An investor enquiry was submitted ($source).\n\nName: $name\nEmail: $email\nWhatsApp: $whatsapp"
);

json_response(200, ['ok' => true]);
