<?php
// /actions/submit_update_request.php

// --- 1. SESSION CONFIGURATION & START ---
session_set_cookie_params(['path' => '/']);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 2. SECURITY & AUTHENTICATION ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['message'] = "Invalid request or form submission token.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../my_profile.php");
    exit;
}

require_once '../includes/db_config.php';
if (!$link) { die("Database connection failed."); }

// ✅ --- THIS IS THE FIX ---
// This block replaces the old, failing security check.

// Step A: Get the empcode from the session (our trusted identifier).
if (!isset($_SESSION['empcode']) || empty($_SESSION['empcode'])) {
    die("CRITICAL ERROR: Your session is missing the Employee Code. Please log out and log in again.");
}
$session_empcode = $_SESSION['empcode'];

// Step B: Get the user ID submitted with the form.
$submitted_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

// Step C: Look up the TRUE user ID from the database using the trusted empcode.
$true_user_id = null;
$sql_get_id = "SELECT user_id FROM user_hr_details WHERE employee_id_ascent = ?";
if ($stmt_get_id = $link->prepare($sql_get_id)) {
    $stmt_get_id->bind_param("s", $session_empcode);
    $stmt_get_id->execute();
    $result = $stmt_get_id->get_result();
    if ($row = $result->fetch_assoc()) {
        $true_user_id = (int)$row['user_id'];
    }
    $stmt_get_id->close();
}

// Step D: Now, compare the ID from the form against the TRUE ID from the database.
// This is a robust check that does not rely on $_SESSION['user_id'].
if ($submitted_user_id !== $true_user_id || $true_user_id === null) {
    $_SESSION['message'] = "Authorization error: You can only submit requests for your own profile.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../my_profile.php");
    exit;
}
// --- END OF FIX ---

// If we passed the check, we can safely use the verified user ID.
$user_id = $true_user_id;

// --- 3. CHECK FOR EXISTING REQUESTS ---
$sql_check = "SELECT request_id FROM user_update_requests WHERE user_id = ? AND request_status = 'pending'";
$stmt_check = mysqli_prepare($link, $sql_check);
mysqli_stmt_bind_param($stmt_check, "i", $user_id);
mysqli_stmt_execute($stmt_check);
if (mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
    $_SESSION['message'] = "You already have a pending update request. Please wait for it to be processed.";
    $_SESSION['message_type'] = "warning";
    header("Location: ../my_profile.php");
    exit;
}
mysqli_stmt_close($stmt_check);

// --- 4. PROCESS FORM DATA & FILE UPLOADS ---
$form_data = $_POST;
unset($form_data['csrf_token']);

$upload_dir = '../uploads/temp_profile_updates/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

foreach ($_FILES as $input_name => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $file["tmp_name"];
        $new_filename = uniqid($user_id . '_', true) . '-' . basename($file["name"]);
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($tmp_name, $destination)) {
            if (strpos($input_name, '[') !== false) {
                parse_str($input_name, $keys);
                $key_arr = array_keys($keys);
                $main_key = $key_arr[0];
                $index = array_keys($keys[$main_key])[0];
                $field = array_keys($keys[$main_key][$index])[0];
                $form_data[$main_key][$index][$field . '_new_path'] = $destination;
            } else {
                $form_data[$input_name . '_new_path'] = $destination;
            }
        }
    }
}

// --- 5. STORE THE REQUEST IN THE DATABASE ---
$changed_data_json = json_encode($form_data, JSON_PRETTY_PRINT);

$sql_insert = "INSERT INTO user_update_requests (user_id, changed_data_json, request_status) VALUES (?, ?, 'pending')";
if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
    mysqli_stmt_bind_param($stmt_insert, "is", $user_id, $changed_data_json);
    if (mysqli_stmt_execute($stmt_insert)) {
        $_SESSION['message'] = "Your update request has been successfully submitted for approval.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error submitting your request: " . mysqli_error($link);
        $_SESSION['message_type'] = "danger";
    }
    mysqli_stmt_close($stmt_insert);
} else {
    $_SESSION['message'] = "A database error occurred: " . mysqli_error($link);
    $_SESSION['message_type'] = "danger";
}

mysqli_close($link);
header("Location: ../my_profile.php");
exit;
