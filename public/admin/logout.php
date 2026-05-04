<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Auth::logout();
Response::redirect('/admin/login.php');
