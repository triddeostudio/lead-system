<?php

// Ejemplo para webs PHP existentes.
// Sustituye el antiguo mail() por una llamada HTTP al endpoint central.

$endpoint = 'https://leads.tudominio.com/api/lead.php';

$payload = [
    'source_site' => 'web-php-ejemplo',
    'source_url' => $_POST['source_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
    'form_name' => 'contacto_php',
    'name' => $_POST['name'] ?? $_POST['nombre'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? $_POST['telefono'] ?? '',
    'message' => $_POST['message'] ?? $_POST['mensaje'] ?? '',
    'consent' => !empty($_POST['consent']),
    'website' => $_POST['website'] ?? '', // honeypot
    'utm_source' => $_POST['utm_source'] ?? '',
    'utm_medium' => $_POST['utm_medium'] ?? '',
    'utm_campaign' => $_POST['utm_campaign'] ?? '',
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    error_log('Lead endpoint failed: HTTP ' . $httpCode . ' ' . $error . ' ' . substr((string) $response, 0, 500));
    echo 'No se ha podido enviar. Inténtalo más tarde.';
    exit;
}

echo 'Gracias. Hemos recibido tu mensaje.';
