<?php
// /actions/save_it_details.php (Updated to handle role in HR table)
session_start();
require_once '../includes/db_config.php';

$errors = [];
$user_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $form_mode = $_POST['form_mode'] ?? 'new';

    // Get IT-specific fields
    $official_phone_number = !empty($_POST['official_phone_number']) ? mysqli_real_escape_string($link, trim($_POST['official_phone_number'])) : null;
    $official_email = !empty($_POST['official_email']) ? mysqli_real_escape_string($link, trim($_POST['official_email'])) : null;
    $intercom_number = !empty($_POST['intercom_number']) ? mysqli_real_escape_string($link, trim($_POST['intercom_number'])) : null;
    
    // Get the employee_role, which will be updated in the user_hr_details table
    $employee_role = mysqli_real_escape_string($link, $_POST['employee_role'] ?? 'USER');

    // --- Server-Side Validation ---
    if (empty($official_email)) {
        $errors['official_email'] = "Official Email is required.";
    } elseif (!filter_var($official_email, FILTER_VALIDATE_EMAIL)) {
        $errors['official_email'] = "Invalid official email format.";
    }
    if (empty(trim($employee_role))) {
        $errors['employee_role'] = "Role of Employee is required.";
    }

    if (empty($errors)) {
        mysqli_begin_transaction($link);
        try {
            // --- Query 1: UPSERT into user_it_details table (without role) ---
            $sql_check_it = "SELECT it_detail_id FROM user_it_details WHERE user_id = ?";
            $it_detail_id = null;
            if($stmt_check_it = mysqli_prepare($link, $sql_check_it)){
                mysqli_stmt_bind_param($stmt_check_it, "i", $user_id);
                mysqli_stmt_execute($stmt_check_it);
                $result_it = mysqli_stmt_get_result($stmt_check_it);
                if($row_it = mysqli_fetch_assoc($result_it)){
                    $it_detail_id = $row_it['it_detail_id'];
                }
                mysqli_stmt_close($stmt_check_it);
            }

            if ($it_detail_id !== null) { // UPDATE existing IT details
                $sql_it_op = "UPDATE user_it_details SET official_phone_number = ?, official_email = ?, intercom_number = ? WHERE user_id = ?";
                $stmt_it = mysqli_prepare($link, $sql_it_op);
                mysqli_stmt_bind_param($stmt_it, "sssi", $official_phone_number, $official_email, $intercom_number, $user_id);
            } else { // INSERT new IT details
                $sql_it_op = "INSERT INTO user_it_details (user_id, official_phone_number, official_email, intercom_number) VALUES (?, ?, ?, ?)";
                $stmt_it = mysqli_prepare($link, $sql_it_op);
                mysqli_stmt_bind_param($stmt_it, "isss", $user_id, $official_phone_number, $official_email, $intercom_number);
            }
            if (!mysqli_stmt_execute($stmt_it)) { throw new Exception("Error saving IT details: " . mysqli_stmt_error($stmt_it)); }
            mysqli_stmt_close($stmt_it);
            
            // --- Query 2: UPDATE the user_hr_details table with the employee_role ---
            // This query runs regardless of whether the IT details were new or updated.
            $sql_update_hr_role = "UPDATE user_hr_details SET employee_role = ? WHERE user_id = ?";
            if($stmt_hr_role = mysqli_prepare($link, $sql_update_hr_role)) {
                mysqli_stmt_bind_param($stmt_hr_role, "si", $employee_role, $user_id);
                if (!mysqli_stmt_execute($stmt_hr_role)) {
                    // Check if a record in user_hr_details exists. If not, create a minimal one.
                    if(mysqli_stmt_errno($stmt_hr_role) === 0 && mysqli_stmt_affected_rows($stmt_hr_role) === 0){
                        $sql_insert_hr_role = "INSERT INTO user_hr_details (user_id, employee_role) VALUES (?, ?)";
                        if($stmt_insert_hr = mysqli_prepare($link, $sql_insert_hr_role)){
                             mysqli_stmt_bind_param($stmt_insert_hr, "is", $user_id, $employee_role);
                             if(!mysqli_stmt_execute($stmt_insert_hr)) {
                                throw new Exception("Could not insert initial HR record for role: " . mysqli_stmt_error($stmt_insert_hr));
                             }
                             mysqli_stmt_close($stmt_insert_hr);
                        }
                    } else {
                        throw new Exception("Error updating employee role in HR details: " . mysqli_stmt_error($stmt_hr_role));
                    }
                }
                mysqli_stmt_close($stmt_hr_role);
            } else {
                throw new Exception("Error preparing HR role update statement: " . mysqli_error($link));
            }


            mysqli_commit($link);
            $_SESSION['message'] = "IT details and employee role updated successfully for User ID: " . htmlspecialchars($user_id);
            $_SESSION['message_type'] = "success";
            header("Location: ../view_user_details.php?user_id=" . $user_id);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($link);
            $errors['database'] = "Transaction Failed: " . $e->getMessage();
        }
    }
    
    // If errors occurred, redirect back to the form
    if (!empty($errors)) {
        $_SESSION['form_errors_it'] = $errors;
        header("Location: ../it_setup_form.php?user_id=" . $user_id);
        exit;
    }
    mysqli_close($link);
} else {
    $_SESSION['message'] = "Invalid request or User ID not provided for IT setup.";
    $_SESSION['message_type'] = "error";
    header("Location: ../view_users.php");
    exit();
}
?>