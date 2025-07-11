<?php
// fetch_issue_log.php
require_once 'config.php';

header('Content-Type: application/json');

// Get the issue ID from the request
$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($issue_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Issue ID provided.']);
    exit;
}

// Security check could be added here if needed, e.g., check if user has permission to view this log.

$response = [
    'status' => 'error',
    'issue_details' => null, // Will hold the main record from po_issues
    'logs' => []
];

// --- STEP 1: Fetch main issue details, including the initial remark ---
$sql_issue = "SELECT * FROM po_issues WHERE id = ?";
if ($stmt_issue = $conn_po->prepare($sql_issue)) {
    $stmt_issue->bind_param('i', $issue_id);
    $stmt_issue->execute();
    $result_issue = $stmt_issue->get_result();
    if ($result_issue->num_rows > 0) {
        $response['issue_details'] = $result_issue->fetch_assoc();
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Issue not found.']);
         exit;
    }
    $stmt_issue->close();
}


// --- STEP 2: Fetch all subsequent logs for this issue ---
$sql_logs = "SELECT * FROM issue_logs WHERE issue_id = ? ORDER BY log_timestamp ASC";
if ($stmt_logs = $conn_po->prepare($sql_logs)) {
    $stmt_logs->bind_param('i', $issue_id);
    $stmt_logs->execute();
    $result_logs = $stmt_logs->get_result();
    while($row = $result_logs->fetch_assoc()) {
        $response['logs'][] = $row;
    }
    $stmt_logs->close();
}

$response['status'] = 'success';

echo json_encode($response);

$conn_po->close();
$conn_user->close();
?>