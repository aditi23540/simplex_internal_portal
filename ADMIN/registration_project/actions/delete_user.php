<?php
// /actions/delete_user.php
session_start();
require_once '../includes/db_config.php';

$user_id_to_delete = null;
$can_delete = false;

// Check if user_id is provided and is a valid integer
if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $user_id_to_delete = (int)$_GET['user_id'];
    $can_delete = true;
} else {
    $_SESSION['message'] = "Invalid User ID provided for deletion.";
    $_SESSION['message_type'] = "error";
    header("Location: ../view_users.php");
    exit();
}

if ($can_delete) {
    mysqli_begin_transaction($link);
    try {
        // Optional: Fetch file paths before deleting to remove files from server
        // This is a simplified example; a more robust solution would list all file paths
        // from all related tables (users, parent_details, user_education, etc.)

        $file_paths_to_delete = [];
        $sql_get_files = "SELECT profile_picture_path, signature_path, pan_card_file_path, aadhar_card_file_path, dl_file_path, passport_file_path FROM users WHERE user_id = ?";
        if($stmt_files = mysqli_prepare($link, $sql_get_files)){
            mysqli_stmt_bind_param($stmt_files, "i", $user_id_to_delete);
            mysqli_stmt_execute($stmt_files);
            $result_files = mysqli_stmt_get_result($stmt_files);
            if($row_files = mysqli_fetch_assoc($result_files)){
                foreach($row_files as $file_path){
                    if(!empty($file_path) && file_exists("../" . $file_path)){ // Paths are stored relative to project root
                        $file_paths_to_delete[] = "../" . $file_path;
                    }
                }
            }
            mysqli_stmt_close($stmt_files);
        }
        // You'd also query user_education, user_certifications, user_work_experience, user_bank_details, parent_details for their document paths

        // Delete from users table (ON DELETE CASCADE should handle related tables)
        $sql_delete_user = "DELETE FROM users WHERE user_id = ?";
        if ($stmt = mysqli_prepare($link, $sql_delete_user)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id_to_delete);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Delete actual files from server
                    foreach ($file_paths_to_delete as $file_to_delete) {
                        if (is_writable($file_to_delete)) { // Check if writable before attempting unlink
                           unlink($file_to_delete);
                        } else {
                            // Log error: could not delete file due to permissions
                            error_log("Permission denied or file not found trying to delete: " . $file_to_delete);
                        }
                    }
                    // Also attempt to remove user-specific upload directory if it's empty
                    $user_upload_dir = "../uploads/user_" . $user_id_to_delete;
                    if (is_dir($user_upload_dir) && (count(scandir($user_upload_dir)) == 2)) { // Checks for . and ..
                        rmdir($user_upload_dir);
                    }

                    mysqli_commit($link);
                    $_SESSION['message'] = "User (ID: " . htmlspecialchars($user_id_to_delete) . ") and associated data deleted successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                     mysqli_rollback($link);
                    $_SESSION['message'] = "User not found or already deleted.";
                    $_SESSION['message_type'] = "error";
                }
            } else {
                mysqli_rollback($link);
                $_SESSION['message'] = "Error deleting user: " . mysqli_stmt_error($stmt);
                $_SESSION['message_type'] = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            mysqli_rollback($link);
            $_SESSION['message'] = "Error preparing delete statement: " . mysqli_error($link);
            $_SESSION['message_type'] = "error";
        }
    } catch (Exception $e) {
        mysqli_rollback($link);
        $_SESSION['message'] = "An error occurred during deletion: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}

mysqli_close($link);
header("Location: ../view_users.php");
exit();
?>