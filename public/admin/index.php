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
$leads = $repository->list($filters, 150);

$queryString = http_build_query(array_filter($filters));
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
                    <input id="source_site" name="source_site" value="<?= Security::e($filters['source_site']) ?>">
                </div>
                <div>
                    <label for="q">Buscar</label>
                    <input id="q" name="q" value="<?= Security::e($filters['q']) ?>" placeholder="Nombre, email, teléfono, mensaje...">
                </div>
                <button type="submit">Filtrar</button>
                <a class="button light" href="/admin/">Limpiar</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Contacto</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= Security::e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></td>
                            <td>
                                <a href="/admin/lead.php?id=<?= Security::e($lead['id']) ?>"><strong><?= Security::e($lead['name'] ?: 'Sin nombre') ?></strong></a><br>
                                <span class="small"><?= Security::e($lead['email']) ?></span><br>
                                <span class="small"><?= Security::e($lead['phone']) ?></span>
                            </td>
                            <td>
                                <?= Security::e($lead['source_site']) ?><br>
                                <span class="small"><?= Security::e($lead['form_name']) ?></span>
                            </td>
                            <td><span class="badge"><?= Security::e($lead['status']) ?></span></td>
                            <td><span class="badge <?= Security::e($lead['priority']) ?>"><?= Security::e($lead['priority']) ?></span></td>
                            <td><?= Security::e(mb_substr((string) $lead['message'], 0, 160)) ?></td>
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
