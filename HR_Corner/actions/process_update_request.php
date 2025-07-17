<?php
// /actions/process_update_request.php

session_set_cookie_params(['path' => '/']);
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// --- 1. SECURITY & AUTHENTICATION ---
if (!isset($_SESSION['loggedin']) || ($_SESSION['employee_role'] !== 'HR' && $_SESSION['employee_role'] !== 'ADMIN')) { die("Access Denied."); }
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) { die("Invalid Request."); }

require_once '../includes/db_config.php';
if (!$link) { die("Database connection failed."); }

// --- 2. GET & VALIDATE INPUT ---
$request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$hr_user = $_SESSION['username'];

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['message'] = "Invalid action or request ID."; $_SESSION['message_type'] = "danger";
    header("Location: ../hr_update_requests.php"); exit;
}

// --- Helper Function for File Handling ---
function handle_file_move($new_path_key, &$data_array, $user_id) {
    if (isset($data_array[$new_path_key]) && file_exists($data_array[$new_path_key])) {
        $temp_path = $data_array[$new_path_key];
        $final_dir = "../uploads/user_{$user_id}/";
        if (!is_dir($final_dir)) { mkdir($final_dir, 0777, true); }
        
        $final_path = $final_dir . basename($temp_path);
        if (rename($temp_path, $final_path)) {
            // Unset old file if it exists and is different
            $existing_path_key = str_replace('_new_path', '', $new_path_key);
            if (isset($data_array[$existing_path_key]) && !empty($data_array[$existing_path_key]) && $data_array[$existing_path_key] !== $final_path) {
                @unlink($data_array[$existing_path_key]);
            }
            return $final_path;
        }
    }
    return null; // Return null if no new file or move failed
}


