<?php

// page name - update_issue_status.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$issue_id = $_POST['issue_id'] ?? null;
$new_status = $_POST['new_status'] ?? null;
$remark = $_POST['remark'] ?? null; // Capture the mandatory remark

if (empty($issue_id) || empty($new_status) || empty(trim($remark))) {
    echo json_encode(['status' => 'error', 'message' => 'A remark is required to change the status.']);
    exit;
}

// You can re-enable role checks here if needed in the future
// $user_role = $_SESSION['employee_role'] ?? 'guest';
// $allowed_roles = ['reviewer', 'admin', 'Purchase']; 
// if (!in_array($user_role, $allowed_roles)) { ... exit ... }

$valid_transitions = ['Open' => ['In Progress'], 'In Progress' => ['Resolved']];

$stmt_check = $conn_po->prepare("SELECT status FROM po_issues WHERE id = ?");
$stmt_check->bind_param('i', $issue_id);
$stmt_check->execute();
$current_status = $stmt_check->get_result()->fetch_assoc()['status'];
$stmt_check->close();

if (!isset($valid_transitions[$current_status]) || !in_array($new_status, $valid_transitions[$current_status])) {
    echo json_encode(['status' => 'error', 'message' => "Invalid status transition."]);
    exit;
}

$assignee_name = htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $_SESSION['username'] ?? 'System'))));
$assignee_empcode = $_SESSION['empcode'] ?? null;
$assignee_userid = $_SESSION['user_id'] ?? null; 

$sql = "UPDATE po_issues SET 
            status = ?,
            user_confirmation = IF(? = 'Resolved', NULL, user_confirmation),
            assignee_name = ?,
            assignee_empcode = ?,
            assignee_userid = ?,
            status_set_by_assignee_at = NOW(),
            updated_at = NOW()
        WHERE id = ?";

$stmt_update = $conn_po->prepare($sql);
if ($stmt_update) {
    $stmt_update->bind_param('ssssii', $new_status, $new_status, $assignee_name, $assignee_empcode, $assignee_userid, $issue_id);
    if ($stmt_update->execute()) {
        // Log the status change action WITH the remark
        log_issue_action($conn_po, $issue_id, 'Status Changed', $remark, $current_status, $new_status);
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
    }
    $stmt_update->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed.']);
}
$conn_po->close();
$conn_user->close();
?>