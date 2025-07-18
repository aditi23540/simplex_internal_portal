<?php
require_once '../config.php';
session_start();
header('Content-Type: application/json');

$apiId = intval($_GET['id'] ?? 0);
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->prepare("SELECT url, sap_user, sap_pass FROM api_master WHERE id = ?");
$stmt->execute([$apiId]);
$api = $stmt->fetch(PDO::FETCH_ASSOC);

$cacheKey = 'metadata_' . $apiId;
if (isset($_SESSION[$cacheKey]) && time() - $_SESSION[$cacheKey]['time'] < CACHE_TTL) {
    echo json_encode($_SESSION[$cacheKey]['data']);
    exit;
}

$ch = curl_init($api['url'] . '/$metadata');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api['sap_user'] . ":" . $api['sap_pass']);
$xml = curl_exec($ch);
curl_close($ch);

$sxe = simplexml_load_string($xml);
$sxe->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');
$entities = [];
foreach ($sxe->xpath('//edm:EntityContainer/edm:EntitySet') as $node) {
    $entities[] = (string)$node['Name'];
}
$_SESSION[$cacheKey] = ['time' => time(), 'data' => $entities];
echo json_encode($entities);
