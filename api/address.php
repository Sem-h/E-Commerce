<?php
/**
 * Adres AJAX Endpoint
 * GET ?action=provinces       → İl listesi
 * GET ?action=districts&city=İstanbul → İlçe listesi
 */
header('Content-Type: application/json; charset=utf-8');

$jsonFile = __DIR__ . '/../data/turkey-addresses.json';
$data = json_decode(file_get_contents($jsonFile), true);

$action = $_GET['action'] ?? '';

if ($action === 'provinces') {
    $provinces = array_keys($data);
    sort($provinces, SORT_LOCALE_STRING);
    echo json_encode($provinces);
    exit;
}

if ($action === 'districts') {
    $city = $_GET['city'] ?? '';
    $districts = $data[$city] ?? [];
    sort($districts, SORT_LOCALE_STRING);
    echo json_encode($districts);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
