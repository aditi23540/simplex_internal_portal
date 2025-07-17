<?php
require_once '../config.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$entity = htmlspecialchars($_POST['entity'] ?? '', ENT_QUOTES);
$fields = $_POST['fields'] ?? [];

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->prepare("SELECT url, sap_user, sap_pass FROM api_master WHERE id = ?");
$stmt->execute([$id]);
$api = $stmt->fetch(PDO::FETCH_ASSOC);

$ch = curl_init("{$api['url']}/$entity?\$top=100&\$format=json");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api['sap_user'] . ":" . $api['sap_pass']);
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);
$results = [];
foreach ($json['value'] ?? [] as $row) {
    $trimmed = [];
    foreach ($fields as $f) $trimmed[$f] = $row[$f] ?? null;
    $results[] = $trimmed;
}
echo json_encode($results);
