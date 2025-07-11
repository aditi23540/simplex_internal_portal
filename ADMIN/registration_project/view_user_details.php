<?php
// /view_user_details.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit;
}

// --- Main template user info logic for header/sidebar ---
$loggedIn_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$username_for_display = str_replace('.', ' ', $loggedIn_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display))); 
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 
$user_email_placeholder = htmlspecialchars($loggedIn_username) . '@simplexengg.in'; 
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';
$avatar_path = "assets/img/kaiadmin/default-avatar.png"; 

$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "user_master_db"; 
if ($empcode !== 'N/A') {
    $conn_avatar = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$conn_avatar->connect_error) {
        $sql_avatar = "SELECT users.profile_picture_path FROM users JOIN user_hr_details ON users.user_id = user_hr_details.user_id WHERE user_hr_details.employee_id_ascent = ?";
        if ($stmt_avatar = $conn_avatar->prepare($sql_avatar)) {
            $stmt_avatar->bind_param("s", $empcode); $stmt_avatar->execute();
            $result_avatar = $stmt_avatar->get_result();
            if ($result_avatar->num_rows > 0) {
                $row_avatar = $result_avatar->fetch_assoc();
                $db_avatar_path = $row_avatar['profile_picture_path']; 
                if (!empty($db_avatar_path) && file_exists("../registration_project/".$db_avatar_path)) { 
                    $avatar_path = "../registration_project/".$db_avatar_path; 
                }
            }
            $stmt_avatar->close();
        }
        $conn_avatar->close();
    }
}


// --- Logic to fetch details for the specific user being viewed ---
require_once 'includes/db_config.php';

$user_id = null; $user_data = null; $spouse_data = null; $father_data = null; $mother_data = null; 
$education_data = []; $certification_data = []; $experience_data = []; $language_data = []; $reference_data = []; 
$bank_data = null; $hr_onboarding_data = null; $it_setup_data = null;

if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $user_id = (int)$_GET['user_id'];
    
    // Paste all your data fetching PHP code here...
    $sql_user = "SELECT * FROM users WHERE user_id = ?";
    if ($stmt_user = mysqli_prepare($link, $sql_user)) { mysqli_stmt_bind_param($stmt_user, "i", $user_id); mysqli_stmt_execute($stmt_user); $result_user = mysqli_stmt_get_result($stmt_user); $user_data = mysqli_fetch_assoc($result_user); mysqli_stmt_close($stmt_user); }
    if (!$user_data) { $_SESSION['message'] = "User not found."; $_SESSION['message_type'] = "error"; header("Location: view_users.php"); exit(); }
    $sql_spouse = "SELECT * FROM spouse_details WHERE user_id = ?";
    if ($stmt_spouse = mysqli_prepare($link, $sql_spouse)) { mysqli_stmt_bind_param($stmt_spouse, "i", $user_id); mysqli_stmt_execute($stmt_spouse); $result_spouse = mysqli_stmt_get_result($stmt_spouse); $spouse_data = mysqli_fetch_assoc($result_spouse); mysqli_stmt_close($stmt_spouse); }
    $sql_parents = "SELECT * FROM parent_details WHERE user_id = ?";
    if ($stmt_parents = mysqli_prepare($link, $sql_parents)) { mysqli_stmt_bind_param($stmt_parents, "i", $user_id); mysqli_stmt_execute($stmt_parents); $result_parents = mysqli_stmt_get_result($stmt_parents); while($row = mysqli_fetch_assoc($result_parents)){ if($row['parent_type'] === 'Father') $father_data = $row; if($row['parent_type'] === 'Mother') $mother_data = $row; } mysqli_stmt_close($stmt_parents); }
    $sql_edu = "SELECT * FROM user_education WHERE user_id = ? ORDER BY education_id ASC";
    if ($stmt_edu = mysqli_prepare($link, $sql_edu)) { mysqli_stmt_bind_param($stmt_edu, "i", $user_id); mysqli_stmt_execute($stmt_edu); $result_edu = mysqli_stmt_get_result($stmt_edu); while ($row = mysqli_fetch_assoc($result_edu)) { $education_data[] = $row; } mysqli_stmt_close($stmt_edu); }
    $sql_cert = "SELECT * FROM user_certifications WHERE user_id = ? ORDER BY certification_id ASC";
    if ($stmt_cert = mysqli_prepare($link, $sql_cert)) { mysqli_stmt_bind_param($stmt_cert, "i", $user_id); mysqli_stmt_execute($stmt_cert); $result_cert = mysqli_stmt_get_result($stmt_cert); while ($row = mysqli_fetch_assoc($result_cert)) { $certification_data[] = $row; } mysqli_stmt_close($stmt_cert); }
    $sql_exp = "SELECT * FROM user_work_experience WHERE user_id = ? ORDER BY experience_id ASC";
    if ($stmt_exp = mysqli_prepare($link, $sql_exp)) { mysqli_stmt_bind_param($stmt_exp, "i", $user_id); mysqli_stmt_execute($stmt_exp); $result_exp = mysqli_stmt_get_result($stmt_exp); while ($row = mysqli_fetch_assoc($result_exp)) { $experience_data[] = $row; } mysqli_stmt_close($stmt_exp); }
    $sql_lang = "SELECT * FROM user_languages WHERE user_id = ? ORDER BY language_id ASC";
    if ($stmt_lang = mysqli_prepare($link, $sql_lang)) { mysqli_stmt_bind_param($stmt_lang, "i", $user_id); mysqli_stmt_execute($stmt_lang); $result_lang = mysqli_stmt_get_result($stmt_lang); while ($row = mysqli_fetch_assoc($result_lang)) { $language_data[] = $row; } mysqli_stmt_close($stmt_lang); }
    $sql_ref = "SELECT * FROM user_references WHERE user_id = ? ORDER BY reference_id ASC LIMIT 3";
    if ($stmt_ref = mysqli_prepare($link, $sql_ref)) { mysqli_stmt_bind_param($stmt_ref, "i", $user_id); mysqli_stmt_execute($stmt_ref); $result_ref = mysqli_stmt_get_result($stmt_ref); while ($row = mysqli_fetch_assoc($result_ref)) { $reference_data[] = $row; } mysqli_stmt_close($stmt_ref); }
    $sql_bank = "SELECT * FROM user_bank_details WHERE user_id = ?";
    if ($stmt_bank = mysqli_prepare($link, $sql_bank)) { mysqli_stmt_bind_param($stmt_bank, "i", $user_id); mysqli_stmt_execute($stmt_bank); $result_bank = mysqli_stmt_get_result($stmt_bank); $bank_data = mysqli_fetch_assoc($result_bank); mysqli_stmt_close($stmt_bank); }
    $sql_hr_details = "SELECT * FROM user_hr_details WHERE user_id = ?";
    if ($stmt_hr_details = mysqli_prepare($link, $sql_hr_details)) { mysqli_stmt_bind_param($stmt_hr_details, "i", $user_id); mysqli_stmt_execute($stmt_hr_details); $result_hr_details = mysqli_stmt_get_result($stmt_hr_details); $hr_onboarding_data = mysqli_fetch_assoc($result_hr_details); mysqli_stmt_close($stmt_hr_details); }
    $sql_it_details = "SELECT * FROM user_it_details WHERE user_id = ?";
    if ($stmt_it_details = mysqli_prepare($link, $sql_it_details)) { mysqli_stmt_bind_param($stmt_it_details, "i", $user_id); mysqli_stmt_execute($stmt_it_details); $result_it_details = mysqli_stmt_get_result($stmt_it_details); $it_setup_data = mysqli_fetch_assoc($result_it_details); mysqli_stmt_close($stmt_it_details); }

} else {
    $_SESSION['message'] = "Invalid User ID.";
    $_SESSION['message_type'] = "error";
    header("Location: view_users.php");
    exit();
}

