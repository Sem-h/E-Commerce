<?php
/**
 * V-Commerce - Genel Konfigürasyon
 */

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlama (production'da kapatılacak)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Proje kök dizini
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// BASE_URL otomatik algıla
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projRoot = str_replace('\\', '/', realpath(ROOT_PATH));
if ($docRoot && $projRoot && strpos($projRoot, $docRoot) === 0) {
    $base = substr($projRoot, strlen($docRoot));
} else {
    $base = '/E-Ticaret';
}
define('BASE_URL', rtrim($base, '/'));

// Dosya yolları
define('UPLOADS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOADS_URL', BASE_URL . '/assets/uploads');

// DB dahil et
require_once ROOT_PATH . 'config' . DIRECTORY_SEPARATOR . 'db.php';

// Functions dahil et
require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
