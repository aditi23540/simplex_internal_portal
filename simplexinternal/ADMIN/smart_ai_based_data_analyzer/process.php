<?php
require 'config.php';

$user_input = $_POST['query'];
$schema_data = getSchemaAndSamples($databases);

// Build prompt for Gemini
$prompt = "You're an expert SQL developer. Given the following schema and user query, generate correct SQL.\n\n";
foreach ($schema_data as $db => $tables) {
    $prompt .= "Database: $db\n";
    foreach ($tables as $table => $info) {
        $prompt .= "Table: $table\nColumns: " . implode(", ", $info['columns']) . "\n";
        if (!empty($info['samples'])) {
            $prompt .= "Sample Data:\n";
            foreach (array_slice($info['samples'], 0, 2) as $row) {
                $prompt .= "- " . json_encode($row) . "\n";
            }
        }
        $prompt .= "\n";
    }
}
$prompt .= "\nUser Query: \"$user_input\"\nReturn ONLY the SQL.";

// 🔗 Gemini call
$api_key = "AIzaSyBmnxO3JTl5AtJzENnAgywqFTg9FdLlXb4"; // Replace with your key
$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$api_key");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "contents" => [[ "parts" => [["text" => $prompt]] ]]
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$sql = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
$sql = trim($sql, "```sql \n"); // Clean

// Execute SQL
try {
    $results = [];
    foreach ($databases as $db) {
        $pdo = getPDO($db);
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) break;
    }

    echo json_encode([
        "sql" => $sql,
        "result" => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage(),
        "sql" => $sql
    ]);
}
?>
