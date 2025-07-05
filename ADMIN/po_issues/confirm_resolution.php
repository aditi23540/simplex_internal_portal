<?php
//Page name - confirm_resolution.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$issue_id = $_POST['issue_id'] ?? null;
$confirmation = $_POST['confirmation_status'] ?? null;
$remarks = $_POST['resolution_remarks'] ?? '';
$empcode = $_SESSION['empcode'] ?? '';

if (empty($issue_id) || !isset($confirmation) || empty($empcode) || empty(trim($remarks))) {
    echo json_encode(['status' => 'error', 'message' => 'Remarks are mandatory.']);
    exit;
}

// ... (Your existing validation and ownership checks) ...
$stmt_check = $conn_po->prepare("SELECT status, reported_by_empcode FROM po_issues WHERE id = ?");
$stmt_check->bind_param("i", $issue_id);
$stmt_check->execute();
$issue_data = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if(!$issue_data || $issue_data['reported_by_empcode'] !== $empcode) {
    echo json_encode(['status' => 'error', 'message' => 'You are not authorized for this action.']);
    exit;
}


if ($confirmation == 1) { $new_status = 'Closed'; }
elseif ($confirmation == 0) { $new_status = 'Open'; }
else { /* handle error */ exit; }

$sql = "UPDATE po_issues SET 
            status = ?, user_confirmation = ?, user_resolution_remarks = ?,
            assignee_name = IF(? = 0, NULL, assignee_name),
            assignee_empcode = IF(? = 0, NULL, assignee_empcode),
            assignee_userid = IF(? = 0, NULL, assignee_userid),
            assignee_remarks = IF(? = 0, NULL, assignee_remarks),
            status_set_by_assignee_at = IF(? = 0, NULL, status_set_by_assignee_at),
            updated_at = NOW()
        WHERE id = ?";

$stmt_update = $conn_po->prepare($sql);
if ($stmt_update) {
    $stmt_update->bind_param('sisiiiiii', $new_status, $confirmation, $remarks, $confirmation, $confirmation, $confirmation, $confirmation, $confirmation, $issue_id);
    
    if ($stmt_update->execute()) {
        $action_type = ($new_status === 'Closed') ? 'Confirmed & Closed' : 'Re-Opened by User';
        log_issue_action($conn_po, $issue_id, $action_type, $remarks, 'Resolved', $new_status);
        
        $message = ($new_status === 'Closed') ? 'Issue confirmed and closed.' : 'Issue has been re-opened.';
        echo json_encode(['status' => 'success', 'message' => $message]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update issue.']);
    }
    $stmt_update->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed.']);
}

$conn_po->close();
$conn_user->close();
?>