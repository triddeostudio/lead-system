<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/Turnstile.php';
require_once __DIR__ . '/LeadRepository.php';
require_once __DIR__ . '/LeadValidator.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/Auth.php';

Config::load($rootPath);

if (Config::get('APP_ENV', 'production') === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
