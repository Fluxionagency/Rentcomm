<?php
// Rentcom — shared DB + request helpers for the API endpoints and admin portal.

function rentcom_config(): array
{
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/config.php';
        if (!file_exists($path)) {
            json_response(500, ['ok' => false, 'error' => 'Server not configured yet.']);
        }
        $config = require $path;
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = rentcom_config();
        try {
            $pdo = new PDO(
                "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",
                $c['db_user'],
                $c['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('Rentcom DB connection failed: ' . $e->getMessage());
            json_response(500, ['ok' => false, 'error' => 'Something went wrong. Please try again later.']);
        }
    }
    return $pdo;
}

function json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

/**
 * Validate the standard lead fields (name/email/whatsapp) plus the honeypot.
 * Returns the cleaned values, or sends a 4xx JSON error and exits.
 */
function require_lead_fields(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(405, ['ok' => false, 'error' => 'Method not allowed.']);
    }

    // Honeypot: real users never fill this hidden field. Pretend success so
    // bots don't learn they were caught.
    if (!empty($_POST['website'])) {
        json_response(200, ['ok' => true]);
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');

    if ($name === '' || mb_strlen($name) > 120) {
        json_response(422, ['ok' => false, 'error' => 'Please enter your name.']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        json_response(422, ['ok' => false, 'error' => 'Please enter a valid email address.']);
    }
    if (!preg_match('/^[+0-9][0-9 ()\-]{6,24}$/', $whatsapp)) {
        json_response(422, ['ok' => false, 'error' => 'Please enter a valid WhatsApp number.']);
    }

    return [$name, $email, $whatsapp];
}

/** Fire-and-forget email notification to the team; failures never block the lead. */
function notify_team(string $subject, string $body): void
{
    $c = rentcom_config();
    if (empty($c['notify_email'])) {
        return;
    }
    $headers = 'From: Rentcom Website <' . $c['mail_from'] . ">\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($c['notify_email'], $subject, $body, $headers);
}
