<?php
/**
 * Konfiguračný súbor pre systém správy pôžičiek
 *
 * DÔLEŽITÉ: Skopírujte tento súbor ako config.local.php a upravte hodnoty podľa vášho prostredia.
 * Súbor config.local.php by nemal byť vo verzionovacom systéme (pridajte do .gitignore)
 */

// Načítanie lokálnej konfigurácie ak existuje
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    // Predvolené hodnoty pre development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'loan_management');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// Session konfigurácia
define('SESSION_NAME', 'loan_mgmt_session');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 dní

// Aplikačné nastavenia
define('APP_NAME', 'Systém správy pôžičiek');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Europe/Bratislava');

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_SECURE', false); // Nastaviť na true pre HTTPS
define('SESSION_HTTPONLY', true);

// Nastavenie časovej zóny
date_default_timezone_set(TIMEZONE);

// Error reporting (vypnúť v produkcii)
error_reporting(E_ALL);
ini_set('display_errors', 1);
