<?php
// /actions/update_user.php
session_start();
require_once '../includes/db_config.php';

// (Re-use the handleFileUpload function from save_registration.php or include it via a functions.php file)
function handleFileUpload($fileInputName, $fieldName, &$errors, $userIdForPath = null, $specificIndex = -1) {
    $fileKey = $fileInputName;
    $isIndexedFile = ($specificIndex !== -1);
    $currentFileDetails = null;

    if ($isIndexedFile) {
        if (isset($_FILES[$fileInputName]['name'][$specificIndex]) && $_FILES[$fileInputName]['error'][$specificIndex] != UPLOAD_ERR_NO_FILE) {
            if ($_FILES[$fileInputName]['error'][$specificIndex] == 0) {
                $currentFileDetails = [
                    'name' => $_FILES[$fileInputName]['name'][$specificIndex],
                    'type' => $_FILES[$fileInputName]['type'][$specificIndex],
                    'tmp_name' => $_FILES[$fileInputName]['tmp_name'][$specificIndex],
                    'size' => $_FILES[$fileInputName]['size'][$specificIndex]
                ];
            } else { $errors[$fieldName . "_" . $specificIndex] = "Error with " . $fieldName . " upload (code " . $_FILES[$fileInputName]['error'][$specificIndex] . ")."; return null; }
        } else { return null; }
    } else {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] != UPLOAD_ERR_NO_FILE) {
            if ($_FILES[$fileInputName]['error'] == 0) {
                $currentFileDetails = $_FILES[$fileInputName];
            } else { $errors[$fieldName] = "Error with " . $fieldName . " upload (code " . $_FILES[$fileInputName]['error'] . ")."; return null; }
        } else { return null; }
    }
    if (!$currentFileDetails) return null;

    $targetDirRoot = "../uploads/";
    $targetDirUser = $targetDirRoot . "user_" . $userIdForPath . "/";
    if ($userIdForPath && !is_dir($targetDirUser)) {
        if (!mkdir($targetDirUser, 0777, true) && !is_dir($targetDirUser)) {
             $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = "Failed to create user directory for uploads at " . $targetDirUser;
             return null;
        }
    }
    
    $originalFileName = $currentFileDetails["name"];
    $sanitizedFileName = preg_replace("/[^a-zA-Z0-9\-\._]/", "_", $originalFileName);
    $targetFilePath = $targetDirUser . time() . "_" . uniqid() . "_" . $sanitizedFileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');

    if (in_array($fileType, $allowedTypes)) {
        if ($currentFileDetails["size"] < 500000) { // 500 KB
            if (move_uploaded_file($currentFileDetails["tmp_name"], $targetFilePath)) {
                return str_replace('../', '', $targetFilePath); 
            } else { $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = "Error moving uploaded " . $fieldName . ". Check permissions for: " . $targetDirUser; }
        } else { $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = $fieldName . " is too large (max 500KB)."; }
    } else { $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = "Invalid file type for " . $fieldName . "."; }
    return null;
}


