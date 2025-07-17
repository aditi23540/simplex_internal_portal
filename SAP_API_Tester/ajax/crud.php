<?php
require_once '../config.php';
header('Content-Type: application/json');
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$table = $data['table'] ?? '';
$values = $data['values'] ?? [];

function runQuery($pdo, $query, $params) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

if ($table === 'api_master') {
    if ($action === 'add') {
        runQuery($pdo, "INSERT INTO api_master (api_no, name, url, sap_user, sap_pass) VALUES (?, ?, ?, ?, ?)", array_values($values));
    } elseif ($action === 'edit') {
        runQuery($pdo, "UPDATE api_master SET api_no=?, name=?, url=?, sap_user=?, sap_pass=? WHERE id=?", array_values($values));
    } elseif ($action === 'delete') {
        runQuery($pdo, "DELETE FROM api_master WHERE id=?", [$values['id']]);
    }
} elseif ($table === 'module_map') {
    if ($action === 'add') {
        runQuery($pdo, "INSERT INTO module_map (module, required_api_nos) VALUES (?, ?)", array_values($values));
    } elseif ($action === 'edit') {
        runQuery($pdo, "UPDATE module_map SET module=?, required_api_nos=? WHERE id=?", array_values($values));
    } elseif ($action === 'delete') {
        runQuery($pdo, "DELETE FROM module_map WHERE id=?", [$values['id']]);
    }
}
echo json_encode(['success' => true]);
