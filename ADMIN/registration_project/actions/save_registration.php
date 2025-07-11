<?php
// /actions/save_registration.php
session_start();
require_once '../includes/db_config.php';

// --- Helper function for file uploads ---
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
            } else {
                $errors[$fieldName . "_" . $specificIndex] = "Error with " . $fieldName . " upload (code " . $_FILES[$fileInputName]['error'][$specificIndex] . ").";
                return null;
            }
        } else {
            return null; 
        }
    } else {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] != UPLOAD_ERR_NO_FILE) {
            if ($_FILES[$fileInputName]['error'] == 0) {
                $currentFileDetails = $_FILES[$fileInputName];
            } else {
                $errors[$fieldName] = "Error with " . $fieldName . " upload (code " . $_FILES[$fileInputName]['error'] . ").";
                return null;
            }
        } else {
            return null; 
        }
    }

    if (!$currentFileDetails) return null;

    $targetDirRoot = "../uploads/"; 
    $targetDirUser = $targetDirRoot;

    if ($userIdForPath) {
        $targetDirUser .= "user_" . $userIdForPath . "/";
        if (!is_dir($targetDirUser)) {
            if (!mkdir($targetDirUser, 0777, true) && !is_dir($targetDirUser)) { 
                 $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = "Failed to create user directory for uploads at " . $targetDirUser;
                 return null;
            }
        }
    }
    
    $originalFileName = $currentFileDetails["name"];
    $sanitizedFileName = preg_replace("/[^a-zA-Z0-9\-\._]/", "_", $originalFileName);
    $targetFilePath = $targetDirUser . time() . "_" . uniqid() . "_" . $sanitizedFileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');

    if (in_array($fileType, $allowedTypes)) {
        if ($currentFileDetails["size"] < 500000) { // 500 KB limit
            if (move_uploaded_file($currentFileDetails["tmp_name"], $targetFilePath)) {
                return str_replace('../', '', $targetFilePath); 
            } else {
                $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = "Error moving uploaded " . $fieldName . ". Check permissions for: " . $targetDirUser;
            }
        } else {
            $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = $fieldName . " is too large (max 500KB). Actual size: " . $currentFileDetails["size"];
        }
    } else {
        $errors[$fieldName . ($isIndexedFile ? "_".$specificIndex : "")] = "Invalid file type for " . $fieldName . " (allow jpg, png, pdf). Type was: " . $fileType;
    }
    return null;
}