$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];

    // --- Sanitize and retrieve ALL data from $_POST ---
    // Page 1: Personal Details
    $name_as_for_document = mysqli_real_escape_string($link, $_POST['nameAsFor'] ?? '');
    $salutation = mysqli_real_escape_string($link, $_POST['salutation'] ?? '');
    $first_name = mysqli_real_escape_string($link, $_POST['firstName'] ?? '');
    // ... (Retrieve ALL fields from $_POST, sanitize them, similar to save_registration.php) ...
    $middle_name = !empty($_POST['middleName']) ? mysqli_real_escape_string($link, $_POST['middleName']) : null;
    $surname = !empty($_POST['surname']) ? mysqli_real_escape_string($link, $_POST['surname']) : null;
    $nationality = mysqli_real_escape_string($link, $_POST['nationality'] ?? '');
    $gender = mysqli_real_escape_string($link, $_POST['gender'] ?? '');
    $religion = mysqli_real_escape_string($link, $_POST['religion'] ?? '');
    $category_type = mysqli_real_escape_string($link, $_POST['categoryType'] ?? '');
    $date_of_birth = !empty($_POST['dob']) ? mysqli_real_escape_string($link, $_POST['dob']) : null;
    $celebrated_date_of_birth = !empty($_POST['celebratedDob']) ? mysqli_real_escape_string($link, $_POST['celebratedDob']) : null;
    
    $perm_birth_country = mysqli_real_escape_string($link, $_POST['permBirthCountry'] ?? '');
    $perm_birth_state = mysqli_real_escape_string($link, $_POST['permBirthState'] ?? '');
    $perm_birth_city_village = mysqli_real_escape_string($link, $_POST['permBirthCity'] ?? '');
    $perm_address_line1 = mysqli_real_escape_string($link, $_POST['permAddress1'] ?? '');
    $perm_address_line2 = !empty($_POST['permAddress2']) ? mysqli_real_escape_string($link, $_POST['permAddress2']) : null;
    $perm_address_line3 = !empty($_POST['permAddress3']) ? mysqli_real_escape_string($link, $_POST['permAddress3']) : null;
    $present_birth_country = mysqli_real_escape_string($link, $_POST['presentBirthCountry'] ?? '');
    $present_birth_state = mysqli_real_escape_string($link, $_POST['presentBirthState'] ?? '');
    $present_birth_city_village = mysqli_real_escape_string($link, $_POST['presentBirthCity'] ?? '');
    $present_address_line1 = mysqli_real_escape_string($link, $_POST['presentAddress1'] ?? '');
    $present_address_line2 = !empty($_POST['presentAddress2']) ? mysqli_real_escape_string($link, $_POST['presentAddress2']) : null;
    $present_address_line3 = !empty($_POST['presentAddress3']) ? mysqli_real_escape_string($link, $_POST['presentAddress3']) : null;
    
    $blood_group = !empty($_POST['bloodGroup']) ? mysqli_real_escape_string($link, $_POST['bloodGroup']) : null;
    $weight_kg = !empty($_POST['weightKg']) ? floatval($_POST['weightKg']) : null;
    $height_cm = !empty($_POST['heightCm']) ? floatval($_POST['heightCm']) : null;
    $identification_marks = mysqli_real_escape_string($link, $_POST['identificationMarks'] ?? '');
    $pan_available = isset($_POST['panAvailable']) && $_POST['panAvailable'] === 'yes' ? 1 : 0;
    $pan_card_no = $pan_available && !empty($_POST['panCardNo']) ? mysqli_real_escape_string($link, $_POST['panCardNo']) : null;
    $aadhar_available = isset($_POST['aadharAvailable']) && $_POST['aadharAvailable'] === 'yes' ? 1 : 0;
    $aadhar_number = $aadhar_available && !empty($_POST['aadharNumberField']) ? mysqli_real_escape_string($link, $_POST['aadharNumberField']) : null;
    $dl_available = isset($_POST['dlAvailable']) && $_POST['dlAvailable'] === 'yes' ? 1 : 0;
    $dl_number = $dl_available && !empty($_POST['dlNumber']) ? mysqli_real_escape_string($link, $_POST['dlNumber']) : null;
    $dl_vehicle_type = $dl_available && !empty($_POST['vehicleType']) ? mysqli_real_escape_string($link, $_POST['vehicleType']) : null;
    $dl_expiration_date = ($dl_available && !empty($_POST['dlExpiry'])) ? mysqli_real_escape_string($link, $_POST['dlExpiry']) : null;
    $passport_available = isset($_POST['passportAvailable']) && $_POST['passportAvailable'] === 'yes' ? 1 : 0;
    $passport_number = $passport_available && !empty($_POST['passportNumberField']) ? mysqli_real_escape_string($link, $_POST['passportNumberField']) : null;
    $passport_expiration_date = ($passport_available && !empty($_POST['passportExpiry'])) ? mysqli_real_escape_string($link, $_POST['passportExpiry']) : null;

    $marital_status = mysqli_real_escape_string($link, $_POST['maritalStatus'] ?? '');
    $your_email_id = !empty($_POST['yourEmailId']) ? mysqli_real_escape_string($link, $_POST['yourEmailId']) : null;
    $your_phone_number = mysqli_real_escape_string($link, $_POST['yourPhoneNumber'] ?? '');
    $emergency_contact_number = mysqli_real_escape_string($link, $_POST['emergencyContactNumber'] ?? '');

    $has_past_experience = isset($_POST['pastExperience']) && $_POST['pastExperience'] === 'yes' ? 1 : 0;
    $has_pf_account = ($has_past_experience && isset($_POST['pfAccount']) && $_POST['pfAccount'] === 'yes') ? 1 : 0;
    $pf_account_established_code = $has_pf_account && !empty($_POST['pfAccountCode']) ? mysqli_real_escape_string($link, $_POST['pfAccountCode']) : null;
    $pf_uan_no = $has_pf_account && !empty($_POST['pfUanNo']) ? mysqli_real_escape_string($link, $_POST['pfUanNo']) : null;
    $pf_esi_no = $has_pf_account && !empty($_POST['pfEsiNo']) ? mysqli_real_escape_string($link, $_POST['pfEsiNo']) : null;
    $extra_curricular_activities = !empty($_POST['extraCurricular']) ? mysqli_real_escape_string($link, $_POST['extraCurricular']) : null;
    $hobbies = !empty($_POST['hobbies']) ? mysqli_real_escape_string($link, $_POST['hobbies']) : null;

    $medical_disability_exists = isset($_POST['medicalDisability']) && $_POST['medicalDisability'] === 'yes' ? 1 : 0;
    $medical_disability_details = $medical_disability_exists && !empty($_POST['medicalDisabilityText']) ? mysqli_real_escape_string($link, $_POST['medicalDisabilityText']) : null;
    $prev_employer_liability_exists = isset($_POST['prevEmployerLiability']) && $_POST['prevEmployerLiability'] === 'yes' ? 1 : 0;
    $prev_employer_liability_details = $prev_employer_liability_exists && !empty($_POST['prevEmployerLiabilityText']) ? mysqli_real_escape_string($link, $_POST['prevEmployerLiabilityText']) : null;
    $worked_simplex_group = isset($_POST['workedSimplex']) && $_POST['workedSimplex'] === 'yes' ? 1 : 0;
    $agree_posted_anywhere_india = isset($_POST['postedAnywhere']) && $_POST['postedAnywhere'] === 'yes' ? 1 : 0;
    $declaration_agreed = isset($_POST['declarationCheck']) ? 1 : 0;

    // --- Server-Side Validation (EXPAND THIS) ---
    if (empty(trim($first_name))) { $errors['firstName'] = "First name is required."; }
    if ($declaration_agreed == 0) { $errors['declarationCheck'] = "You must agree to the declaration."; }


    // --- File Path Management for Update ---
    $file_update_fields = [
        'pan_card_file_path' => ['input_name' => 'panCardFile', 'existing_path_field' => 'existing_pan_card_file', 'condition' => $pan_available],
        'aadhar_card_file_path' => ['input_name' => 'aadharCardFile', 'existing_path_field' => 'existing_aadhar_card_file', 'condition' => $aadhar_available],
        'dl_file_path' => ['input_name' => 'dlFile', 'existing_path_field' => 'existing_dl_file', 'condition' => $dl_available],
        'passport_file_path' => ['input_name' => 'passportFile', 'existing_path_field' => 'existing_passport_file', 'condition' => $passport_available],
        'profile_picture_path' => ['input_name' => 'uploadPicture', 'existing_path_field' => 'existing_profile_picture', 'condition' => true],
        'signature_path' => ['input_name' => 'uploadSign', 'existing_path_field' => 'existing_signature', 'condition' => true]
    ];
    $db_file_paths = [];

    foreach ($file_update_fields as $db_column => $file_info) {
        $existing_file_path = $_POST[$file_info['existing_path_field']] ?? null;
        $db_file_paths[$db_column] = $existing_file_path; // Default to existing

        if ($file_info['condition']) { // Only process if condition is met (e.g., pan_available is true)
            $new_file_path = handleFileUpload($file_info['input_name'], str_replace('_', ' ', ucfirst(str_replace('_path','',$db_column))), $errors, $user_id);
            if ($new_file_path) {
                if ($existing_file_path && file_exists("../" . $existing_file_path)) {
                    unlink("../" . $existing_file_path); // Delete old file
                }
                $db_file_paths[$db_column] = $new_file_path; // Set new path
            } elseif (!empty($_FILES[$file_info['input_name']]['name']) && $_FILES[$file_info['input_name']]['error'] != UPLOAD_ERR_NO_FILE) {
                // If a new file was attempted but failed, an error is already in $errors. Don't overwrite existing path yet.
                // Keep the existing path unless a new file successfully replaces it.
            }
        } else { // If condition is false (e.g., pan_available is false), clear the file path and delete old file
            if ($existing_file_path && file_exists("../" . $existing_file_path)) {
                unlink("../" . $existing_file_path);
            }
            $db_file_paths[$db_column] = null;
        }
    }
    
    // Father Aadhar (Special case as it's in parent_details)
    $existing_father_aadhar_file = $_POST['existing_father_aadhar_file'] ?? null;
    $father_aadhar_file_path_db = $existing_father_aadhar_file;
    $new_father_aadhar_file = handleFileUpload('fatherAadharFile', "Father_Aadhaar_Document", $errors, $user_id);
    if ($new_father_aadhar_file) {
        if ($existing_father_aadhar_file && file_exists("../" . $existing_father_aadhar_file)) {
            unlink("../" . $existing_father_aadhar_file);
        }
        $father_aadhar_file_path_db = $new_father_aadhar_file;
    }


    // --- Database Update ---
    if (empty($errors)) {
        mysqli_begin_transaction($link);
        try {
            $sql_user_update = "UPDATE users SET 
                name_as_for_document = ?, salutation = ?, first_name = ?, middle_name = ?, surname = ?, nationality = ?, gender = ?, religion = ?, category_type = ?, date_of_birth = ?, celebrated_date_of_birth = ?, 
                perm_birth_country = ?, perm_birth_state = ?, perm_birth_city_village = ?, perm_address_line1 = ?, perm_address_line2 = ?, perm_address_line3 = ?, 
                present_birth_country = ?, present_birth_state = ?, present_birth_city_village = ?, present_address_line1 = ?, present_address_line2 = ?, present_address_line3 = ?,
                blood_group = ?, weight_kg = ?, height_cm = ?, identification_marks = ?, 
                pan_available = ?, pan_card_no = ?, pan_card_file_path = ?, 
                aadhar_available = ?, aadhar_number = ?, aadhar_card_file_path = ?, 
                dl_available = ?, dl_number = ?, dl_file_path = ?, dl_vehicle_type = ?, dl_expiration_date = ?, 
                passport_available = ?, passport_number = ?, passport_file_path = ?, passport_expiration_date = ?, 
                profile_picture_path = ?, signature_path = ?, 
                marital_status = ?, your_email_id = ?, your_phone_number = ?, emergency_contact_number = ?, 
                has_past_experience = ?, has_pf_account = ?, pf_account_established_code = ?, pf_uan_no = ?, pf_esi_no = ?, 
                extra_curricular_activities = ?, hobbies = ?, 
                medical_disability_exists = ?, medical_disability_details = ?, 
                prev_employer_liability_exists = ?, prev_employer_liability_details = ?, 
                worked_simplex_group = ?, agree_posted_anywhere_india = ?, declaration_agreed = ?
                WHERE user_id = ?";
            
            $stmt_user = mysqli_prepare($link, $sql_user_update);
            mysqli_stmt_bind_param($stmt_user, "ssssssssssssssssssssssssssdsssssississsssissssssssssssssssssiii", 
                $name_as_for_document, $salutation, $first_name, $middle_name, $surname, $nationality, $gender, $religion, $category_type, $date_of_birth, $celebrated_date_of_birth, 
                $perm_birth_country, $perm_birth_state, $perm_birth_city_village, $perm_address_line1, $perm_address_line2, $perm_address_line3, 
                $present_birth_country, $present_birth_state, $present_birth_city_village, $present_address_line1, $present_address_line2, $present_address_line3,
                $blood_group, $weight_kg, $height_cm, $identification_marks,
                $pan_available, $pan_card_no, $db_file_paths['pan_card_file_path'], 
                $aadhar_available, $aadhar_number, $db_file_paths['aadhar_card_file_path'],
                $dl_available, $dl_number, $db_file_paths['dl_file_path'], $dl_vehicle_type, $dl_expiration_date,
                $passport_available, $passport_number, $db_file_paths['passport_file_path'], $passport_expiration_date,
                $db_file_paths['profile_picture_path'], $db_file_paths['signature_path'],
                $marital_status, $your_email_id, $your_phone_number, $emergency_contact_number,
                $has_past_experience, $has_pf_account, $pf_account_established_code, $pf_uan_no, $pf_esi_no,
                $extra_curricular_activities, $hobbies,
                $medical_disability_exists, $medical_disability_details,
                $prev_employer_liability_exists, $prev_employer_liability_details,
                $worked_simplex_group, $agree_posted_anywhere_india, $declaration_agreed,
                $user_id // For WHERE clause
            );
            mysqli_stmt_execute($stmt_user);
            mysqli_stmt_close($stmt_user);

            // --- Spouse Details (Update or Insert or Delete) ---
            if (($marital_status === 'Married' || $marital_status === 'Registered Partnership') && !empty(trim($_POST['spouseName'] ?? ''))) {
                $s_salutation = mysqli_real_escape_string($link, $_POST['spouseSalutation'] ?? '');
                $s_name = mysqli_real_escape_string($link, $_POST['spouseName']);
                // ... (get all spouse POST data) ...
                $s_nom_pf = isset($_POST['spouseNomineePF']) ? 1 : 0;
                $s_nom_esic = isset($_POST['spouseNomineeESIC']) ? 1 : 0;
                $s_dependent = isset($_POST['spouseDependent']) ? 1 : 0;
                $s_mobile = !empty($_POST['spouseMobile']) ? mysqli_real_escape_string($link, $_POST['spouseMobile']) : null;
                $s_dob = !empty($_POST['spouseDob']) ? mysqli_real_escape_string($link, $_POST['spouseDob']) : null;
                $s_aadhar = !empty($_POST['spouseAadharNo']) ? mysqli_real_escape_string($link, $_POST['spouseAadharNo']) : null;
                $s_occupation = mysqli_real_escape_string($link, $_POST['spouseOccupation'] ?? '');
                $s_address = mysqli_real_escape_string($link, $_POST['spouseAddress'] ?? '');

                // Check if spouse record exists
                $sql_check_spouse = "SELECT spouse_id FROM spouse_details WHERE user_id = ?";
                $stmt_check_spouse = mysqli_prepare($link, $sql_check_spouse);
                mysqli_stmt_bind_param($stmt_check_spouse, "i", $user_id);
                mysqli_stmt_execute($stmt_check_spouse);
                $result_check_spouse = mysqli_stmt_get_result($stmt_check_spouse);
                if (mysqli_num_rows($result_check_spouse) > 0) { // Update existing
                    $sql_spouse_update = "UPDATE spouse_details SET salutation=?, name=?, mobile_number=?, date_of_birth=?, aadhar_no=?, occupation=?, address=?, is_nominee_pf=?, is_nominee_esic=?, is_dependent=? WHERE user_id=?";
                    $stmt_spouse = mysqli_prepare($link, $sql_spouse_update);
                    mysqli_stmt_bind_param($stmt_spouse, "sssssssiidi", $s_salutation, $s_name, $s_mobile, $s_dob, $s_aadhar, $s_occupation, $s_address, $s_nom_pf, $s_nom_esic, $s_dependent, $user_id);
                } else { // Insert new
                    $sql_spouse_update = "INSERT INTO spouse_details (user_id, salutation, name, mobile_number, date_of_birth, aadhar_no, occupation, address, is_nominee_pf, is_nominee_esic, is_dependent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_spouse = mysqli_prepare($link, $sql_spouse_update);
                    mysqli_stmt_bind_param($stmt_spouse, "isssssssiii", $user_id, $s_salutation, $s_name, $s_mobile, $s_dob, $s_aadhar, $s_occupation, $s_address, $s_nom_pf, $s_nom_esic, $s_dependent);
                }
                mysqli_stmt_execute($stmt_spouse);
                mysqli_stmt_close($stmt_spouse);
                mysqli_stmt_close($stmt_check_spouse);

            } else { // Not Married/Registered Partnership, or no spouse name provided: delete existing spouse details if any
                $sql_delete_spouse = "DELETE FROM spouse_details WHERE user_id = ?";
                $stmt_delete_spouse = mysqli_prepare($link, $sql_delete_spouse);
                mysqli_stmt_bind_param($stmt_delete_spouse, "i", $user_id);
                mysqli_stmt_execute($stmt_delete_spouse);
                mysqli_stmt_close($stmt_delete_spouse);
            }

            // --- Parent Details (Delete then Insert strategy) ---
            $sql_delete_parents = "DELETE FROM parent_details WHERE user_id = ?";
            $stmt_delete_parents = mysqli_prepare($link, $sql_delete_parents);
            mysqli_stmt_bind_param($stmt_delete_parents, "i", $user_id);
            mysqli_stmt_execute($stmt_delete_parents);
            mysqli_stmt_close($stmt_delete_parents);
            // (Note: old father_aadhar_file should be deleted from server if replaced/parent removed - add logic)

            $parent_types_data = [
                'Father' => ['salutation' => $_POST['fatherSalutation'] ?? '', 'name' => $_POST['fatherNameP2'] ?? '', 'mobile' => $_POST['fatherMobile'] ?? null, 'dob' => $_POST['fatherDob'] ?? null, 'aadhar' => $_POST['fatherAadharNo'] ?? '', 'aadhar_file' => $father_aadhar_file_path_db, 'occupation' => $_POST['fatherOccupation'] ?? '', 'address' => $_POST['fatherAddress'] ?? '', 'nomPF' => isset($_POST['fatherNomineePF']), 'nomESIC' => isset($_POST['fatherNomineeESIC']), 'dep' => isset($_POST['fatherDependent'])],
                'Mother' => ['salutation' => $_POST['motherSalutation'] ?? '', 'name' => $_POST['motherNameP2'] ?? '', 'mobile' => $_POST['motherMobile'] ?? null, 'dob' => $_POST['motherDob'] ?? null, 'aadhar' => $_POST['motherAadharNo'] ?? null, 'aadhar_file' => null, 'occupation' => $_POST['motherOccupation'] ?? '', 'address' => $_POST['motherAddress'] ?? '', 'nomPF' => isset($_POST['motherNomineePF']), 'nomESIC' => isset($_POST['motherNomineeESIC']), 'dep' => isset($_POST['motherDependent'])]
            ];
            foreach ($parent_types_data as $ptype => $details) {
                if (!empty(trim($details['name']))) {
                    $sql_parent = "INSERT INTO parent_details (user_id, parent_type, salutation, name, mobile_number, date_of_birth, aadhar_no, aadhar_file_path, occupation, address, is_nominee_pf, is_nominee_esic, is_dependent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_parent = mysqli_prepare($link, $sql_parent);
                    mysqli_stmt_bind_param($stmt_parent, "isssssssssiii", $user_id, $ptype, $details['salutation'], $details['name'], $details['mobile'], $details['dob'], $details['aadhar'], $details['aadhar_file'], $details['occupation'], $details['address'], $details['nomPF'], $details['nomESIC'], $details['dep']);
                    mysqli_stmt_execute($stmt_parent);
                    mysqli_stmt_close($stmt_parent);
                }
            }

            // --- Education, Certifications, Work Experience, Languages, References (Delete all old then Insert new) ---
            $tables_to_repopulate = ['user_education', 'user_certifications', 'user_work_experience', 'user_languages', 'user_references'];
            foreach($tables_to_repopulate as $table) {
                // Optional: Fetch old file paths from $table for $user_id to delete them from server
                $sql_del = "DELETE FROM $table WHERE user_id = ?";
                $stmt_del = mysqli_prepare($link, $sql_del);
                mysqli_stmt_bind_param($stmt_del, "i", $user_id);
                mysqli_stmt_execute($stmt_del);
                mysqli_stmt_close($stmt_del);
            }
            // Re-insert logic (similar to save_registration.php, but using updated $_POST data)
            // Education
            if (isset($_POST['eduQualification']) && is_array($_POST['eduQualification'])) {
                for ($i = 0; $i < count($_POST['eduQualification']); $i++) {
                    if (!empty(trim($_POST['eduQualification'][$i]))) {
                        $edu_doc_path = handleFileUpload('eduDocument', 'Education_Document', $errors, $user_id, $i) ?: ($_POST['existing_edu_document'][$i] ?? null);
                        $sql = "INSERT INTO user_education (user_id, qualification, board_university, subject, enrollment_year, passing_year, percentage_grade, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_bind_param($stmt, "isssiiss", $user_id, $_POST['eduQualification'][$i], $_POST['eduBoardUniversity'][$i], $_POST['eduSubject'][$i], $_POST['eduEnrollmentYear'][$i], $_POST['eduPassingYear'][$i], $_POST['eduPercentageGrade'][$i], $edu_doc_path);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
            // Certifications
            if (isset($_POST['certName']) && is_array($_POST['certName'])) { /* ... similar insert loop ... */ }
            // Work Experience
            if ($has_past_experience && isset($_POST['expCompanyName']) && is_array($_POST['expCompanyName'])) { /* ... similar insert loop ... */ }
            // Languages
            if (isset($_POST['langName']) && is_array($_POST['langName'])) { /* ... similar insert loop ... */ }
            // References
            if (isset($_POST['refName']) && is_array($_POST['refName'])) { /* ... similar insert loop ... */ }


            // --- Bank Details (Update or Insert) ---
            $bank_name = mysqli_real_escape_string($link, $_POST['bankName'] ?? '');
            if (!empty($bank_name)) {
                $bank_acc_no = mysqli_real_escape_string($link, $_POST['bankAccountNumber'] ?? '');
                $bank_ifsc = mysqli_real_escape_string($link, $_POST['bankIfsc'] ?? '');
                $bank_micr = mysqli_real_escape_string($link, $_POST['bankMicr'] ?? null);
                $bank_address = mysqli_real_escape_string($link, $_POST['bankAddress'] ?? '');
                
                $existing_bank_passbook = $_POST['existing_bank_passbook_doc'] ?? null;
                $bank_passbook_path = $existing_bank_passbook;
                $new_bank_passbook = handleFileUpload('bankPassbookDoc', 'Bank_Passbook', $errors, $user_id);
                if ($new_bank_passbook) {
                    if ($existing_bank_passbook && file_exists("../" . $existing_bank_passbook)) unlink("../" . $existing_bank_passbook);
                    $bank_passbook_path = $new_bank_passbook;
                }

                $sql_check_bank = "SELECT bank_detail_id FROM user_bank_details WHERE user_id = ?";
                $stmt_check_bank = mysqli_prepare($link, $sql_check_bank);
                mysqli_stmt_bind_param($stmt_check_bank, "i", $user_id);
                mysqli_stmt_execute($stmt_check_bank);
                $result_check_bank = mysqli_stmt_get_result($stmt_check_bank);
                if(mysqli_num_rows($result_check_bank) > 0){
                    $sql_bank_op = "UPDATE user_bank_details SET bank_name=?, account_number=?, ifsc_code=?, micr_code=?, bank_address=?, passbook_document_path=? WHERE user_id=?";
                    $stmt_bank = mysqli_prepare($link, $sql_bank_op);
                    mysqli_stmt_bind_param($stmt_bank, "ssssssi", $bank_name, $bank_acc_no, $bank_ifsc, $bank_micr, $bank_address, $bank_passbook_path, $user_id);
                } else {
                    $sql_bank_op = "INSERT INTO user_bank_details (user_id, bank_name, account_number, ifsc_code, micr_code, bank_address, passbook_document_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_bank = mysqli_prepare($link, $sql_bank_op);
                    mysqli_stmt_bind_param($stmt_bank, "issssss", $user_id, $bank_name, $bank_acc_no, $bank_ifsc, $bank_micr, $bank_address, $bank_passbook_path);
                }
                mysqli_stmt_execute($stmt_bank);
                mysqli_stmt_close($stmt_bank);
                mysqli_stmt_close($stmt_check_bank);
            } else { // If bank name is empty, consider deleting existing bank details
                $sql_delete_bank = "DELETE FROM user_bank_details WHERE user_id = ?";
                 // Also delete associated passbook file if any
                $stmt_delete_bank = mysqli_prepare($link, $sql_delete_bank);
                mysqli_stmt_bind_param($stmt_delete_bank, "i", $user_id);
                mysqli_stmt_execute($stmt_delete_bank);
                mysqli_stmt_close($stmt_delete_bank);
            }


            if (!empty($errors)) { // Check for file upload errors occurred during transaction
                 throw new Exception("Error during file uploads or subsequent data update. " . print_r($errors, true));
            }

            mysqli_commit($link);
            $_SESSION['message'] = "User details updated successfully for User ID: " . htmlspecialchars($user_id);
            $_SESSION['message_type'] = "success";
            header("location: ../view_user_details.php?user_id=" . $user_id);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($link);
            $errors['database'] = "Transaction Failed: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        // Redirect back to edit form with errors and pre-filled data
        // For simplicity, echoing errors here. Better to redirect and display on form.
        echo "<h3>Errors occurred during update:</h3><ul>";
        foreach($errors as $field => $err_msg) { 
            echo "<li><strong>".htmlspecialchars($field).":</strong> ".htmlspecialchars($err_msg)."</li>"; 
        }
        echo "</ul><p><a href='../edit_user_form.php?user_id=".htmlspecialchars($user_id)."' class='text-blue-500 hover:underline'>Go back to edit form</a></p>";
        exit;
    }

    mysqli_close($link);
} else {
    $_SESSION['message'] = "Invalid request or User ID not provided for update.";
    $_SESSION['message_type'] = "error";
    header("location: ../view_users.php");
    exit();
}
?>