function displayValue($value, $default = 'N/A') { return !empty($value) ? htmlspecialchars($value) : $default; }
function displayBool($value, $yes = 'Yes', $no = 'No') { return isset($value) ? ($value ? $yes : $no) : '<span class="text-muted">N/A</span>'; }
function displayFileLink($path, $text = 'View Document') {
    if (!empty($path) && file_exists($path)) { 
        return '<a href="' . htmlspecialchars($path) . '" target="_blank" class="btn btn-link btn-sm p-0">' . htmlspecialchars($text) . '</a>';
    }
    return '<span class="text-muted">Not Available</span>';
}

$page_specific_title = "User Details";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>SIMPLEX INTERNAL PORTAL - <?php echo htmlspecialchars($page_specific_title); ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script> WebFont.load({ google: { families: ["Public Sans:300,400,500,600,700", "Inter:400,500,600,700"] }, custom: { families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ["assets/css/fonts.min.css"], }, active: function () { sessionStorage.fonts = true; }, }); </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" /><link rel="stylesheet" href="assets/css/plugins.min.css" /><link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <style>
        .details-list dt { font-weight: 600; color: #575962; font-size: 0.9rem; }
        .details-list dd { color: #333; margin-bottom: 1rem; }
        .section-title { font-size: 1.25rem; font-weight: 600; padding-bottom: 0.75rem; border-bottom: 1px solid #dee2e6; margin-bottom: 1.5rem; }
        .dynamic-entry-view { border: 1px solid #eee; background-color: #f8f9fa; padding: 1.25rem; border-radius: 8px; margin-bottom: 1rem; }
        .dynamic-entry-view h5 { font-size: 1.1rem; font-weight: 600; color: #177dff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px dotted #ccc;}
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo"><div class="logo-header" data-background-color="dark"><a href="index.php" class="logo"><img src="assets/img/kaiadmin/simplex_icon_2.png" alt="navbar brand" class="navbar-brand" height="50" /></a><div class="nav-toggle"><button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button><button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button></div><button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button></div></div>
<div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item active"> 
                <a href="../dashboard/index.php"> 
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                 
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#base">
                  <i class="fas fa-layer-group"></i>
                  <p>SAP & REPORTS</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="base">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="../sap_api_tester/api_tester.php" target="_blank">
                        <span class="sub-item">SAP API TESTER</span>
                      </a>
                    </li>
                    <li>
                      <a href="https://my412439.s4hana.cloud.sap/" target="_blank">
                        <span class="sub-item">SAP Production</span>
                      </a>
                    </li>
                    
                    <li>
                      <a href="https://my409512.s4hana.cloud.sap" target="_blank">
                        <span class="sub-item">SAP Quality</span>
                      </a>
                    </li>
                     <li>
                      <a href="https://my407036.s4hana.cloud.sap" target="_blank">
                        <span class="sub-item">SAP Development</span>
                      </a>
                    </li>
                    <li>
                      <a href="../item_group_detail/index.php" target="_self"> 
                        <span class="sub-item">Item Group Details</span>
                      </a>
                    </li>
                    <li>
                      <a href="../Part_Description_Bifurcation/index.php" target="_self"> 
                        <span class="sub-item">Part Description Bifurcation</span>
                      </a>
                    </li>
                    <li>
                      <a href="../po_issues/po_issues_dashboard.php" target="_self"> 
                        <span class="sub-item">PO Issues</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
                    <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#hrCornerCollapse"> 
                      <i class="fas fa-users"></i> 
                      <p>HR CORNER</p>
                      <span class="caret"></span>
                    </a>
                    <div class="collapse" id="hrCornerCollapse">  
                      <ul class="nav nav-collapse">
                         <li>
                          <a href="../hr_dashboard/index.php" target="_self">
                            <span class="sub-item">HR Dashboard (Analytical Overview)</span>
                          </a>
                        </li>
                        <li>
                          <a href="../registration_project/registartion_page.php" target="_self">
                            <span class="sub-item">New Employee Registration</span>
                          </a>
                        </li>
                        <li>
                          <a href="../registration_project/view_users.php" target="_self">
                            <span class="sub-item">Employee Master</span>
                          </a>
                        </li>
                          <li>
                          <a href="../registration_project/hr_update_requests" target="_self">
                            <span class="sub-item">User's Profile Update Requests</span>
                          </a>
                        </li>
                       
                      </ul>
                    </div>
                  </li>
              <li class="nav-item">
                <a href="../project_management/index.html" target="_blank">
                  <i class="fas fa-project-diagram"></i>
                  <p>Project Manager(Early Phase)</p>
                </a>
              </li>
           
            </ul>
          </div>
        </div>
    </div>
        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo"><div class="logo-header" data-background-color="dark"><a href="index.php" class="logo"><img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" /></a><div class="nav-toggle"><button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button><button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button></div><button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button></div></div>
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <div class="navbar-brand-wrapper d-flex align-items-center me-auto"><a href="index.php" style="display: flex; align-items: center; text-decoration: none; color: #333;"><img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 40px; margin-right: 10px;" /><span style="font-size: 1.5rem; font-weight: 500;"> Simplex Engineering and Foundry Works </span></a></div>
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret"><a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false"><div class="avatar-sm"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User Avatar" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';"/></div><span class="profile-username"><span class="op-7">Hi,</span> <span class="fw-bold"><?php echo $username_for_display; ?></span></span></a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn"><div class="dropdown-user-scroll scrollbar-outer"><li><div class="user-box"><div class="avatar-lg"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="image profile" class="avatar-img rounded" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';"/></div><div class="u-text"><h4><?php echo $username_for_display; ?></h4><p class="text-muted"><?php echo $user_email_placeholder; ?></p><p class="text-muted">Emp Code: <?php echo $empcode; ?></p><p class="text-muted">Dept: <?php echo $department_display; ?></p><p class="text-muted">Role: <?php echo $employee_role_display; ?></p></div></div></li><li><div class="dropdown-divider"></div><a class="dropdown-item" href="#">My Profile</a><div class="dropdown-divider"></div><a class="dropdown-item" href="#">Account Setting</a><div class="dropdown-divider"></div><a class="dropdown-item" href="../../LOGIN/logout.php">Logout</a></li></div></ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <div class="container">
                <div class="page-inner">
                    <?php if ($user_data): ?>
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title fw-bold">User Registration Details</h4>
                                    <a href="view_users.php" class="btn btn-secondary btn-round ms-auto"><i class="fas fa-arrow-left"></i> Back to Employee Master</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <h3><?php echo displayValue($user_data['first_name'] . ' ' . $user_data['surname']); ?></h3>
                                    <p class="text-muted">Applicant ID: <?php echo htmlspecialchars($user_data['user_id']); ?></p>
                                </div>

                                <section class="details-section">
                                    <h4 class="section-title">Personal Identification</h4>
                                    <dl class="row details-list">
                                 <div class="col-md-4"><dt>Name as per Document</dt><dd><?php echo displayValue($user_data['name_as_for_document']); ?></dd></div>
                                 <div class="col-md-4"><dt>Salutation</dt><dd><?php echo displayValue($user_data['salutation']); ?></dd></div>
                                 <div class="col-md-4"><dt>First Name</dt><dd><?php echo displayValue($user_data['first_name']); ?></dd></div>
                                 <div class="col-md-4"><dt>Middle Name</dt><dd><?php echo displayValue($user_data['middle_name']); ?></dd></div>
                                 <div class="col-md-4"><dt>Surname</dt><dd><?php echo displayValue($user_data['surname']); ?></dd></div>
                                 <div class="col-md-4"><dt>Nationality</dt><dd><?php echo displayValue($user_data['nationality']); ?></dd></div>
                                 <div class="col-md-4"><dt>Gender</dt><dd><?php echo displayValue($user_data['gender']); ?></dd></div>
                                 <div class="col-md-4"><dt>Religion</dt><dd><?php echo displayValue($user_data['religion']); ?></dd></div>
                                 <div class="col-md-4"><dt>Category Type</dt><dd><?php echo displayValue($user_data['category_type']); ?></dd></div>
                                 <div class="col-md-4"><dt>Date of Birth</dt><dd><?php echo displayValue($user_data['date_of_birth']); ?></dd></div>
                                 <div class="col-md-4"><dt>Celebrated Date of Birth</dt><dd><?php echo displayValue($user_data['celebrated_date_of_birth']); ?></dd></div>
                                    </dl>
                                </section>

                                <section class="details-section">
                                     <div>
                <h4 class="sub-section-title">Permanent Address</h4>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Birth Country</dt><dd><?php echo displayValue($user_data['perm_birth_country']); ?></dd></div>
                    <div class="col-md-4"><dt>Birth State</dt><dd><?php echo displayValue($user_data['perm_birth_state']); ?></dd></div>
                    <div class="col-md-4"><dt>Birth City/Village</dt><dd><?php echo displayValue($user_data['perm_birth_city_village']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address Line 1</dt><dd><?php echo displayValue($user_data['perm_address_line1']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address Line 2</dt><dd><?php echo displayValue($user_data['perm_address_line2']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address Line 3</dt><dd><?php echo displayValue($user_data['perm_address_line3']); ?></dd></div>
                </dl>
            </div>
                  <div class="mt-6">
                     <h4 class="sub-section-title">Present Address</h4>
                   <dl class="row details-list">
                    <div class="col-md-4"><dt>Birth Country</dt><dd><?php echo displayValue($user_data['present_birth_country']); ?></dd></div>
                    <div class="col-md-4"><dt>Birth State</dt><dd><?php echo displayValue($user_data['present_birth_state']); ?></dd></div>
                    <div class="col-md-4"><dt>Birth City/Village</dt><dd><?php echo displayValue($user_data['present_birth_city_village']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address Line 1</dt><dd><?php echo displayValue($user_data['present_address_line1']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address Line 2</dt><dd><?php echo displayValue($user_data['present_address_line2']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address Line 3</dt><dd><?php echo displayValue($user_data['present_address_line3']); ?></dd></div>
                 </dl>
             </div>
                                </section>

                               <section class="details-section">
            <h4 class="section-title">Physical & Identification Documents</h4>
            <dl class="row details-list">
                <div class="col-md-4"><dt>Blood Group</dt><dd><?php echo displayValue($user_data['blood_group']); ?></dd></div>
                <div class="col-md-4"><dt>Weight (KG)</dt><dd><?php echo displayValue($user_data['weight_kg']); ?></dd></div>
                <div class="col-md-4"><dt>Height (CM)</dt><dd><?php echo displayValue($user_data['height_cm']); ?></dd></div>
                <div class="col-md-4 col-span-full"><dt>Identification Marks</dt><dd><?php echo displayValue($user_data['identification_marks']); ?></dd></div>

                <div class="col-md-4"><dt>PAN Card Available</dt><dd><?php echo displayBool($user_data['pan_available']); ?></dd></div>
                <?php if ($user_data['pan_available']): ?>
                    <div class="col-md-4"><dt>PAN Card No.</dt><dd><?php echo displayValue($user_data['pan_card_no']); ?></dd></div>
                    <div class="col-md-4"><dt>PAN Document</dt><dd><?php echo displayFileLink($user_data['pan_card_file_path']); ?></dd></div>
                <?php endif; ?>

                <div class="col-md-4"><dt>Aadhaar Card Available</dt><dd><?php echo displayBool($user_data['aadhar_available']); ?></dd></div>
                 <?php if ($user_data['aadhar_available']): ?>
                    <div class="col-md-4"><dt>Aadhaar Number</dt><dd><?php echo displayValue($user_data['aadhar_number']); ?></dd></div>
                    <div class="col-md-4"><dt>Aadhaar Document</dt><dd><?php echo displayFileLink($user_data['aadhar_card_file_path']); ?></dd></div>
                <?php endif; ?>

                <div class="col-md-4"><dt>Driving Licence Available</dt><dd><?php echo displayBool($user_data['dl_available']); ?></dd></div>
                <?php if ($user_data['dl_available']): ?>
                    <div class="col-md-4"><dt>DL Number</dt><dd><?php echo displayValue($user_data['dl_number']); ?></dd></div>
                    <div class="col-md-4"><dt>DL Vehicle Type</dt><dd><?php echo displayValue($user_data['dl_vehicle_type']); ?></dd></div>
                    <div class="col-md-4"><dt>DL Expiry Date</dt><dd><?php echo displayValue($user_data['dl_expiration_date']); ?></dd></div>
                    <div class="col-md-4"><dt>DL Document</dt><dd><?php echo displayFileLink($user_data['dl_file_path']); ?></dd></div>
                <?php endif; ?>
                
                <div class="col-md-4"><dt>Passport Available</dt><dd><?php echo displayBool($user_data['passport_available']); ?></dd></div>
                 <?php if ($user_data['passport_available']): ?>
                    <div class="col-md-4"><dt>Passport Number</dt><dd><?php echo displayValue($user_data['passport_number']); ?></dd></div>
                    <div class="col-md-4"><dt>Passport Expiry</dt><dd><?php echo displayValue($user_data['passport_expiration_date']); ?></dd></div>
                    <div class="col-md-4"><dt>Passport Document</dt><dd><?php echo displayFileLink($user_data['passport_file_path']); ?></dd></div>
                <?php endif; ?>

                <div class="col-md-4"><dt>Profile Picture</dt><dd><?php echo displayFileLink($user_data['profile_picture_path'], 'View Picture'); ?></dd></div>
                <div class="col-md-4"><dt>Signature</dt><dd><?php echo displayFileLink($user_data['signature_path'], 'View Signature'); ?></dd></div>
            </dl>
        </section>

         <section class="details-section">
            <h4 class="section-title">Family & Contact Information</h4>
            <dl class="data-grid">
                <div class="col-md-4"><dt>Marital Status</dt><dd><?php echo displayValue($user_data['marital_status']); ?></dd></div>
            </dl>

            <?php if ($spouse_data): ?>
            <div id="viewSpouseDetails" class="mt-6">
                <h4 class="sub-section-title">Spouse's Details</h4>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Salutation</dt><dd><?php echo displayValue($spouse_data['salutation']); ?></dd></div>
                    <div class="col-md-4"><dt>Name</dt><dd><?php echo displayValue($spouse_data['name']); ?></dd></div>
                    <div class="col-md-4"><dt>Mobile Number</dt><dd><?php echo displayValue($spouse_data['mobile_number']); ?></dd></div>
                    <div class="col-md-4"><dt>Date of Birth</dt><dd><?php echo displayValue($spouse_data['date_of_birth']); ?></dd></div>
                    <div class="col-md-4"><dt>Aadhar No.</dt><dd><?php echo displayValue($spouse_data['aadhar_no']); ?></dd></div>
                    <div class="col-md-4"><dt>Occupation</dt><dd><?php echo displayValue($spouse_data['occupation']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address</dt><dd><?php echo displayValue($spouse_data['address']); ?></dd></div>
                    <div class="col-md-4"><dt>Nominee (PF)</dt><dd><?php echo displayBool($spouse_data['is_nominee_pf']); ?></dd></div>
                    <div class="col-md-4"><dt>Nominee (ESIC)</dt><dd><?php echo displayBool($spouse_data['is_nominee_esic']); ?></dd></div>
                    <div class="col-md-4"><dt>Dependent</dt><dd><?php echo displayBool($spouse_data['is_dependent']); ?></dd></div>
                </dl>
            </div>
            <?php endif; ?>

            <?php if ($father_data): ?>
            <div class="mt-6">
                <h4 class="sub-section-title">Father's Details</h4>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Salutation</dt><dd><?php echo displayValue($father_data['salutation']); ?></dd></div>
                    <div class="col-md-4"><dt>Name</dt><dd><?php echo displayValue($father_data['name']); ?></dd></div>
                    <div class="col-md-4"><dt>Mobile</dt><dd><?php echo displayValue($father_data['mobile_number']); ?></dd></div>
                    <div class="col-md-4"><dt>DOB</dt><dd><?php echo displayValue($father_data['date_of_birth']); ?></dd></div>
                    <div class="col-md-4"><dt>Aadhar No.</dt><dd><?php echo displayValue($father_data['aadhar_no']); ?></dd></div>
                    <div class="col-md-4"><dt>Aadhar Document</dt><dd><?php echo displayFileLink($father_data['aadhar_file_path']); ?></dd></div>
                    <div class="col-md-4"><dt>Occupation</dt><dd><?php echo displayValue($father_data['occupation']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address</dt><dd><?php echo displayValue($father_data['address']); ?></dd></div>
                    <div class="col-md-4"><dt>Nominee (PF)</dt><dd><?php echo displayBool($father_data['is_nominee_pf']); ?></dd></div>
                    <div class="col-md-4"><dt>Nominee (ESIC)</dt><dd><?php echo displayBool($father_data['is_nominee_esic']); ?></dd></div>
                    <div class="col-md-4"><dt>Dependent</dt><dd><?php echo displayBool($father_data['is_dependent']); ?></dd></div>
                </dl>
            </div>
            <?php endif; ?>
            
            <?php if ($mother_data): ?>
            <div class="mt-6">
                <h4 class="sub-section-title">Mother's Details</h4>
                 <dl class="row details-list">
                    <div class="col-md-4"><dt>Salutation</dt><dd><?php echo displayValue($mother_data['salutation']); ?></dd></div>
                    <div class="col-md-4"><dt>Name</dt><dd><?php echo displayValue($mother_data['name']); ?></dd></div>
                    <div class="col-md-4"><dt>Mobile</dt><dd><?php echo displayValue($mother_data['mobile_number']); ?></dd></div>
                    <div class="col-md-4"><dt>DOB</dt><dd><?php echo displayValue($mother_data['date_of_birth']); ?></dd></div>
                    <div class="col-md-4"><dt>Aadhar No.</dt><dd><?php echo displayValue($mother_data['aadhar_no']); ?></dd></div>
                    <div class="col-md-4"><dt>Occupation</dt><dd><?php echo displayValue($mother_data['occupation']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Address</dt><dd><?php echo displayValue($mother_data['address']); ?></dd></div>
                    <div class="col-md-4"><dt>Nominee (PF)</dt><dd><?php echo displayBool($mother_data['is_nominee_pf']); ?></dd></div>
                    <div class="col-md-4"><dt>Nominee (ESIC)</dt><dd><?php echo displayBool($mother_data['is_nominee_esic']); ?></dd></div>
                    <div class="col-md-4"><dt>Dependent</dt><dd><?php echo displayBool($mother_data['is_dependent']); ?></dd></div>
                </dl>
            </div>
            <?php endif; ?>

            <div class="mt-6">
                <h4 class="sub-section-title">Your Contact Details</h4>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Email ID</dt><dd><?php echo displayValue($user_data['your_email_id']); ?></dd></div>
                    <div class="col-md-4"><dt>Phone Number</dt><dd><?php echo displayValue($user_data['your_phone_number']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Emergency Contact Number</dt><dd><?php echo displayValue($user_data['emergency_contact_number']); ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="details-section">
            <h4 class="section-title">Education, Certifications & Work History</h4>
            <?php if (!empty($education_data)): ?>
            <div>
                <h4 class="sub-section-title">Educational Qualifications</h4>
                <?php foreach($education_data as $index => $edu): ?>
                <div class="dynamic-entry-view <?php if($index > 0) echo 'mt-4'; ?>">
                    <h5>Qualification <?php echo $index + 1; ?></h5>
                    <dl class="row details-list">
                        <div class="col-md-4"><dt>Qualification</dt><dd><?php echo displayValue($edu['qualification']); ?></dd></div>
                        <div class="col-md-4"><dt>Board/University</dt><dd><?php echo displayValue($edu['board_university']); ?></dd></div>
                        <div class="col-md-4"><dt>Subject</dt><dd><?php echo displayValue($edu['subject']); ?></dd></div>
                        <div class="col-md-4"><dt>Enrollment Year</dt><dd><?php echo displayValue($edu['enrollment_year']); ?></dd></div>
                        <div class="col-md-4"><dt>Passing Year</dt><dd><?php echo displayValue($edu['passing_year']); ?></dd></div>
                        <div class="col-md-4"><dt>Percentage/Grade</dt><dd><?php echo displayValue($edu['percentage_grade']); ?></dd></div>
                        <div class="col-md-4 col-span-full"><dt>Document</dt><dd><?php echo displayFileLink($edu['document_path']); ?></dd></div>
                    </dl>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($certification_data)): ?>
            <div class="mt-6">
                <h4 class="sub-section-title">Certifications</h4>
                 <?php foreach($certification_data as $index => $cert): ?>
                <div class="dynamic-entry-view <?php if($index > 0) echo 'mt-4'; ?>">
                    <h5>Certification <?php echo $index + 1; ?></h5>
                    <dl class="row details-list">
                        <div class="col-md-4"><dt>Certificate Name</dt><dd><?php echo displayValue($cert['certificate_name']); ?></dd></div>
                        <div class="col-md-4"><dt>Issued On</dt><dd><?php echo displayValue($cert['issued_on']); ?></dd></div>
                        <div class="col-md-4"><dt>Valid Upto</dt><dd><?php echo displayValue($cert['valid_upto']); ?></dd></div>
                        <div class="col-md-4"><dt>Certificate Authority</dt><dd><?php echo displayValue($cert['certificate_authority']); ?></dd></div>
                        <div class="col-md-4 col-span-full"><dt>Document</dt><dd><?php echo displayFileLink($cert['document_path']); ?></dd></div>
                    </dl>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($user_data['has_past_experience']): ?>
            <div class="mt-6">
                <h4 class="sub-section-title">Work Experience</h4>
                <dl class="row details-list mb-4">
                     <div class="col-md-4"><dt>Has Past Experience</dt><dd>Yes</dd></div>
                     <div class="col-md-4"><dt>Has PF Account</dt><dd><?php echo displayBool($user_data['has_pf_account']); ?></dd></div>
                     <?php if ($user_data['has_pf_account']): ?>
                     <div class="col-md-4"><dt>PF Established Code</dt><dd><?php echo displayValue($user_data['pf_account_established_code']); ?></dd></div>
                     <div class="col-md-4"><dt>UAN No.</dt><dd><?php echo displayValue($user_data['pf_uan_no']); ?></dd></div>
                     <div class="col-md-4"><dt>ESI No.</dt><dd><?php echo displayValue($user_data['pf_esi_no']); ?></dd></div>
                     <?php endif; ?>
                </dl>
                <?php if (!empty($experience_data)): ?>
                    <?php foreach($experience_data as $index => $exp): ?>
                    <div class="dynamic-entry-view <?php if($index > 0) echo 'mt-4'; ?>">
                        <h5>Experience <?php echo $index + 1; ?></h5>
                        <dl class="row details-list">
                            <div class="col-md-4"><dt>Company/Firm Name</dt><dd><?php echo displayValue($exp['company_name']); ?></dd></div>
                            <div class="col-md-4"><dt>Designation/Position</dt><dd><?php echo displayValue($exp['designation']); ?></dd></div>
                            <div class="col-md-4 col-span-full"><dt>Reason for Leaving</dt><dd><?php echo displayValue($exp['reason_for_leaving']); ?></dd></div>
                            <div class="col-md-4"><dt>Salary (Per Annum)</dt><dd><?php echo displayValue($exp['salary_per_annum']); ?></dd></div>
                            <div class="col-md-4 col-span-full"><dt>Roles & Responsibility</dt><dd><?php echo displayValue($exp['roles_responsibility']); ?></dd></div>
                            <div class="col-md-4 col-span-full"><dt>Competency</dt><dd><?php echo displayValue($exp['competency']); ?></dd></div>
                            <div class="col-md-4"><dt>From Date</dt><dd><?php echo displayValue($exp['from_date']); ?></dd></div>
                            <div class="col-md-4"><dt>To Date</dt><dd><?php echo displayValue($exp['to_date']); ?></dd></div>
                            <div class="col-md-4"><dt>Employer Contact No.</dt><dd><?php echo displayValue($exp['employer_contact_no']); ?></dd></div>
                            <div class="col-md-4"><dt>Experience Letter</dt><dd><?php echo displayFileLink($exp['experience_letter_path']); ?></dd></div>
                        </dl>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">No specific work experiences listed despite having past experience.</p>
                <?php endif; ?>
            </div>
             <?php else: ?>
                <div class="mt-6"><h4 class="sub-section-title">Work Experience</h4><p class="text-gray-500">No past work experience indicated.</p></div>
            <?php endif; ?>
            
            <div class="mt-6">
                <dl class="row details-list">
                    <div class="col-md-4 col-span-full"><dt>Extra Curricular Activities</dt><dd><?php echo displayValue($user_data['extra_curricular_activities']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Hobbies</dt><dd><?php echo displayValue($user_data['hobbies']); ?></dd></div>
                </dl>
            </div>
        </section>

         <section class="details-section">
            <h4 class="section-title">Personal Identification</h4>
            <?php if (!empty($language_data)): ?>
            <div>
                <h4 class="sub-section-title">Language Proficiency</h4>
                <?php foreach($language_data as $index => $lang): ?>
                <div class="dynamic-entry-view <?php if($index > 0) echo 'mt-4'; ?>">
                    <h5>Language <?php echo $index + 1; ?>: <?php echo displayValue($lang['language_name']); ?></h5>
                    <dl class="row details-list">
                        <div class="col-md-4"><dt>Speak</dt><dd><?php echo displayBool($lang['can_speak']); ?></dd></div>
                        <div class="col-md-4"><dt>Read</dt><dd><?php echo displayBool($lang['can_read']); ?></dd></div>
                        <div class="col-md-4"><dt>Write</dt><dd><?php echo displayBool($lang['can_write']); ?></dd></div>
                        <div class="col-md-4"><dt>Understand</dt><dd><?php echo displayBool($lang['can_understand']); ?></dd></div>
                    </dl>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($reference_data[0])): // Check if at least first reference exists ?>
            <div class="mt-6">
                <h4 class="sub-section-title">Reference Details</h4>
                <?php foreach($reference_data as $index => $ref): ?>
                    <?php if ($ref): // Only display if reference data exists for this slot ?>
                    <div class="reference-block <?php if($index > 0) echo 'mt-6';?>">
                        <legend>Reference <?php echo $index + 1; ?></legend>
                        <dl class="row details-list">
                            <div class="col-md-4"><dt>Name</dt><dd><?php echo displayValue($ref['reference_name']); ?></dd></div>
                            <div class="col-md-4 col-span-full"><dt>Address</dt><dd><?php echo displayValue($ref['address']); ?></dd></div>
                            <div class="col-md-4"><dt>Designation/Position</dt><dd><?php echo displayValue($ref['designation_position']); ?></dd></div>
                            <div class="col-md-4"><dt>Relation</dt><dd><?php echo displayValue($ref['relation']); ?></dd></div>
                            <div class="col-md-4"><dt>Contact No.</dt><dd><?php echo displayValue($ref['contact_no']); ?></dd></div>
                        </dl>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($bank_data): ?>
            <div class="mt-6">
                <h4 class="sub-section-title">Bank Details</h4>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Name of Bank</dt><dd><?php echo displayValue($bank_data['bank_name']); ?></dd></div>
                    <div class="col-md-4"><dt>Account Number</dt><dd><?php echo displayValue($bank_data['account_number']); ?></dd></div>
                    <div class="col-md-4"><dt>IFSC Number</dt><dd><?php echo displayValue($bank_data['ifsc_code']); ?></dd></div>
                    <div class="col-md-4"><dt>MICR Code</dt><dd><?php echo displayValue($bank_data['micr_code']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Bank Address</dt><dd><?php echo displayValue($bank_data['bank_address']); ?></dd></div>
                    <div class="col-md-4"><dt>Passbook Document</dt><dd><?php echo displayFileLink($bank_data['passbook_document_path']); ?></dd></div>
                </dl>
            </div>
            <?php endif; ?>
            
            <?php if ($hr_onboarding_data): ?>
            <section class="view-section mt-6">
                <legend>HR Onboarding Details</legend>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Unit</dt><dd><?php echo displayValue($hr_onboarding_data['unit']); ?></dd></div>
                    <div class="col-md-4"><dt>Department</dt><dd><?php echo displayValue($hr_onboarding_data['department']); ?></dd></div>
                    <div class="col-md-4"><dt>Designation</dt><dd><?php echo displayValue($hr_onboarding_data['designation']); ?></dd></div>
                    <div class="col-md-4"><dt>Date of Joining</dt><dd><?php echo displayValue($hr_onboarding_data['date_of_joining']); ?></dd></div>
                    <div class="col-md-4"><dt>Category</dt><dd><?php echo displayValue($hr_onboarding_data['category']); ?></dd></div>
                    <div class="col-md-4"><dt>Grade</dt><dd><?php echo displayValue($hr_onboarding_data['grade']); ?></dd></div>
                    <div class="col-md-4"><dt>Status</dt><dd><?php echo displayValue($hr_onboarding_data['status']); ?></dd></div>
                    <div class="col-md-4"><dt>Leave Group</dt><dd><?php echo displayValue($hr_onboarding_data['leave_group']); ?></dd></div>
                    <div class="col-md-4"><dt>Shift Schedule</dt><dd><?php echo displayValue($hr_onboarding_data['shift_schedule']); ?></dd></div>
                    <div class="col-md-4"><dt>Reporting Incharge</dt><dd><?php echo displayValue($hr_onboarding_data['reporting_incharge']); ?></dd></div>
                    <div class="col-md-4"><dt>Department Head</dt><dd><?php echo displayValue($hr_onboarding_data['department_head']); ?></dd></div>
                    <div class="col-md-4"><dt>Attendance Policy</dt><dd><?php echo displayValue($hr_onboarding_data['attendance_policy']); ?></dd></div>
                    <div class="col-md-4"><dt>Employee ID (Ascent)</dt><dd><?php echo displayValue($hr_onboarding_data['employee_id_ascent']); ?></dd></div>
                    <div class="col-md-4"><dt>Role of Employee</dt><dd><?php echo displayValue($hr_onboarding_data['employee_role']); ?></dd></div>
                    <div class="col-md-4"><dt>Payroll Code</dt><dd><?php echo displayValue($hr_onboarding_data['payroll_code']); ?></dd></div>
                    <div class="col-md-4"><dt>Vaccination Code</dt><dd><?php echo displayValue($hr_onboarding_data['vaccination_code']); ?></dd></div>
                </dl>
            </section>
            <?php else: ?>
            <section class="details-section mt-6">
                <legend>HR Onboarding Details</legend>
                <p class="text-gray-500">No HR onboarding details found for this user yet.</p>
            </section>
            <?php endif; ?>

            <?php if ($it_setup_data): ?>
            <section class="details-section mt-6">
                <legend>IT Setup Details</legend>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Official Phone Number</dt><dd><?php echo displayValue($it_setup_data['official_phone_number']); ?></dd></div>
                    <div class="col-md-4"><dt>Official Email</dt><dd><?php echo displayValue($it_setup_data['official_email']); ?></dd></div>
                    <div class="col-md-4"><dt>Intercom Number</dt><dd><?php echo displayValue($it_setup_data['intercom_number']); ?></dd></div>
                </dl>
            </section>
            <?php else: ?>
            <section class="details-section mt-6">
                <legend>IT Setup Details</legend>
                <p class="text-gray-500">No IT setup details found for this user yet.</p>
            </section>
            <?php endif; ?>


            <div class="mt-6">
                <h4 class="sub-section-title">Other Details & Declarations</h4>
                <dl class="row details-list">
                    <div class="col-md-4"><dt>Any Medical Disability?</dt><dd><?php echo displayBool($user_data['medical_disability_exists']); ?></dd></div>
                    <?php if ($user_data['medical_disability_exists']): ?>
                        <div class="col-md-4 col-span-full"><dt>Disability Details</dt><dd><?php echo displayValue($user_data['medical_disability_details']); ?></dd></div>
                    <?php endif; ?>
                    <div class="col-md-4"><dt>Liability with Previous Employer?</dt><dd><?php echo displayBool($user_data['prev_employer_liability_exists']); ?></dd></div>
                     <?php if ($user_data['prev_employer_liability_exists']): ?>
                        <div class="col-md-4 col-span-full"><dt>Liability Details</dt><dd><?php echo displayValue($user_data['prev_employer_liability_details']); ?></dd></div>
                    <?php endif; ?>
                    <div class="col-md-4"><dt>Worked for Simplex Group Before?</dt><dd><?php echo displayBool($user_data['worked_simplex_group']); ?></dd></div>
                    <div class="col-md-4"><dt>Agree to be Posted Anywhere in India?</dt><dd><?php echo displayBool($user_data['agree_posted_anywhere_india']); ?></dd></div>
                    <div class="col-md-4 col-span-full"><dt>Declaration Agreed</dt><dd><?php echo displayBool($user_data['declaration_agreed']); ?></dd></div>
                </dl>
            </div>
        </section>

                            </div>
                            <div class="card-footer text-end">
                                <a href="it_setup_form.php?user_id=<?php echo $user_id; ?>" class="btn btn-secondary"><?php echo $it_setup_data ? 'Edit IT Details' : 'Add IT Details'; ?></a>
                                <a href="hr_onboarding_form.php?user_id=<?php echo $user_id; ?>" class="btn btn-success"><?php echo $hr_onboarding_data ? 'Edit HR Details' : 'Add HR Details'; ?></a>
                                <a href="edit_user_form.php?user_id=<?php echo $user_id; ?>" class="btn btn-warning">Edit Basic Registration</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">User not found or invalid ID provided. Please <a href="view_users.php" class="alert-link">go back to the user list</a>.</div>
                    <?php endif; ?>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left"><ul class="nav"></ul></nav>
                    <div class="copyright"><?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Abhimanyu</a></div>
                    <div>For <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering & Foundry Works PVT. LTD.</a>.</div>
                </div>
            </footer>
        </div>
    </div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script> 
</body>
</html>
<?php 
if (isset($link) && $link instanceof mysqli) {
    $link->close(); 
}
?>