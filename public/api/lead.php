<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$allowedOrigins = array_filter(array_map('trim', explode(',', (string) Config::get('CORS_ALLOWED_ORIGINS', '*'))));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array('*', $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['success' => false, 'message' => 'Método no permitido'], 405);
}

$ip = Security::clientIp();
$maxAttempts = Config::int('RATE_LIMIT_MAX_ATTEMPTS', 10);
$windowSeconds = Config::int('RATE_LIMIT_WINDOW_SECONDS', 300);

if (!RateLimiter::allow('lead:' . $ip, $maxAttempts, $windowSeconds)) {
    Logger::info('Lead rate limit reached.', ['ip' => $ip]);
    Response::json(['success' => false, 'message' => 'Demasiados intentos. Inténtalo más tarde.'], 429);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if (str_contains(strtolower($contentType), 'application/json')) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        Response::json(['success' => false, 'message' => 'JSON no válido'], 400);
    }
    $input = $decoded;
} else {
    $input = $_POST;
}

// Honeypot: si un bot rellena este campo, respondemos éxito falso-positivo y no guardamos.
// No usamos `website` como honeypot porque puede ser un campo real del lead.
$honeypot = trim((string) ($input['_hp_website'] ?? $input['hp_field'] ?? ''));
if ($honeypot !== '') {
    Logger::info('Honeypot triggered.', ['ip' => $ip]);
    Response::json(['success' => true, 'message' => 'Lead recibido correctamente']);
}

$turnstileToken = $input['cf-turnstile-response'] ?? $input['turnstile_token'] ?? null;
if (!Turnstile::verify(is_string($turnstileToken) ? $turnstileToken : null, $ip)) {
    Response::json(['success' => false, 'message' => 'No se ha podido validar el formulario.'], 400);
}

[$isValid, $errors, $lead] = LeadValidator::validate($input);

if (!$isValid) {
    Response::json([
        'success' => false,
        'message' => 'Revisa los campos del formulario.',
        'errors' => $errors,
    ], 422);
}

try {
    $repository = new LeadRepository();
    $leadId = $repository->create($lead);

    $mail = new MailService();
    $mail->notifyNewLead($leadId, $lead);
    $mail->notifyN8n($leadId, $lead);

    Response::json([
        'success' => true,
        'message' => 'Lead recibido correctamente',
        'lead_id' => $leadId,
    ]);
} catch (Throwable $exception) {
    Logger::error('Lead creation failed.', ['error' => $exception->getMessage()]);
    Response::json(['success' => false, 'message' => 'No se ha podido procesar el formulario.'], 500);
}
