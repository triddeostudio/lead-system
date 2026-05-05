<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::redirect('/admin/');
}

if (!Security::verifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo 'CSRF inválido';
    exit;
}

$id = (string) ($_POST['id'] ?? '');
$status = (string) ($_POST['status'] ?? 'nuevo');
$priority = (string) ($_POST['priority'] ?? 'media');

if (!in_array($status, ['nuevo','contactado','cualificado','propuesta_enviada','ganado','perdido','spam','descartado'], true)) {
    $status = 'nuevo';
}

if (!in_array($priority, ['baja','media','alta','urgente'], true)) {
    $priority = 'media';
}

$nextActionInput = Security::cleanString($_POST['next_action_at'] ?? null, 40);
$nextAction = Time::fromLocalInput($nextActionInput);

$repository = new LeadRepository();
$repository->update($id, [
    'status' => $status,
    'priority' => $priority,
    'assigned_to' => Security::cleanString($_POST['assigned_to'] ?? null, 200),
    'next_action_at' => $nextAction,
    'notes' => Security::cleanString($_POST['notes'] ?? null, 8000),
], Auth::user());

Response::redirect('/admin/lead.php?id=' . rawurlencode($id));
