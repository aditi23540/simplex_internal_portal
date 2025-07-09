<?php
// List of databases you want to work with
$databases = ['user_master_db']; // Add your DB names

function getPDO($db) {
    return new PDO("mysql:host=localhost;dbname=$db", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

// Get schema + sample data (for Gemini prompt)
function getSchemaAndSamples($databases) {
    $result = [];
    foreach ($databases as $db) {
        $pdo = getPDO($db);
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $cols = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            $sample = $pdo->query("SELECT * FROM $table LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            $result[$db][$table] = [
                'columns' => array_column($cols, 'Field'),
                'samples' => $sample
            ];
        }
    }
    return $result;
}
?>
