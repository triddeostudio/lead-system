<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Auth::require();

$filters = [
    'status' => Security::cleanString($_GET['status'] ?? null, 40),
    'source_site' => Security::cleanString($_GET['source_site'] ?? null, 255),
    'form_name' => Security::cleanString($_GET['form_name'] ?? null, 120),
    'q' => Security::cleanString($_GET['q'] ?? null, 255),
];

$repository = new LeadRepository();
$leads = $repository->list($filters, 150);

$queryString = http_build_query(array_filter($filters));

function landing_label(?string $url): string
{
    if (!$url) {
        return '';
    }

    $path = parse_url($url, PHP_URL_PATH) ?: '/';
    $query = parse_url($url, PHP_URL_QUERY);

    return $query ? $path . '?' . $query : $path;
}

function external_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
        ? $url
        : 'https://' . $url;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leads</title>
    <link rel="stylesheet" href="/admin/assets/styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div>
                <h1>Leads</h1>
                <p class="small"><?= count($leads) ?> resultados visibles</p>
            </div>
            <div class="actions">
                <a class="button light" href="/admin/export.php?<?= Security::e($queryString) ?>">Exportar CSV</a>
                <a class="button secondary" href="/admin/logout.php">Salir</a>
            </div>
        </header>

        <section class="card">
            <form class="filters" method="get">
                <div>
                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        <?php foreach (['nuevo','contactado','cualificado','propuesta_enviada','ganado','perdido','spam','descartado'] as $status): ?>
                            <option value="<?= Security::e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= Security::e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="source_site">Web origen</label>
                    <input id="source_site" name="source_site" value="<?= Security::e($filters['source_site']) ?>" placeholder="daferrer.es">
                </div>
                <div>
                    <label for="form_name">Formulario</label>
                    <input id="form_name" name="form_name" value="<?= Security::e($filters['form_name']) ?>" placeholder="cta_contacto_automatizaciones">
                </div>
                <div>
                    <label for="q">Buscar</label>
                    <input id="q" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Nombre, email, teléfono, web, landing, campos extra...">
                </div>
                <button type="submit">Filtrar</button>
                <a class="button light" href="/admin/">Limpiar</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Contacto</th>
                        <th>Origen / landing</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Mensaje / extras</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <?php
                            $sourceUrl = (string) ($lead['source_url'] ?? '');
                            $clientWebsite = external_url($lead['client_website'] ?? '');
                            $extraFields = LeadFields::extractExtraFieldsFromRawPayload($lead['raw_payload'] ?? null);
                        ?>
                        <tr>
                            <td><?= Security::e(Time::format($lead['created_at'])) ?></td>
                            <td>
                                <a href="/admin/lead.php?id=<?= Security::e($lead['id']) ?>"><strong><?= Security::e($lead['name'] ?: 'Sin nombre') ?></strong></a><br>
                                <span class="small"><?= Security::e($lead['email']) ?></span><br>
                                <span class="small"><?= Security::e($lead['phone']) ?></span>
                                <?php if (!empty($lead['client_website'])): ?>
                                    <br><a class="small" href="<?= Security::e($clientWebsite) ?>" target="_blank" rel="noopener">🌐 <?= Security::e($lead['client_website']) ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= Security::e($lead['source_site'] ?: 'Sin origen') ?></strong><br>
                                <span class="small"><?= Security::e($lead['form_name']) ?></span><br>
                                <?php if ($sourceUrl !== ''): ?>
                                    <a class="small" href="<?= Security::e($sourceUrl) ?>" target="_blank" rel="noopener"><?= Security::e(landing_label($sourceUrl)) ?></a>
                                <?php else: ?>
                                    <span class="small">Sin URL de landing</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge"><?= Security::e($lead['status']) ?></span></td>
                            <td><span class="badge <?= Security::e($lead['priority']) ?>"><?= Security::e($lead['priority']) ?></span></td>
                            <td>
                                <?= Security::e(mb_substr((string) $lead['message'], 0, 160)) ?>
                                <?php if ($extraFields !== []): ?>
                                    <br><span class="small"><?= count($extraFields) ?> campo<?= count($extraFields) === 1 ? '' : 's' ?> adicional<?= count($extraFields) === 1 ? '' : 'es' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($leads === []): ?>
                        <tr><td colspan="6">No hay leads con esos filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
