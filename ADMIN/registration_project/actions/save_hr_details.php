<?php
// /actions/save_hr_details.php
session_start();
require_once '../includes/db_config.php';

$errors = [];
$user_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $form_mode = $_POST['form_mode'] ?? 'new';

    $unit = mysqli_real_escape_string($link, $_POST['unit'] ?? null);
    $department = mysqli_real_escape_string($link, $_POST['department'] ?? null);
    $designation = mysqli_real_escape_string($link, $_POST['designation'] ?? null);
    $date_of_joining = !empty($_POST['date_of_joining']) ? mysqli_real_escape_string($link, $_POST['date_of_joining']) : null;
    $category = mysqli_real_escape_string($link, $_POST['category'] ?? null);
    $grade = mysqli_real_escape_string($link, $_POST['grade'] ?? null);
    $status = mysqli_real_escape_string($link, $_POST['status'] ?? null);
    $leave_group = mysqli_real_escape_string($link, $_POST['leave_group'] ?? null);
    $shift_schedule = mysqli_real_escape_string($link, $_POST['shift_schedule'] ?? null);
    $reporting_incharge = mysqli_real_escape_string($link, $_POST['reporting_incharge'] ?? null);
    $department_head = mysqli_real_escape_string($link, $_POST['department_head'] ?? null);
    $attendance_policy = mysqli_real_escape_string($link, $_POST['attendance_policy'] ?? null);
    $employee_id_ascent = mysqli_real_escape_string($link, $_POST['employee_id_ascent'] ?? null);
    // $employee_role REMOVED
    $payroll_code = mysqli_real_escape_string($link, $_POST['payroll_code'] ?? null);
    $vaccination_code = mysqli_real_escape_string($link, $_POST['vaccination_code'] ?? null);

    // --- Server-Side Validation ---
    if (empty(trim($unit))) { $errors['unit'] = "Unit is required."; }
    // ... (other validations for HR fields) ...
    if (empty(trim($employee_id_ascent))) { $errors['employee_id_ascent'] = "Employee ID (Ascent) is required."; }


    if (empty($errors)) {
        mysqli_begin_transaction($link);
        try {
            $sql_check = "SELECT hr_detail_id FROM user_hr_details WHERE user_id = ?";
            $hr_detail_id = null;
            if($stmt_check = mysqli_prepare($link, $sql_check)){
                mysqli_stmt_bind_param($stmt_check, "i", $user_id);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                if($row_check = mysqli_fetch_assoc($result_check)){
                    $hr_detail_id = $row_check['hr_detail_id'];
                }
                mysqli_stmt_close($stmt_check);
            }

            if ($hr_detail_id !== null) { // Update
                $sql_hr_op = "UPDATE user_hr_details SET unit = ?, department = ?, designation = ?, date_of_joining = ?, category = ?, grade = ?, status = ?, leave_group = ?, shift_schedule = ?, reporting_incharge = ?, department_head = ?, attendance_policy = ?, employee_id_ascent = ?, payroll_code = ?, vaccination_code = ? WHERE user_id = ?"; // employee_role removed
                $stmt_hr = mysqli_prepare($link, $sql_hr_op);
                mysqli_stmt_bind_param($stmt_hr, "ssssssssssssssi", // type string shortened
                    $unit, $department, $designation, $date_of_joining, $category, $grade, $status, 
                    $leave_group, $shift_schedule, $reporting_incharge, $department_head, 
                    $attendance_policy, $employee_id_ascent, $payroll_code, $vaccination_code,
                    $user_id
                );
            } else { // Insert
                $sql_hr_op = "INSERT INTO user_hr_details (user_id, unit, department, designation, date_of_joining, category, grade, status, leave_group, shift_schedule, reporting_incharge, department_head, attendance_policy, employee_id_ascent, payroll_code, vaccination_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // employee_role removed
                $stmt_hr = mysqli_prepare($link, $sql_hr_op);
                mysqli_stmt_bind_param($stmt_hr, "isssssssssssssss", // type string shortened
                    $user_id, $unit, $department, $designation, $date_of_joining, $category, $grade, $status, 
                    $leave_group, $shift_schedule, $reporting_incharge, $department_head, 
                    $attendance_policy, $employee_id_ascent, $payroll_code, $vaccination_code
                );
            }

            if (mysqli_stmt_execute($stmt_hr)) {
                mysqli_commit($link);
                $_SESSION['message'] = "HR details " . ($hr_detail_id ? "updated" : "saved") . " successfully for User ID: " . htmlspecialchars($user_id);
                $_SESSION['message_type'] = "success";
                header("Location: ../view_user_details.php?user_id=" . $user_id);
                exit();
            } else {
                throw new Exception("Error saving HR details: " . mysqli_stmt_error($stmt_hr));
            }
            mysqli_stmt_close($stmt_hr);

        } catch (Exception $e) {
            mysqli_rollback($link);
            $errors['database'] = "Transaction Failed: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors_hr'] = $errors;
        header("Location: ../hr_onboarding_form.php?user_id=" . $user_id);
        exit;
    }
    mysqli_close($link);
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "error";
    header("Location: ../view_users.php");
    exit();
}
?>