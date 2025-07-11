<?php
// file name - process_po_issue.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// --- File Upload Handling ---
$attachment_path = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/po_issues/';
    if (!is_dir($upload_dir)) {
        // Attempt to create the directory recursively
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(["status" => "error", "message" => "Failed to create uploads directory."]);
            exit;
        }
    }
    
    $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid('po_', true) . '.' . $file_extension;
    $target_file = $upload_dir . $unique_filename;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
        $attachment_path = $target_file;
    } else {
        // Optionally, you can log an error here if the file move fails
        // For now, we'll just proceed with a NULL path
    }
}

// --- Retrieve and Sanitize Form Data ---
$site = $_POST['site'] ?? null;
$problem_type = $_POST['problem_type'] ?? null;
$gate_entry_no = $_POST['gate_entry_no'] ?? null;
$supplier_id = $_POST['supplier_id'] ?? null;
$supplier_name = $_POST['supplier_name'] ?? null;
$invoice_no = $_POST['invoice_no'] ?? null;
$invoice_date = !empty($_POST['invoice_date']) ? $_POST['invoice_date'] : null;
$challan_no = $_POST['challan_no'] ?? null;
$challan_date = !empty($_POST['challan_date']) ? $_POST['challan_date'] : null;
$po_number = $_POST['po_number'] ?? null;
$remarks = $_POST['remarks'] ?? null;
$project_id = $_POST['project_id'] ?? null;
$buyer_id = $_POST['buyer_id'] ?? null;
$buyer_name = $_POST['buyer_name'] ?? null;
$reporter_user_id = $_POST['reporter_user_id'] ?? null;
$reported_by_empcode = $_POST['reported_by_empcode'] ?? null;
$reported_by_name = $_POST['reported_by_name'] ?? null;

// --- Database Insertion ---
$sql = "INSERT INTO po_issues (
            site, problem_type, gate_entry_no, supplier_id, supplier_name, invoice_no, 
            invoice_date, challan_no, challan_date, po_number, remarks, project_id, buyer_id, 
            buyer_name, attachment_path, reporter_user_id, reported_by_empcode, reported_by_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

if ($stmt = $conn_po->prepare($sql)) {
    // CORRECTED BINDING: The 15th parameter (attachment_path) is now 's' (string) 
    // and the 16th (reporter_user_id) is now correctly 'i' (integer).
    $stmt->bind_param(
        "sssssssssssssssiss",
        $site, $problem_type, $gate_entry_no, $supplier_id, $supplier_name, $invoice_no, $invoice_date,
        $challan_no, $challan_date, $po_number, $remarks, $project_id, $buyer_id,
        $buyer_name, $attachment_path, $reporter_user_id, $reported_by_empcode, $reported_by_name
    );

    if ($stmt->execute()) {
        $new_issue_id = $stmt->insert_id;
        // Log the creation event
        log_issue_action($conn_po, $new_issue_id, 'Created', "Issue raised with initial remarks: " . $remarks, null, 'Open');
        
        echo json_encode(["status" => "success", "message" => "PO issue has been submitted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database insert failed: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Database query preparation failed: " . $conn_po->error]);
}

$conn_po->close();
$conn_user->close();
?>