// --- 3. PROCESS THE ACTION ---
if ($action == 'reject') {
    // Simple case: Just update the request status to 'rejected'.
    $sql_reject = "UPDATE user_update_requests SET request_status = 'rejected', processed_by = ?, processed_at = NOW() WHERE request_id = ?";
    if ($stmt_reject = mysqli_prepare($link, $sql_reject)) {
        mysqli_stmt_bind_param($stmt_reject, "si", $hr_user, $request_id);
        mysqli_stmt_execute($stmt_reject);
        mysqli_stmt_close($stmt_reject);
        $_SESSION['message'] = "Request #{$request_id} has been rejected.";
        $_SESSION['message_type'] = "warning";
    }
} elseif ($action == 'approve') {
    // Complex case: Apply the changes from the JSON data.
    
    // Fetch the request data first
    $sql_fetch = "SELECT user_id, changed_data_json FROM user_update_requests WHERE request_id = ? AND request_status = 'pending'";
    $stmt_fetch = mysqli_prepare($link, $sql_fetch);
    mysqli_stmt_bind_param($stmt_fetch, "i", $request_id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    $request_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_fetch);
    
    if (!$request_data) {
        $_SESSION['message'] = "Request #{$request_id} could not be found or has already been processed.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../hr_update_requests.php"); exit;
    }

    $user_id = $request_data['user_id'];
    $new_data = json_decode($request_data['changed_data_json'], true);
    
    // Start a transaction to ensure all updates succeed or none do.
    mysqli_autocommit($link, false);
    $all_updates_successful = true;

    // --- Update 'users' table ---
    $user_table_columns = ['salutation', 'first_name', 'middle_name', 'surname', 'nationality', 'gender', 'religion', 'category_type', 'date_of_birth', 'celebrated_date_of_birth', 'perm_birth_country', 'perm_birth_state', 'perm_birth_city_village', 'perm_address_line1', 'perm_address_line2', 'perm_address_line3', 'present_birth_country', 'present_birth_state', 'present_birth_city_village', 'present_address_line1', 'present_address_line2', 'present_address_line3', 'blood_group', 'weight_kg', 'height_cm', 'your_phone_number', 'identification_marks', 'your_email_id', 'emergency_contact_number', 'pan_card_no', 'aadhar_number', 'dl_number', 'dl_expiration_date', 'passport_number', 'passport_expiration_date', 'marital_status', 'extra_curricular_activities', 'hobbies'];
    $update_parts = []; $params = []; $types = '';
    
    // Handle main file uploads for users table
    if ($new_path = handle_file_move('profile_picture_new_path', $new_data, $user_id)) { $new_data['profile_picture_path'] = $new_path; }
    if ($new_path = handle_file_move('signature_new_path', $new_data, $user_id)) { $new_data['signature_path'] = $new_path; }
    // Add other user-level file fields here...

    foreach ($user_table_columns as $column) {
        if (isset($new_data[$column])) {
            $update_parts[] = "{$column} = ?";
            $params[] = $new_data[$column];
            $types .= 's'; // Assume string for simplicity; adjust as needed
        }
    }

    if (!empty($update_parts)) {
        $sql_update_user = "UPDATE users SET " . implode(', ', $update_parts) . " WHERE user_id = ?";
        $params[] = $user_id; $types .= 'i';
        
        if ($stmt_update = mysqli_prepare($link, $sql_update_user)) {
            mysqli_stmt_bind_param($stmt_update, $types, ...$params);
            if (!mysqli_stmt_execute($stmt_update)) {
                $all_updates_successful = false;
                error_log("Failed to update users table for request_id {$request_id}: " . mysqli_error($link));
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $all_updates_successful = false;
        }
    }
    
    // --- Spouse Details Update (Insert on Duplicate Key) ---
    if (isset($new_data['spouse']) && is_array($new_data['spouse']) && $all_updates_successful) {
        $s = $new_data['spouse'];
        $sql_spouse = "INSERT INTO spouse_details (user_id, salutation, name, date_of_birth, aadhar_no, occupation, mobile_number, address, is_nominee_pf, is_nominee_esic, is_dependent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE salutation=VALUES(salutation), name=VALUES(name), date_of_birth=VALUES(date_of_birth), aadhar_no=VALUES(aadhar_no), occupation=VALUES(occupation), mobile_number=VALUES(mobile_number), address=VALUES(address), is_nominee_pf=VALUES(is_nominee_pf), is_nominee_esic=VALUES(is_nominee_esic), is_dependent=VALUES(is_dependent)";
        if($stmt_s = mysqli_prepare($link, $sql_spouse)){
            mysqli_stmt_bind_param($stmt_s, "isssssssiii", $user_id, $s['salutation'], $s['name'], $s['date_of_birth'], $s['aadhar_no'], $s['occupation'], $s['mobile_number'], $s['address'], ($s['is_nominee_pf'] ?? 0), ($s['is_nominee_esic'] ?? 0), ($s['is_dependent'] ?? 0));
            if(!mysqli_stmt_execute($stmt_s)){ $all_updates_successful = false; error_log("Spouse update failed: ".mysqli_error($link)); }
            mysqli_stmt_close($stmt_s);
        }
    }

    // --- Parent Details Update (Father/Mother) ---
    foreach(['father', 'mother'] as $type){
        if (isset($new_data[$type]) && is_array($new_data[$type]) && $all_updates_successful) {
            $p = $new_data[$type];
            $aadhar_path = handle_file_move($type.'_aadhar_file_new_path', $new_data, $user_id) ?? ($p['existing_father_aadhar_file_path'] ?? null);
            $sql_parent = "INSERT INTO parent_details (user_id, parent_type, salutation, name, date_of_birth, aadhar_no, occupation, mobile_number, address, aadhar_file_path, is_nominee_pf, is_nominee_esic, is_dependent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE salutation=VALUES(salutation), name=VALUES(name), date_of_birth=VALUES(date_of_birth), aadhar_no=VALUES(aadhar_no), occupation=VALUES(occupation), mobile_number=VALUES(mobile_number), address=VALUES(address), aadhar_file_path=VALUES(aadhar_file_path), is_nominee_pf=VALUES(is_nominee_pf), is_nominee_esic=VALUES(is_nominee_esic), is_dependent=VALUES(is_dependent)";
            if($stmt_p = mysqli_prepare($link, $sql_parent)){
                 mysqli_stmt_bind_param($stmt_p, "isssssssssiii", $user_id, ucfirst($type), $p['salutation'], $p['name'], $p['date_of_birth'], $p['aadhar_no'], $p['occupation'], $p['mobile_number'], $p['address'], $aadhar_path, ($p['is_nominee_pf'] ?? 0), ($p['is_nominee_esic'] ?? 0), ($p['is_dependent'] ?? 0));
                 if(!mysqli_stmt_execute($stmt_p)){ $all_updates_successful = false; error_log(ucfirst($type)." update failed: ".mysqli_error($link)); }
                 mysqli_stmt_close($stmt_p);
            }
        }
    }
    
    // --- Bank Details Update ---
    if (isset($new_data['bank']) && is_array($new_data['bank']) && $all_updates_successful) {
        $b = $new_data['bank'];
        $doc_path = handle_file_move('bank_passbook_document_new_path', $new_data, $user_id) ?? ($new_data['existing_bank_passbook_document'] ?? null);
        $sql_bank = "INSERT INTO user_bank_details (user_id, bank_name, account_number, ifsc_code, micr_code, bank_address, passbook_document_path) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bank_name=VALUES(bank_name), account_number=VALUES(account_number), ifsc_code=VALUES(ifsc_code), micr_code=VALUES(micr_code), bank_address=VALUES(bank_address), passbook_document_path=VALUES(passbook_document_path)";
        if($stmt_b = mysqli_prepare($link, $sql_bank)){
            mysqli_stmt_bind_param($stmt_b, "issssss", $user_id, $b['bank_name'], $b['account_number'], $b['ifsc_code'], $b['micr_code'], $b['bank_address'], $doc_path);
            if(!mysqli_stmt_execute($stmt_b)){ $all_updates_successful = false; error_log("Bank details update failed: ".mysqli_error($link)); }
            mysqli_stmt_close($stmt_b);
        }
    }

    // --- Dynamic Sections (Education, Certifications, etc.) ---
    $dynamic_sections = [
        'education' => ['table' => 'user_education', 'id_col' => 'education_id', 'cols' => ['qualification', 'board_university', 'subject', 'enrollment_year', 'passing_year', 'percentage_grade', 'document_path']],
        'certification' => ['table' => 'user_certifications', 'id_col' => 'certification_id', 'cols' => ['certificate_name', 'certificate_authority', 'issued_on', 'valid_upto', 'document_path']],
        'experience' => ['table' => 'user_work_experience', 'id_col' => 'experience_id', 'cols' => ['company_name', 'designation', 'from_date', 'to_date', 'salary_per_annum', 'reason_for_leaving', 'experience_letter_path']],
        'language' => ['table' => 'user_languages', 'id_col' => 'language_id', 'cols' => ['language_name', 'can_speak', 'can_read', 'can_write', 'can_understand']]
    ];

    foreach ($dynamic_sections as $key => $config) {
        if (isset($new_data[$key]) && is_array($new_data[$key]) && $all_updates_successful) {
            $table = $config['table'];
            $id_col = $config['id_col'];
            $cols = $config['cols'];

            // 1. Get existing IDs from DB
            $existing_ids = [];
            $res = mysqli_query($link, "SELECT {$id_col} FROM {$table} WHERE user_id = {$user_id}");
            while($row = mysqli_fetch_assoc($res)) { $existing_ids[] = $row[$id_col]; }
            
            $submitted_ids = [];

            // 2. Loop through submitted data to INSERT or UPDATE
            foreach ($new_data[$key] as $index => $item) {
                $item_id = !empty($item['id']) ? (int)$item['id'] : null;
                
                // Handle file upload for this item
                if(isset($item['document_new_path'])){
                    $item['document_path'] = handle_file_move("document_new_path", $item, $user_id) ?? ($item['existing_document'] ?? null);
                }

                $placeholders = implode(', ', array_fill(0, count($cols), '?'));
                $col_names = implode(', ', $cols);
                
                if ($item_id) { // UPDATE
                    $submitted_ids[] = $item_id;
                    $set_parts = implode(' = ?, ', $cols) . ' = ?';
                    $sql_item = "UPDATE {$table} SET {$set_parts} WHERE {$id_col} = ?";
                    $item_params = array_values(array_intersect_key($item, array_flip($cols)));
                    $item_params[] = $item_id;
                    $item_types = str_repeat('s', count($cols)) . 'i';
                } else { // INSERT
                    $sql_item = "INSERT INTO {$table} (user_id, {$col_names}) VALUES (?, {$placeholders})";
                    $item_params = [$user_id];
                    foreach($cols as $c){ $item_params[] = $item[$c] ?? null; }
                    $item_types = 'i' . str_repeat('s', count($cols));
                }

                if ($stmt_item = mysqli_prepare($link, $sql_item)) {
                    mysqli_stmt_bind_param($stmt_item, $item_types, ...$item_params);
                    if (!mysqli_stmt_execute($stmt_item)) { $all_updates_successful = false; error_log("{$key} item update/insert failed: ".mysqli_error($link)); }
                    if(!$item_id) { $submitted_ids[] = mysqli_insert_id($link); }
                    mysqli_stmt_close($stmt_item);
                } else { $all_updates_successful = false; }
            }

            // 3. Determine and execute DELETIONS
            $ids_to_delete = array_diff($existing_ids, $submitted_ids);
            if (!empty($ids_to_delete)) {
                $delete_ids_str = implode(',', array_map('intval', $ids_to_delete));
                $sql_delete = "DELETE FROM {$table} WHERE user_id = {$user_id} AND {$id_col} IN ({$delete_ids_str})";
                if(!mysqli_query($link, $sql_delete)){ $all_updates_successful = false; error_log("{$key} item deletion failed: ".mysqli_error($link)); }
            }
        }
    }


    // --- Finalize Transaction ---
    if ($all_updates_successful) {
        mysqli_commit($link); // Commit all database changes
        
        // Mark request as approved
        $sql_approve = "UPDATE user_update_requests SET request_status = 'approved', processed_by = ?, processed_at = NOW() WHERE request_id = ?";
        if($stmt_approve = mysqli_prepare($link, $sql_approve)){
            mysqli_stmt_bind_param($stmt_approve, "si", $hr_user, $request_id);
            mysqli_stmt_execute($stmt_approve);
            mysqli_stmt_close($stmt_approve);
        }
        
        $_SESSION['message'] = "Request #{$request_id} has been approved and the user's profile is updated.";
        $_SESSION['message_type'] = "success";
    } else {
        mysqli_rollback($link); // Revert all database changes if any part failed
        $_SESSION['message'] = "Failed to approve Request #{$request_id} due to a database error. The changes have been rolled back.";
        $_SESSION['message_type'] = "danger";
    }
}

mysqli_close($link);
header("Location: ../hr_update_requests.php");
exit;
