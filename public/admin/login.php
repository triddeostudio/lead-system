<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Auth::start();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sesión caducada. Recarga la página.';
    } else {
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::login($username, $password)) {
            Response::redirect('/admin/');
        }

        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso | Leads</title>
    <link rel="stylesheet" href="/admin/assets/styles.css">
</head>
<body>
    <main class="login card">
        <h1>Panel de leads</h1>
        <p class="small">Acceso privado.</p>
        <?php if ($error): ?><div class="error"><?= Security::e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
            <div class="form-row">
                <label for="username">Usuario</label>
                <input id="username" name="username" autocomplete="username" required>
            </div>
            <div class="form-row">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
