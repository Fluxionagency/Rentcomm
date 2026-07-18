<?php
// Handles realtor registration (Realtor Journey). Creates the realtor plus a
// pending row in the invite-dispatch tracker.
require __DIR__ . '/db.php';

[$name, $email, $whatsapp] = require_lead_fields();

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO realtors (name, email, whatsapp, created_at) VALUES (?, ?, ?, NOW())'
    );
    $stmt->execute([$name, $email, $whatsapp]);
    $realtorId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "INSERT INTO dispatches (realtor_id, pack_status, visit_status) VALUES (?, 'Pending', 'Not Scheduled')"
    );
    $stmt->execute([$realtorId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    // Duplicate email registrations are fine from the realtor's perspective —
    // they still unlock the portal; the team just doesn't get a second row.
    if ($e instanceof PDOException && ($e->errorInfo[1] ?? 0) === 1062) {
        json_response(200, ['ok' => true]);
    }
    error_log('Rentcom realtor registration failed: ' . $e->getMessage());
    json_response(500, ['ok' => false, 'error' => 'Something went wrong. Please try again.']);
}

notify_team(
    'New realtor registration: ' . $name,
    "A realtor registered for the Fadayee campaign.\n\nName: $name\nEmail: $email\nWhatsApp: $whatsapp\n\nRemember to dispatch their physical invite pack."
);

json_response(200, ['ok' => true]);
