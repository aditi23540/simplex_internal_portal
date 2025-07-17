<?php
require_once '../config.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
$entity = htmlspecialchars($_GET['entity'] ?? '', ENT_QUOTES);

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->prepare("SELECT url, sap_user, sap_pass FROM api_master WHERE id = ?");
$stmt->execute([$id]);
$api = $stmt->fetch(PDO::FETCH_ASSOC);

$ch = curl_init("{$api['url']}/$entity?\$top=1&\$format=json");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api['sap_user'] . ":" . $api['sap_pass']);
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);
$fields = array_keys($json['value'][0] ?? []);
echo json_encode($fields);
