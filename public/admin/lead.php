<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Auth::require();

$id = (string) ($_GET['id'] ?? '');
$repository = new LeadRepository();
$lead = $repository->find($id);

if (!$lead) {
    http_response_code(404);
    echo 'Lead no encontrado';
    exit;
}

$events = $repository->events($id);

function detail_external_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
        ? $url
        : 'https://' . $url;
}

$rawPayload = $lead['raw_payload'] ?? '';
$decodedPayload = [];
$prettyPayload = '';
if ($rawPayload) {
    $decoded = json_decode((string) $rawPayload, true);
    $decodedPayload = is_array($decoded) ? $decoded : [];
    $prettyPayload = json_encode($decodedPayload ?: $rawPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$extraFields = LeadFields::extractExtraFieldsFromRawPayload($decodedPayload);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lead | <?= Security::e($lead['name'] ?: $lead['email'] ?: $lead['phone']) ?></title>
    <link rel="stylesheet" href="/admin/assets/styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div>
                <p><a href="/admin/">← Volver</a></p>
                <h1><?= Security::e($lead['name'] ?: 'Lead sin nombre') ?></h1>
                <p class="small">Recibido el <?= Security::e(Time::format($lead['created_at'])) ?></p>
            </div>
            <a class="button secondary" href="/admin/logout.php">Salir</a>
        </header>

        <div class="grid">
            <section class="card">
                <h2>Datos del lead</h2>
                <dl class="meta">
                    <dt>Email</dt><dd><?= Security::e($lead['email']) ?></dd>
                    <dt>Teléfono</dt><dd><?= Security::e($lead['phone']) ?></dd>
                    <dt>Empresa</dt><dd><?= Security::e($lead['company']) ?></dd>
                    <dt>Web del lead</dt>
                    <dd>
                        <?php if (!empty($lead['client_website'])): ?>
                            <a href="<?= Security::e(detail_external_url($lead['client_website'])) ?>" target="_blank" rel="noopener"><?= Security::e($lead['client_website']) ?></a>
                        <?php endif; ?>
                    </dd>
                    <dt>Web origen</dt><dd><?= Security::e($lead['source_site']) ?></dd>
                    <dt>Landing exacta</dt>
                    <dd>
                        <?php if (!empty($lead['source_url'])): ?>
                            <a href="<?= Security::e($lead['source_url']) ?>" target="_blank" rel="noopener"><?= Security::e($lead['source_url']) ?></a>
                        <?php endif; ?>
                    </dd>
                    <dt>Ruta</dt><dd><?= Security::e((string) ($decodedPayload['source_path'] ?? '')) ?></dd>
                    <dt>Título página</dt><dd><?= Security::e((string) ($decodedPayload['page_title'] ?? '')) ?></dd>
                    <dt>Formulario</dt><dd><?= Security::e($lead['form_name']) ?></dd>
                    <dt>Referrer</dt>
                    <dd>
                        <?php if (!empty($lead['referrer'])): ?>
                            <a href="<?= Security::e($lead['referrer']) ?>" target="_blank" rel="noopener"><?= Security::e($lead['referrer']) ?></a>
                        <?php endif; ?>
                    </dd>
                    <dt>UTM source</dt><dd><?= Security::e($lead['utm_source']) ?></dd>
                    <dt>UTM medium</dt><dd><?= Security::e($lead['utm_medium']) ?></dd>
                    <dt>UTM campaign</dt><dd><?= Security::e($lead['utm_campaign']) ?></dd>
                    <dt>UTM term</dt><dd><?= Security::e($lead['utm_term']) ?></dd>
                    <dt>UTM content</dt><dd><?= Security::e($lead['utm_content']) ?></dd>
                    <dt>IP</dt><dd><?= Security::e($lead['ip_address']) ?></dd>
                    <dt>User agent</dt><dd><?= Security::e($lead['user_agent']) ?></dd>
                    <dt>Consentimiento</dt><dd><?= $lead['consent'] ? 'Sí' : 'No' ?></dd>
                </dl>

                <h2>Mensaje</h2>
                <div class="message card"><?= nl2br(Security::e($lead['message'])) ?></div>

                <?php if ($extraFields !== []): ?>
                    <h2>Campos adicionales</h2>
                    <dl class="meta">
                        <?php foreach ($extraFields as $field): ?>
                            <dt><?= Security::e((string) ($field['label'] ?? 'Campo')) ?></dt>
                            <dd><?= nl2br(Security::e(LeadFields::valueToString($field['value'] ?? ''))) ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>

                <?php if ($prettyPayload): ?>
                    <h2>Datos completos recibidos</h2>
                    <div class="message card"><pre style="white-space:pre-wrap;word-break:break-word;margin:0"><?= Security::e($prettyPayload) ?></pre></div>
                <?php endif; ?>
            </section>

            <aside class="card">
                <h2>Gestión</h2>
                <form method="post" action="/admin/update-lead.php">
                    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= Security::e($lead['id']) ?>">

                    <div class="form-row">
                        <label for="status">Estado</label>
                        <select id="status" name="status" required>
                            <?php foreach (['nuevo','contactado','cualificado','propuesta_enviada','ganado','perdido','spam','descartado'] as $status): ?>
                                <option value="<?= Security::e($status) ?>" <?= $lead['status'] === $status ? 'selected' : '' ?>><?= Security::e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="priority">Prioridad</label>
                        <select id="priority" name="priority" required>
                            <?php foreach (['baja','media','alta','urgente'] as $priority): ?>
                                <option value="<?= Security::e($priority) ?>" <?= $lead['priority'] === $priority ? 'selected' : '' ?>><?= Security::e($priority) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="assigned_to">Asignado a</label>
                        <input id="assigned_to" name="assigned_to" value="<?= Security::e($lead['assigned_to']) ?>">
                    </div>

                    <div class="form-row">
                        <label for="next_action_at">Próxima acción</label>
                        <input id="next_action_at" name="next_action_at" type="datetime-local" value="<?= $lead['next_action_at'] ? Security::e(Time::datetimeLocal($lead['next_action_at'])) : '' ?>">
                    </div>

                    <div class="form-row">
                        <label for="notes">Notas internas</label>
                        <textarea id="notes" name="notes"><?= Security::e($lead['notes']) ?></textarea>
                    </div>

                    <button type="submit">Guardar cambios</button>
                </form>
            </aside>
        </div>

        <section class="card" style="margin-top:18px">
            <h2>Histórico</h2>
            <table>
                <thead>
                    <tr><th>Fecha</th><th>Evento</th><th>Usuario</th><th>Datos</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?= Security::e(Time::format($event['created_at'])) ?></td>
                            <td><?= Security::e($event['event_type']) ?></td>
                            <td><?= Security::e($event['created_by']) ?></td>
                            <td><code><?= Security::e($event['event_data']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
