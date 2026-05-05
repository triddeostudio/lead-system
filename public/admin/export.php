<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Auth::require();

$filters = [
    'status' => Security::cleanString($_GET['status'] ?? null, 40),
    'source_site' => Security::cleanString($_GET['source_site'] ?? null, 255),
    'q' => Security::cleanString($_GET['q'] ?? null, 255),
];

$repository = new LeadRepository();
$leads = $repository->list($filters, 500);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'fecha_madrid',
    'nombre',
    'email',
    'telefono',
    'empresa',
    'web_del_lead',
    'mensaje',
    'web_origen',
    'landing_exacta',
    'formulario',
    'estado',
    'prioridad',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
    'referrer',
    'ip',
    'user_agent',
    'consentimiento',
    'notas',
    'raw_payload',
]);

foreach ($leads as $lead) {
    fputcsv($output, [
        Time::format($lead['created_at'], 'Y-m-d H:i:s'),
        $lead['name'],
        $lead['email'],
        $lead['phone'],
        $lead['company'],
        $lead['client_website'],
        $lead['message'],
        $lead['source_site'],
        $lead['source_url'],
        $lead['form_name'],
        $lead['status'],
        $lead['priority'],
        $lead['utm_source'],
        $lead['utm_medium'],
        $lead['utm_campaign'],
        $lead['utm_term'],
        $lead['utm_content'],
        $lead['referrer'],
        $lead['ip_address'],
        $lead['user_agent'],
        $lead['consent'] ? 'sí' : 'no',
        $lead['notes'],
        $lead['raw_payload'],
    ]);
}

fclose($output);
exit;
