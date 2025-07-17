<?php
require_once '../config.php';
header('Content-Type: application/json');
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$rows = $data['rows'] ?? [];

if ($type === 'api_master') {
    $stmt = $pdo->prepare("REPLACE INTO api_master (id, api_no, name, url, sap_user, sap_pass) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($rows as $row) {
        $stmt->execute([$row['id'] ?? null, $row['api_no'], $row['name'], $row['url'], $row['sap_user'], $row['sap_pass']]);
    }
} elseif ($type === 'module_map') {
    $stmt = $pdo->prepare("REPLACE INTO module_map (id, module, required_api_nos) VALUES (?, ?, ?)");
    foreach ($rows as $row) {
        $stmt->execute([$row['id'] ?? null, $row['module'], $row['required_api_nos']]);
    }
} else {
    echo json_encode(['error' => 'Invalid table type.']);
    exit;
}
echo json_encode(['success' => true]);
