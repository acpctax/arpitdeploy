<?php
header('Content-Type: application/json');

$RESPONSES_DIR = __DIR__ . '/responses';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

if (!is_dir($RESPONSES_DIR)) {
    mkdir($RESPONSES_DIR, 0755, true);
}

$htaccess = $RESPONSES_DIR . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

$timestamp = date('Y-m-d_H-i-s');
$filename = "arpit-survey-{$timestamp}.json";
$filepath = $RESPONSES_DIR . '/' . $filename;

$written = file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($written === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to save']);
    exit;
}

echo json_encode([
    'ok' => true,
    'file' => $filename,
    'submitted_at' => $data['submitted_at'] ?? date('c'),
]);