$errors = []; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Sanitize and retrieve ALL data from $_POST ---
    // Page 1: Personal Details
    $name_as_for_document = mysqli_real_escape_string($link, $_POST['nameAsFor'] ?? '');
    $salutation = mysqli_real_escape_string($link, $_POST['salutation'] ?? '');
    $first_name = mysqli_real_escape_string($link, $_POST['firstName'] ?? '');
    $middle_name = !empty($_POST['middleName']) ? mysqli_real_escape_string($link, $_POST['middleName']) : null;
    $surname = !empty($_POST['surname']) ? mysqli_real_escape_string($link, $_POST['surname']) : null;
    $nationality = mysqli_real_escape_string($link, $_POST['nationality'] ?? '');
    $gender = mysqli_real_escape_string($link, $_POST['gender'] ?? '');
    $religion = mysqli_real_escape_string($link, $_POST['religion'] ?? '');
    $category_type = mysqli_real_escape_string($link, $_POST['categoryType'] ?? '');
    $date_of_birth = !empty($_POST['dob']) ? mysqli_real_escape_string($link, $_POST['dob']) : null;
    $celebrated_date_of_birth = !empty($_POST['celebratedDob']) ? mysqli_real_escape_string($link, $_POST['celebratedDob']) : null;
    
    // Page 1: Birth & Address Details
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

    // Page 1: Physical & Identification Details
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

    // Page 2: Family & Contact Details
    $marital_status = mysqli_real_escape_string($link, $_POST['maritalStatus'] ?? '');
    $your_email_id = !empty($_POST['yourEmailId']) ? mysqli_real_escape_string($link, $_POST['yourEmailId']) : null;
    $your_phone_number = mysqli_real_escape_string($link, $_POST['yourPhoneNumber'] ?? '');
    $emergency_contact_number = mysqli_real_escape_string($link, $_POST['emergencyContactNumber'] ?? '');

    // Page 3: Work Experience
    $has_past_experience = isset($_POST['pastExperience']) && $_POST['pastExperience'] === 'yes' ? 1 : 0;
    $has_pf_account = ($has_past_experience && isset($_POST['pfAccount']) && $_POST['pfAccount'] === 'yes') ? 1 : 0;
    $pf_account_established_code = $has_pf_account && !empty($_POST['pfAccountCode']) ? mysqli_real_escape_string($link, $_POST['pfAccountCode']) : null;
    $pf_uan_no = $has_pf_account && !empty($_POST['pfUanNo']) ? mysqli_real_escape_string($link, $_POST['pfUanNo']) : null;
    $pf_esi_no = $has_pf_account && !empty($_POST['pfEsiNo']) ? mysqli_real_escape_string($link, $_POST['pfEsiNo']) : null;
    $extra_curricular_activities = !empty($_POST['extraCurricular']) ? mysqli_real_escape_string($link, $_POST['extraCurricular']) : null;
    $hobbies = !empty($_POST['hobbies']) ? mysqli_real_escape_string($link, $_POST['hobbies']) : null;

    // Page 4: Other Details
    $medical_disability_exists = isset($_POST['medicalDisability']) && $_POST['medicalDisability'] === 'yes' ? 1 : 0;
    $medical_disability_details = $medical_disability_exists && !empty($_POST['medicalDisabilityText']) ? mysqli_real_escape_string($link, $_POST['medicalDisabilityText']) : null;
    $prev_employer_liability_exists = isset($_POST['prevEmployerLiability']) && $_POST['prevEmployerLiability'] === 'yes' ? 1 : 0;
    $prev_employer_liability_details = $prev_employer_liability_exists && !empty($_POST['prevEmployerLiabilityText']) ? mysqli_real_escape_string($link, $_POST['prevEmployerLiabilityText']) : null;
    $worked_simplex_group = isset($_POST['workedSimplex']) && $_POST['workedSimplex'] === 'yes' ? 1 : 0;
    $agree_posted_anywhere_india = isset($_POST['postedAnywhere']) && $_POST['postedAnywhere'] === 'yes' ? 1 : 0;
    $declaration_agreed = isset($_POST['declarationCheck']) ? 1 : 0;

    // --- Server-Side Validation ---
    if (empty(trim($first_name))) { $errors['firstName'] = "First name is required."; }
    if (empty(trim($salutation))) { $errors['salutation'] = "Salutation is required."; }
    if (empty(trim($nationality))) { $errors['nationality'] = "Nationality is required."; }
    if ($declaration_agreed == 0) { $errors['declarationCheck'] = "You must agree to the declaration.";}


    $pan_card_file_path = null; $aadhar_card_file_path = null; $dl_file_path = null;
    $passport_file_path = null; $profile_picture_path = null; $signature_path = null;
    $father_aadhar_file_path = null; $bank_passbook_path = null;


    if (empty($errors)) {
        mysqli_begin_transaction($link);
        try {
            // CORRECTED SQL: Added two more '?' placeholders
            $sql_user = "INSERT INTO users (name_as_for_document, salutation, first_name, middle_name, surname, nationality, gender, religion, category_type, date_of_birth, celebrated_date_of_birth, perm_birth_country, perm_birth_state, perm_birth_city_village, perm_address_line1, perm_address_line2, perm_address_line3, present_birth_country, present_birth_state, present_birth_city_village, present_address_line1, present_address_line2, present_address_line3, blood_group, weight_kg, height_cm, identification_marks, pan_available, pan_card_no, pan_card_file_path, aadhar_available, aadhar_number, aadhar_card_file_path, dl_available, dl_number, dl_file_path, dl_vehicle_type, dl_expiration_date, passport_available, passport_number, passport_file_path, passport_expiration_date, profile_picture_path, signature_path, marital_status, your_email_id, your_phone_number, emergency_contact_number, has_past_experience, has_pf_account, pf_account_established_code, pf_uan_no, pf_esi_no, extra_curricular_activities, hobbies, medical_disability_exists, medical_disability_details, prev_employer_liability_exists, prev_employer_liability_details, worked_simplex_group, agree_posted_anywhere_india, declaration_agreed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_user = mysqli_prepare($link, $sql_user);
            
            mysqli_stmt_bind_param($stmt_user, "ssssssssssssssssssssssssssdsssssississsssissssssssssssssssssii", 
                $name_as_for_document, $salutation, $first_name, $middle_name, $surname, $nationality, $gender, $religion, $category_type, $date_of_birth, $celebrated_date_of_birth, 
                $perm_birth_country, $perm_birth_state, $perm_birth_city_village, $perm_address_line1, $perm_address_line2, $perm_address_line3, 
                $present_birth_country, $present_birth_state, $present_birth_city_village, $present_address_line1, $present_address_line2, $present_address_line3,
                $blood_group, $weight_kg, $height_cm, $identification_marks,
                $pan_available, $pan_card_no, $pan_card_file_path, 
                $aadhar_available, $aadhar_number, $aadhar_card_file_path,
                $dl_available, $dl_number, $dl_file_path, $dl_vehicle_type, $dl_expiration_date,
                $passport_available, $passport_number, $passport_file_path, $passport_expiration_date,
                $profile_picture_path, $signature_path,
                $marital_status, $your_email_id, $your_phone_number, $emergency_contact_number,
                $has_past_experience, $has_pf_account, $pf_account_established_code, $pf_uan_no, $pf_esi_no,
                $extra_curricular_activities, $hobbies,
                $medical_disability_exists, $medical_disability_details,
                $prev_employer_liability_exists, $prev_employer_liability_details,
                $worked_simplex_group, $agree_posted_anywhere_india, $declaration_agreed
            );
            
            mysqli_stmt_execute($stmt_user);
            $user_id = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_user);

            if (!$user_id) {
                throw new Exception("Failed to create user record. " . mysqli_error($link));
            }

            $update_paths_sql_parts = [];
            $update_params = [];
            $update_types = "";
            
            if ($pan_available) { $path = handleFileUpload('panCardFile', 'PAN_Card_Document', $errors, $user_id); if ($path) { $update_paths_sql_parts[] = "pan_card_file_path = ?"; $update_params[] = $path; $update_types .= "s"; } }
            if ($aadhar_available) { $path = handleFileUpload('aadharCardFile', 'Aadhaar_Card_Document', $errors, $user_id); if ($path) { $update_paths_sql_parts[] = "aadhar_card_file_path = ?"; $update_params[] = $path; $update_types .= "s"; } }
            if ($dl_available) { $path = handleFileUpload('dlFile', 'Driving_Licence_Document', $errors, $user_id); if ($path) { $update_paths_sql_parts[] = "dl_file_path = ?"; $update_params[] = $path; $update_types .= "s"; } }
            if ($passport_available) { $path = handleFileUpload('passportFile', 'Passport_Document', $errors, $user_id); if ($path) { $update_paths_sql_parts[] = "passport_file_path = ?"; $update_params[] = $path; $update_types .= "s"; } }
            
            $path_profile = handleFileUpload('uploadPicture', 'Profile_Picture', $errors, $user_id); if ($path_profile) { $update_paths_sql_parts[] = "profile_picture_path = ?"; $update_params[] = $path_profile; $update_types .= "s"; }
            $path_signature = handleFileUpload('uploadSign', 'Signature', $errors, $user_id); if ($path_signature) { $update_paths_sql_parts[] = "signature_path = ?"; $update_params[] = $path_signature; $update_types .= "s"; }

            if (!empty($update_paths_sql_parts)) {
                $update_paths_sql = "UPDATE users SET " . implode(", ", $update_paths_sql_parts) . " WHERE user_id = ?";
                $update_params[] = $user_id;
                $update_types .= "i";
                $stmt_update_paths = mysqli_prepare($link, $update_paths_sql);
                mysqli_stmt_bind_param($stmt_update_paths, $update_types, ...$update_params);
                mysqli_stmt_execute($stmt_update_paths);
                mysqli_stmt_close($stmt_update_paths);
            }
            
            if (($marital_status === 'Married' || $marital_status === 'Registered Partnership') && !empty(trim($_POST['spouseName'] ?? ''))) {
                $s_salutation = mysqli_real_escape_string($link, $_POST['spouseSalutation'] ?? '');
                $s_name = mysqli_real_escape_string($link, $_POST['spouseName']);
                $s_mobile = !empty($_POST['spouseMobile']) ? mysqli_real_escape_string($link, $_POST['spouseMobile']) : null;
                $s_dob = !empty($_POST['spouseDob']) ? mysqli_real_escape_string($link, $_POST['spouseDob']) : null;
                $s_aadhar = !empty($_POST['spouseAadharNo']) ? mysqli_real_escape_string($link, $_POST['spouseAadharNo']) : null;
                $s_occupation = mysqli_real_escape_string($link, $_POST['spouseOccupation'] ?? '');
                $s_address = mysqli_real_escape_string($link, $_POST['spouseAddress'] ?? '');
                $s_nom_pf = isset($_POST['spouseNomineePF']) ? 1 : 0;
                $s_nom_esic = isset($_POST['spouseNomineeESIC']) ? 1 : 0;
                $s_dependent = isset($_POST['spouseDependent']) ? 1 : 0;

                $sql_spouse = "INSERT INTO spouse_details (user_id, salutation, name, mobile_number, date_of_birth, aadhar_no, occupation, address, is_nominee_pf, is_nominee_esic, is_dependent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_spouse = mysqli_prepare($link, $sql_spouse);
                mysqli_stmt_bind_param($stmt_spouse, "isssssssiii", $user_id, $s_salutation, $s_name, $s_mobile, $s_dob, $s_aadhar, $s_occupation, $s_address, $s_nom_pf, $s_nom_esic, $s_dependent);
                mysqli_stmt_execute($stmt_spouse);
                mysqli_stmt_close($stmt_spouse);
            }

            $father_aadhar_file_path_db = handleFileUpload('fatherAadharFile', "Father_Aadhaar_Document", $errors, $user_id);
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
            
            if (isset($_POST['eduQualification']) && is_array($_POST['eduQualification'])) {
                for ($i = 0; $i < count($_POST['eduQualification']); $i++) {
                    if (!empty(trim($_POST['eduQualification'][$i]))) {
                        $edu_doc_path = handleFileUpload('eduDocument', 'Education_Document', $errors, $user_id, $i);
                        $sql = "INSERT INTO user_education (user_id, qualification, board_university, subject, enrollment_year, passing_year, percentage_grade, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_bind_param($stmt, "isssiiss", $user_id, $_POST['eduQualification'][$i], $_POST['eduBoardUniversity'][$i], $_POST['eduSubject'][$i], $_POST['eduEnrollmentYear'][$i], $_POST['eduPassingYear'][$i], $_POST['eduPercentageGrade'][$i], $edu_doc_path);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
             if (isset($_POST['certName']) && is_array($_POST['certName'])) {
                for ($i = 0; $i < count($_POST['certName']); $i++) {
                    if (!empty(trim($_POST['certName'][$i]))) {
                        $cert_doc_path = handleFileUpload('certDocument', 'Certification_Document', $errors, $user_id, $i);
                        $cert_issued_on = !empty($_POST['certIssuedOn'][$i]) ? $_POST['certIssuedOn'][$i] : null;
                        $cert_valid_upto = !empty($_POST['certValidUpto'][$i]) ? $_POST['certValidUpto'][$i] : null;
                        $sql = "INSERT INTO user_certifications (user_id, certificate_name, issued_on, valid_upto, certificate_authority, document_path) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $_POST['certName'][$i], $cert_issued_on, $cert_valid_upto, $_POST['certAuthority'][$i], $cert_doc_path);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
            if ($has_past_experience && isset($_POST['expCompanyName']) && is_array($_POST['expCompanyName'])) {
                for ($i = 0; $i < count($_POST['expCompanyName']); $i++) {
                     if (!empty(trim($_POST['expCompanyName'][$i]))) {
                        $exp_letter_path = handleFileUpload('expLetter', 'Experience_Letter', $errors, $user_id, $i);
                        $exp_from = !empty($_POST['expFromDate'][$i]) ? $_POST['expFromDate'][$i] : null;
                        $exp_to = !empty($_POST['expToDate'][$i]) ? $_POST['expToDate'][$i] : null;
                        $sql = "INSERT INTO user_work_experience (user_id, company_name, designation, reason_for_leaving, salary_per_annum, roles_responsibility, competency, from_date, to_date, employer_contact_no, experience_letter_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_bind_param($stmt, "issssssssss", $user_id, $_POST['expCompanyName'][$i], $_POST['expDesignation'][$i], $_POST['expReasonLeaving'][$i], $_POST['expSalary'][$i], $_POST['expRolesResponsibility'][$i], $_POST['expCompetency'][$i], $exp_from, $exp_to, $_POST['expEmployerContact'][$i], $exp_letter_path);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
            if (isset($_POST['langName']) && is_array($_POST['langName'])) {
                foreach ($_POST['langName'] as $key => $lang_name_val) {
                    if (!empty(trim($lang_name_val))) {
                        $lang_name = mysqli_real_escape_string($link, $lang_name_val);
                        $can_speak = isset($_POST['langSpeak_'.$key]) ? 1 : 0;
                        $can_read = isset($_POST['langRead_'.$key]) ? 1 : 0;
                        $can_write = isset($_POST['langWrite_'.$key]) ? 1 : 0;
                        $can_understand = isset($_POST['langUnderstand_'.$key]) ? 1 : 0;

                        $sql_lang = "INSERT INTO user_languages (user_id, language_name, can_speak, can_read, can_write, can_understand) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_lang = mysqli_prepare($link, $sql_lang);
                        mysqli_stmt_bind_param($stmt_lang, "isiiii", $user_id, $lang_name, $can_speak, $can_read, $can_write, $can_understand);
                        mysqli_stmt_execute($stmt_lang);
                        mysqli_stmt_close($stmt_lang);
                    }
                }
            }
            if (isset($_POST['refName']) && is_array($_POST['refName'])) {
                 for ($i = 0; $i < count($_POST['refName']); $i++) {
                    if (!empty(trim($_POST['refName'][$i]))) {
                        $sql = "INSERT INTO user_references (user_id, reference_name, address, designation_position, relation, contact_no) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $_POST['refName'][$i], $_POST['refAddress'][$i], $_POST['refDesignation'][$i], $_POST['refRelation'][$i], $_POST['refContactNo'][$i]);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            }
            if (!empty(trim($_POST['bankName']))) {
                $bank_passbook_path = handleFileUpload('bankPassbookDoc', 'Bank_Passbook', $errors, $user_id);
                $sql = "INSERT INTO user_bank_details (user_id, bank_name, account_number, ifsc_code, micr_code, bank_address, passbook_document_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, "issssss", $user_id, $_POST['bankName'], $_POST['bankAccountNumber'], $_POST['bankIfsc'], $_POST['bankMicr'], $_POST['bankAddress'], $bank_passbook_path);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }


            if (!empty($errors)) { 
                 throw new Exception("Error during file uploads or subsequent data insertion. Please check error messages. " . print_r($errors, true));
            }

            mysqli_commit($link);
            $_SESSION['message'] = "Registration successful! User ID: " . htmlspecialchars($user_id);
            $_SESSION['message_type'] = "success";
            header("location: ../view_users.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($link);
            $errors['database'] = "Transaction Failed: " . $e->getMessage();
        }
    } // This closes the `if (empty($errors))` that wraps the try-catch block

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; 
        
        $error_string = "<h3>Errors occurred:</h3><ul>";
        foreach($errors as $field => $err_msg) { 
            $error_string .= "<li><strong>".htmlspecialchars($field).":</strong> ".htmlspecialchars($err_msg)."</li>"; 
        }
        $error_string .= "</ul><p><a href='../index.html' class='text-blue-500 hover:underline'>Go back to registration and try again.</a></p>";
        
        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Registration Error</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-red-100 p-8'><div class='max-w-md mx-auto bg-white p-6 rounded-lg shadow-md text-red-700'>".$error_string."</div></body></html>";
        exit;
    }

    mysqli_close($link);
} else {
    header("location: ../index.html");
    exit();
}
?>
