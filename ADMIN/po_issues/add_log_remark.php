<?php
// page name - add_log_remark.php
require_once 'config.php';

header('Content-Type: application/json');

// --- Security and Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to add a remark.']);
    exit;
}

$issue_id = $_POST['issue_id'] ?? null;
$remark = $_POST['remark'] ?? null;

if (empty($issue_id) || empty(trim($remark))) {
    echo json_encode(['status' => 'error', 'message' => 'Issue ID and Remark are required.']);
    exit;
}

// --- Log the Action using the reusable function from config.php ---
log_issue_action($conn_po, $issue_id, 'Remark Added', $remark, null, null);


// --- Also update the main issue's 'updated_at' timestamp to reflect activity ---
$stmt = $conn_po->prepare("UPDATE po_issues SET updated_at = NOW() WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $issue_id);
    $stmt->execute();
    $stmt->close();
}


echo json_encode(['status' => 'success', 'message' => 'Remark added successfully.']);

$conn_po->close();
$conn_user->close();
?>