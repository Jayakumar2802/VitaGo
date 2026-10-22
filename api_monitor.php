<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // Allow CORS for JS fetch
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = 'ecg_data.json';  // Local storage file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Receive and store ECG data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['ecg'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $newData = [
        'ecg' => (int)$input['ecg'],
        'leads_off' => (bool)($input['leads_off'] ?? false),  // Store leads_off status
        'received_at' => time()  // Server timestamp for sync
    ];

    // Load existing data
    $existingData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
    if (!is_array($existingData)) $existingData = [];

    // Append new data
    $existingData[] = $newData;

    // Keep only last 1000 readings to prevent file bloat (~8-10 min at 500ms intervals)
    if (count($existingData) > 1000) {
        $existingData = array_slice($existingData, -1000);
    }

    // Save back
    file_put_contents($dataFile, json_encode($existingData));

    echo json_encode(['status' => 'ok', 'points' => count($existingData)]);
} else {
    // GET: Return all data (for dashboard polling)
    if (file_exists($dataFile)) {
        $data = json_decode(file_get_contents($dataFile), true);
        echo json_encode($data ?: []);
    } else {
        echo json_encode([]);
    }
}
?>