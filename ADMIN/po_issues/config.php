<?php
// page name - config.php

// Start the session at the very beginning
session_start();

// --- Database Configuration ---
// Connection 1: For User Master DB
$db_user_host = "localhost";
$db_user_user = "root";
$db_user_pass = "";
$db_user_name = "user_master_db";

// Connection 2: For Simplex Internal DB (PO Issues)
$db_po_host = "localhost";
$db_po_user = "root";
$db_po_pass = "";
$db_po_name = "simplexinternal";


// --- Establish Database Connections ---
$conn_user = new mysqli($db_user_host, $db_user_user, $db_user_pass, $db_user_name);
$conn_po = new mysqli($db_po_host, $db_po_user, $db_po_pass, $db_po_name);


// --- Check Connections ---
if ($conn_user->connect_error) {
    die("Connection failed for user_master_db: " . $conn_user->connect_error);
}
if ($conn_po->connect_error) {
    die("Connection failed for simplexinternal: " . $conn_po->connect_error);
}


// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// --- Retrieve and prepare user info from session ---
$original_username = $_SESSION['username'] ?? 'User';
$empcode = $_SESSION['empcode'] ?? 'N/A';
$user_id = $_SESSION['user_id'] ?? null; // Make sure user_id is stored in session upon login
$department_display = htmlspecialchars($_SESSION['department'] ?? 'N/A');
$employee_role_display = htmlspecialchars($_SESSION['employee_role'] ?? 'N/A');
$username_for_display = htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $original_username))));


// --- Fetch User Avatar from user_master_db ---
$avatar_path = "assets/img/kaiadmin/default-avatar.png";
if ($empcode !== 'N/A') {
    $sql = "SELECT users.profile_picture_path
            FROM users
            JOIN user_hr_details ON users.user_id = user_hr_details.user_id
            WHERE user_hr_details.employee_id_ascent = ?";

    if ($stmt = $conn_user->prepare($sql)) {
        $stmt->bind_param("s", $empcode);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['profile_picture_path'])) {
                $avatar_path = "../registration_project/" . htmlspecialchars($row['profile_picture_path']);
            }
        }
        $stmt->close();
    }
}


// --- NEW: Reusable Logging Function ---
function log_issue_action($conn_po, $issue_id, $action_type, $remarks = null, $old_value = null, $new_value = null) {
    // Get user info from the current session for logging
    $action_by_userid = $_SESSION['user_id'] ?? null;
    $action_by_empcode = $_SESSION['empcode'] ?? null;
    $action_by_name = isset($_SESSION['username']) ? htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $_SESSION['username'])))) : null;

    $log_sql = "INSERT INTO issue_logs (issue_id, action_type, action_by_userid, action_by_empcode, action_by_name, remarks, old_value, new_value) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($log_stmt = $conn_po->prepare($log_sql)) {
        $log_stmt->bind_param(
            "isssssss",
            $issue_id,
            $action_type,
            $action_by_userid,
            $action_by_empcode,
            $action_by_name,
            $remarks,
            $old_value,
            $new_value
        );
        // Execute and close without returning anything to prevent interference
        $log_stmt->execute();
        $log_stmt->close();
    }
}
?>