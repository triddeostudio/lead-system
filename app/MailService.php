<?php

declare(strict_types=1);

final class MailService
{
    public function notifyNewLead(string $leadId, array $lead): void
    {
        $provider = strtolower((string) Config::get('MAIL_PROVIDER', 'none'));
        $to = (string) Config::get('MAIL_TO', '');

        if ($to === '' || $provider === 'none') {
            return;
        }

        $subject = 'Nuevo lead: ' . ($lead['name'] ?: $lead['email'] ?: $lead['phone'] ?: 'sin nombre');
        $body = $this->buildLeadHtml($leadId, $lead);

        try {
            match ($provider) {
                'postmark' => $this->sendPostmark($to, $subject, $body),
                'resend' => $this->sendResend($to, $subject, $body),
                default => Logger::error('Unsupported mail provider.', ['provider' => $provider]),
            };
        } catch (Throwable $exception) {
            Logger::error('Lead email notification failed.', ['error' => $exception->getMessage(), 'lead_id' => $leadId]);
        }
    }

    public function notifyN8n(string $leadId, array $lead): void
    {
        $url = (string) Config::get('N8N_NEW_LEAD_WEBHOOK_URL', '');

        if ($url === '') {
            return;
        }

        try {
            $this->postJson($url, [
                'event' => 'lead_created',
                'lead_id' => $leadId,
                'lead' => $lead,
                'extra_fields' => LeadFields::extraFieldsToPlainArray($lead['extra_fields'] ?? []),
            ], []);
        } catch (Throwable $exception) {
            Logger::error('n8n webhook notification failed.', ['error' => $exception->getMessage(), 'lead_id' => $leadId]);
        }
    }

    private function sendPostmark(string $to, string $subject, string $html): void
    {
        $token = (string) Config::get('POSTMARK_SERVER_TOKEN', '');
        $from = (string) Config::get('MAIL_FROM', '');

        if ($token === '' || $from === '') {
            Logger::error('Postmark configuration is incomplete.');
            return;
        }

        $this->postJson('https://api.postmarkapp.com/email', [
            'From' => $from,
            'To' => $to,
            'Subject' => $subject,
            'HtmlBody' => $html,
            'MessageStream' => Config::get('POSTMARK_MESSAGE_STREAM', 'outbound'),
        ], [
            'X-Postmark-Server-Token: ' . $token,
        ]);
    }

    private function sendResend(string $to, string $subject, string $html): void
    {
        $apiKey = (string) Config::get('RESEND_API_KEY', '');
        $from = (string) Config::get('MAIL_FROM', '');

        if ($apiKey === '' || $from === '') {
            Logger::error('Resend configuration is incomplete.');
            return;
        }

        $this->postJson('https://api.resend.com/emails', [
            'from' => $from,
            'to' => array_map('trim', explode(',', $to)),
            'subject' => $subject,
            'html' => $html,
        ], [
            'Authorization: Bearer ' . $apiKey,
        ]);
    }

    private function postJson(string $url, array $payload, array $extraHeaders): void
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL is not available.');
        }

        $headers = array_merge(['Content-Type: application/json'], $extraHeaders);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            throw new RuntimeException('HTTP ' . $httpCode . ' ' . $error . ' ' . substr((string) $response, 0, 500));
        }
    }

    private function buildLeadHtml(string $leadId, array $lead): string
    {
        $panelBaseUrl = rtrim((string) Config::get('PANEL_BASE_URL', ''), '/');
        $leadUrl = $panelBaseUrl !== '' ? $panelBaseUrl . '/admin/lead.php?id=' . rawurlencode($leadId) : '';

        $rows = [
            'Nombre' => $lead['name'] ?? '',
            'Email' => $lead['email'] ?? '',
            'Teléfono' => $lead['phone'] ?? '',
            'Empresa' => $lead['company'] ?? '',
            'Web del lead' => $lead['client_website'] ?? '',
            'Mensaje' => nl2br(Security::e($lead['message'] ?? '')),
            'Web origen' => $lead['source_site'] ?? '',
            'URL origen' => $lead['source_url'] ?? '',
            'Formulario' => $lead['form_name'] ?? '',
            'UTM source' => $lead['utm_source'] ?? '',
            'UTM medium' => $lead['utm_medium'] ?? '',
            'UTM campaign' => $lead['utm_campaign'] ?? '',
            'Prioridad' => $lead['priority'] ?? '',
        ];

        $html = '<h2>Nuevo lead recibido</h2><table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse">';
        foreach ($rows as $label => $value) {
            $safeValue = $label === 'Mensaje' ? (string) $value : Security::e((string) $value);
            $html .= '<tr><th align="left">' . Security::e($label) . '</th><td>' . $safeValue . '</td></tr>';
        }

        $extraFields = $lead['extra_fields'] ?? [];
        if (is_array($extraFields) && $extraFields !== []) {
            $html .= '<tr><th colspan="2" align="left" style="background:#f6f7f9">Campos adicionales</th></tr>';
            foreach ($extraFields as $field) {
                $label = (string) ($field['label'] ?? 'Campo adicional');
                $value = LeadFields::valueToString($field['value'] ?? '');
                $html .= '<tr><th align="left">' . Security::e($label) . '</th><td>' . nl2br(Security::e($value)) . '</td></tr>';
            }
        }

        $html .= '</table>';

        if ($leadUrl !== '') {
            $html .= '<p><a href="' . Security::e($leadUrl) . '">Ver lead en el panel</a></p>';
        }

        return $html;
    }
}
