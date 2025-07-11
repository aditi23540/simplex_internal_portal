<?php
// my_profile.php
// Final, working version.

// --- 1. SESSION CONFIGURATION & START ---
// This line is essential for making the session work across different directories.
// It must be called BEFORE session_start().
session_set_cookie_params(['path' => '/']);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 2. AUTHENTICATION CHECK ---
// Checks for login status. If it fails, redirects to login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// --- 3. GET USER IDENTIFIER FROM SESSION & ESTABLISH DB CONNECTION ---
// Your login script provides 'empcode', so we must use it.
if (!isset($_SESSION['empcode']) || empty($_SESSION['empcode'])) {
    die("CRITICAL ERROR: Your session is missing the Employee Code. Please log out and log in again.");
}
$empcode = $_SESSION['empcode'];

require_once 'includes/db_config.php';
if (!$link) { die("Database connection failed. Check includes/db_config.php"); }

// --- 4. FIND THE USER'S PRIMARY ID (user_id) USING THE EMPLOYEE CODE ---
// This is the key step that makes the page work.
$user_id = null;
$sql_get_id = "SELECT user_id FROM user_hr_details WHERE employee_id_ascent = ?";
if ($stmt_get_id = $link->prepare($sql_get_id)) {
    $stmt_get_id->bind_param("s", $empcode);
    $stmt_get_id->execute();
    $result_get_id = $stmt_get_id->get_result();
    if ($row_id = $result_get_id->fetch_assoc()) {
        // PERMANENT FIX: We force the user_id to be a number (integer).
        $user_id = (int) $row_id['user_id'];
    }
    $stmt_get_id->close();
}

// If we could not find a user_id, we cannot proceed.
if ($user_id === null) {
    die("Error: Your Employee Code was found, but it is not linked to a user profile in the portal. Please contact HR to resolve this data issue.");
}

// --- 5. SETUP PAGE VARIABLES & FETCH ALL PROFILE DATA using the found user_id ---
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$loggedIn_username = $_SESSION['username'] ?? 'User';
$username_for_display = htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $loggedIn_username))));
$user_email_placeholder = htmlspecialchars($loggedIn_username) . '@simplexengg.in';
$department_display = $_SESSION['department'] ?? 'N/A';
$employee_role_display = $_SESSION['employee_role'] ?? 'N/A';
$avatar_path = "assets/img/kaiadmin/default-avatar.png";

// Initialize data variables
$user_data = null; $spouse_data = null; $father_data = null; $mother_data = null;
$education_data = []; $certification_data = []; $experience_data = []; $language_data = []; $reference_data = [];
$bank_data = null; $has_pending_request = false; $pending_request_date = '';

// Avatar fetching using the found user_id for reliability
$sql_avatar = "SELECT profile_picture_path FROM users WHERE user_id = ?";
if ($stmt_avatar = $link->prepare($sql_avatar)) {
    $stmt_avatar->bind_param("i", $user_id);
    $stmt_avatar->execute();
    $result_avatar = $stmt_avatar->get_result();
    if($row_avatar = $result_avatar->fetch_assoc()){
        if (!empty($row_avatar['profile_picture_path']) && file_exists($row_avatar['profile_picture_path'])) {
            $avatar_path = $row_avatar['profile_picture_path'];
        }
    }
    $stmt_avatar->close();
}

// Check for pending update request
$sql_check = "SELECT requested_at FROM user_update_requests WHERE user_id = ? AND request_status = 'pending'";
if($stmt_check = mysqli_prepare($link, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "i", $user_id); mysqli_stmt_execute($stmt_check);
    if ($pending_request_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check))) {
        $has_pending_request = true;
        $pending_request_date = date('F j, Y, g:i a', strtotime($pending_request_data['requested_at']));
    }
    mysqli_stmt_close($stmt_check);
}

// Fetch main user data
$sql_user = "SELECT * FROM users WHERE user_id = ?";
if ($stmt_user = mysqli_prepare($link, $sql_user)) { mysqli_stmt_bind_param($stmt_user, "i", $user_id); mysqli_stmt_execute($stmt_user); $result_user = mysqli_stmt_get_result($stmt_user); $user_data = mysqli_fetch_assoc($result_user); mysqli_stmt_close($stmt_user); }

if (!$user_data) {
    die("Data Integrity Error: A profile for your User ID (#" . htmlspecialchars($user_id) . ") could not be found in the main users table. Please contact your administrator to fix your profile data.");
}

// Fetch all other related profile data
$sql_spouse = "SELECT * FROM spouse_details WHERE user_id = ?";
if ($stmt_spouse = mysqli_prepare($link, $sql_spouse)) { mysqli_stmt_bind_param($stmt_spouse, "i", $user_id); mysqli_stmt_execute($stmt_spouse); $result_spouse = mysqli_stmt_get_result($stmt_spouse); $spouse_data = mysqli_fetch_assoc($result_spouse); mysqli_stmt_close($stmt_spouse); }
$sql_parents = "SELECT * FROM parent_details WHERE user_id = ?";
if ($stmt_parents = mysqli_prepare($link, $sql_parents)) {
    mysqli_stmt_bind_param($stmt_parents, "i", $user_id); mysqli_stmt_execute($stmt_parents); $result_parents = mysqli_stmt_get_result($stmt_parents);
    while($row = mysqli_fetch_assoc($result_parents)){
        if($row['parent_type'] === 'Father') { $father_data = $row; }
        if($row['parent_type'] === 'Mother') { $mother_data = $row; }
    }
    mysqli_stmt_close($stmt_parents);
}
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

// --- 6. HELPER FUNCTIONS ---
function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function displayEditableFileLink($path, $input_name, $existing_path_name) {
    $current_file = '<span class="form-text text-muted small">No file uploaded.</span>';
    if (!empty($path)) {
        $web_path = "../registration_project/" . $path; // Adjust path as needed
        if (file_exists(__DIR__ . "/" . $web_path)) {
             $fileName = basename($path);
             $current_file = '<a href="' . e($web_path) . '" target="_blank" class="text-primary d-block mb-1 small">View Current: ' . e($fileName) . '</a>';
        } else {
             $current_file = '<span class="form-text text-danger small">File not found.</span>';
        }
    }
    $html = $current_file;
    $html .= '<label for="' . e($input_name) . '" class="form-label visually-hidden">Upload new file</label>';
    $html .= '<input class="form-control form-control-sm" type="file" id="' . e($input_name) . '" name="' . e($input_name) . '">';
    $html .= '<input type="hidden" name="' . e($existing_path_name) . '" value="' . e($path) . '">';
    return $html;
}

$page_specific_title = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>SIMPLEX INTERNAL PORTAL - <?php echo e($page_specific_title); ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700", "Inter:400,500,600,700"] },
            custom: { families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ["assets/css/fonts.min.css"], },
            active: function () { sessionStorage.fonts = true; },
        });
    </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
     <style>
        .form-section { border: 1px solid #eee; background-color: #fdfdfd; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-section-title { font-size: 1.25rem; font-weight: 600; padding-bottom: 0.75rem; border-bottom: 1px solid #dee2e6; margin-bottom: 1.5rem; color: #333; }
        .form-sub-section-title { font-size: 1.1rem; font-weight: 600; color: #177dff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-top: 1px dotted #ccc; padding-top: 1.5rem; }
        .form-label { font-weight: 500; color: #575962; font-size: 0.9rem; }
        .dynamic-entry { border-top: 1px dashed #dee2e6; padding-top: 1.5rem; margin-top: 1.5rem; position: relative; }
        .remove-entry-btn { position: absolute; top: 1.5rem; right: 1rem; }
        .conditional-section { display: none; }
    </style>
</head>
<body>
    <div class="wrapper">
       <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="dashboard.php" class="logo"> 
              <img
                src="assets/img/kaiadmin/simplex_icon_2.png"
                alt="navbar brand"
                class="navbar-brand"
                height="50"
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item active"> 
                <a href="index.php"> 
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
                          <a href="hr_link_2.php" target="_self">
                            <span class="sub-item">User's Profile Update Requests</span>
                          </a>
                        </li>
                       
                      </ul>
                    </div>
                  </li>
                  <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#EmpCornerCollapse"> 
                      <i class="fas fa-users"></i> 
                      <p>Employee Corner</p>
                      <span class="caret"></span>
                    </a>
                    <div class="collapse" id="EmpCornerCollapse">  
                      <ul class="nav nav-collapse">
                         <li>
                          <a href="../employee_corner/LMS/LMS_dasboard.php" target="_self">
                            <span class="sub-item">LMS DASHBOARD</span>
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
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <div class="logo-header" data-background-color="dark">
              <a href="dashboard.php" class="logo"> 
                <img
                  src="assets/img/kaiadmin/logo_light.svg" 
                  alt="navbar brand"
                  class="navbar-brand"
                  height="20"
                />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
          </div>
          <nav
            class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom"
          >
            <div class="container-fluid">
              <div class="navbar-brand-wrapper d-flex align-items-center me-auto">
                <a href="dashboard.php" 
                   style="display: flex; align-items: center; text-decoration: none; color: #333;">
                  <img src="assets/img/kaiadmin/simplex_icon.ico" 
                       alt="Simplex Logo" 
                       style="height: 60px; margin-right: 10px;" /> 
                  <span style="font-size: 1.8rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%;">
                    Simplex Engineering and Foundry Works
                  </span>
                </a>
              </div>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item topbar-user dropdown hidden-caret">
                  <a
                    class="dropdown-toggle profile-pic"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false"
                  >
                    <div class="avatar-sm">
                      <img
                        src="<?php echo htmlspecialchars($avatar_path); ?>" 
                        alt="User Avatar"
                        class="avatar-img rounded-circle"
                        onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';" 
                      />
                    </div>
                    <span class="profile-username">
                      <span class="op-7">Hi,</span>
                      <span class="fw-bold"><?php echo $username_for_display; ?></span> 
                    </span>
                  </a>
                  <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                      <li>
                        <div class="user-box">
                          <div class="avatar-lg">
                            <img
                              src="<?php echo htmlspecialchars($avatar_path); ?>" 
                              alt="image profile"
                              class="avatar-img rounded"
                              onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';"
                            />
                          </div>
                          <div class="u-text">
                            <h4><?php echo $username_for_display; ?></h4> 
                            <p class="text-muted"><?php echo $user_email_placeholder; ?></p> 
                            <p class="text-muted">Emp Code: <?php echo $empcode; ?></p> 
                            <p class="text-muted">Dept: <?php echo $department_display; ?></p> <!-- ADDED -->
                            <p class="text-muted">Role: <?php echo $employee_role_display; ?></p> <!-- ADDED -->
                          </div>
                        </div>
                      </li>
                      <li>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../registration_project/my_profile.php">My Profile</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Account Setting</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../../LOGIN/logout.php">Logout</a> 
                      </li>
                    </div>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
        </div>
          <div class="container">
                <div class="page-inner">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title fw-bold">My Profile</h4>
                        </div>
                        <div class="card-body">
                            
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?php echo e($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
                                    <?php echo e($_SESSION['message']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>
                            
                            <?php if ($has_pending_request): ?>
                                <div class="alert alert-info text-center" role="alert">
                                    <h4 class="alert-heading">Update Request Pending</h4>
                                    <p>You submitted an update request on <strong><?php echo e($pending_request_date); ?></strong> that is currently awaiting approval from HR.</p>
                                    <p class="mb-0">You cannot submit a new request until the current one has been processed.</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light" role="alert">
                                    You can edit the information in the fields below. When you are done, click the "Request for Update" button to submit your changes for HR approval.
                                </div>
                            <?php endif; ?>

                            <form action="actions/submit_update_request.php" method="POST" enctype="multipart/form-data" id="editUserForm">
                                <fieldset <?php if ($has_pending_request) echo 'disabled'; ?>>
                                    <input type="hidden" name="user_id" value="<?php echo e($user_id); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

                                    <section class="form-section">
                                    <h4 class="form-section-title">Personal Identification</h4>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label for="name_as_for_document" class="form-label">Name as per</label><select id="name_as_for_document" name="name_as_for_document" class="form-select"><option value="10th_certificate" <?php if(e($user_data['name_as_for_document'])=='10th_certificate') echo'selected';?>>10th certificate</option><option value="aadhar_card" <?php if(e($user_data['name_as_for_document'])=='aadhar_card') echo'selected';?>>Aadhar card</option><option value="pan_card" <?php if(e($user_data['name_as_for_document'])=='pan_card') echo'selected';?>>PAN Card</option></select></div>
                                        <div class="col-md-4"><label for="salutation" class="form-label">Salutation</label><select id="salutation" name="salutation" class="form-select"><option value="Mr." <?php if(e($user_data['salutation'])=='Mr.') echo'selected';?>>Mr.</option><option value="Miss" <?php if(e($user_data['salutation'])=='Miss') echo'selected';?>>Miss</option><option value="Mrs." <?php if(e($user_data['salutation'])=='Mrs.') echo'selected';?>>Mrs.</option></select></div>
                                        <div class="col-md-4"><label for="first_name" class="form-label">First Name</label><input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo e($user_data['first_name']); ?>"></div>
                                        <div class="col-md-4"><label for="middle_name" class="form-label">Middle Name</label><input type="text" id="middle_name" name="middle_name" class="form-control" value="<?php echo e($user_data['middle_name']); ?>"></div>
                                        <div class="col-md-4"><label for="surname" class="form-label">Surname</label><input type="text" id="surname" name="surname" class="form-control" value="<?php echo e($user_data['surname']); ?>"></div>
                                        <div class="col-md-4"><label for="nationality" class="form-label">Nationality</label><input type="text" id="nationality" name="nationality" class="form-control" value="<?php echo e($user_data['nationality']); ?>"></div>
                                        <div class="col-md-4"><label for="gender" class="form-label">Gender</label><select id="gender" name="gender" class="form-select"><option value="male" <?php if(e($user_data['gender'])=='male') echo'selected';?>>Male</option><option value="female" <?php if(e($user_data['gender'])=='female') echo'selected';?>>Female</option></select></div>
                                        <div class="col-md-4"><label for="religion" class="form-label">Religion</label><input type="text" id="religion" name="religion" class="form-control" value="<?php echo e($user_data['religion']); ?>"></div>
                                        <div class="col-md-4"><label for="category_type" class="form-label">Category</label><input type="text" id="category_type" name="category_type" class="form-control" value="<?php echo e($user_data['category_type']); ?>"></div>
                                        <div class="col-md-4"><label for="date_of_birth" class="form-label">Date of Birth</label><input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?php echo e($user_data['date_of_birth']); ?>"></div>
                                        <div class="col-md-4"><label for="celebrated_date_of_birth" class="form-label">Celebrated DOB</label><input type="date" id="celebrated_date_of_birth" name="celebrated_date_of_birth" class="form-control" value="<?php echo e($user_data['celebrated_date_of_birth']); ?>"></div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <h4 class="form-section-title">Address, Physical & Contact Details</h4>
                                    <h5 class="form-sub-section-title" style="border-top:none; padding-top:0;">Permanent Address</h5>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label for="perm_birth_country" class="form-label">Country</label><input type="text" id="perm_birth_country" name="perm_birth_country" class="form-control" value="<?php echo e($user_data['perm_birth_country']); ?>"></div>
                                        <div class="col-md-4"><label for="perm_birth_state" class="form-label">State</label><input type="text" id="perm_birth_state" name="perm_birth_state" class="form-control" value="<?php echo e($user_data['perm_birth_state']); ?>"></div>
                                        <div class="col-md-4"><label for="perm_birth_city_village" class="form-label">City/Village</label><input type="text" id="perm_birth_city_village" name="perm_birth_city_village" class="form-control" value="<?php echo e($user_data['perm_birth_city_village']); ?>"></div>
                                        <div class="col-12"><label for="perm_address_line1" class="form-label">Address Line 1</label><input type="text" id="perm_address_line1" name="perm_address_line1" class="form-control" value="<?php echo e($user_data['perm_address_line1']); ?>"></div>
                                        <div class="col-12"><label for="perm_address_line2" class="form-label">Address Line 2</label><input type="text" id="perm_address_line2" name="perm_address_line2" class="form-control" value="<?php echo e($user_data['perm_address_line2']); ?>"></div>
                                        <div class="col-12"><label for="perm_address_line3" class="form-label">Address Line 3 / Pincode</label><input type="text" id="perm_address_line3" name="perm_address_line3" class="form-control" value="<?php echo e($user_data['perm_address_line3']); ?>"></div>
                                    </div>
                                    <h5 class="form-sub-section-title">Present Address</h5>
                                    <div class="form-check mb-3"><input type="checkbox" id="sameAsPermanent" class="form-check-input"><label for="sameAsPermanent" class="form-check-label">Same as Permanent</label></div>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label for="present_birth_country" class="form-label">Country</label><input type="text" id="present_birth_country" name="present_birth_country" class="form-control" value="<?php echo e($user_data['present_birth_country']); ?>"></div>
                                        <div class="col-md-4"><label for="present_birth_state" class="form-label">State</label><input type="text" id="present_birth_state" name="present_birth_state" class="form-control" value="<?php echo e($user_data['present_birth_state']); ?>"></div>
                                        <div class="col-md-4"><label for="present_birth_city_village" class="form-label">City/Village</label><input type="text" id="present_birth_city_village" name="present_birth_city_village" class="form-control" value="<?php echo e($user_data['present_birth_city_village']); ?>"></div>
                                        <div class="col-12"><label for="present_address_line1" class="form-label">Address Line 1</label><input type="text" id="present_address_line1" name="present_address_line1" class="form-control" value="<?php echo e($user_data['present_address_line1']); ?>"></div>
                                        <div class="col-12"><label for="present_address_line2" class="form-label">Address Line 2</label><input type="text" id="present_address_line2" name="present_address_line2" class="form-control" value="<?php echo e($user_data['present_address_line2']); ?>"></div>
                                        <div class="col-12"><label for="present_address_line3" class="form-label">Address Line 3 / Pincode</label><input type="text" id="present_address_line3" name="present_address_line3" class="form-control" value="<?php echo e($user_data['present_address_line3']); ?>"></div>
                                    </div>
                                    <h5 class="form-sub-section-title">Physical & Contact</h5>
                                    <div class="row g-3">
                                        <div class="col-md-3"><label for="blood_group" class="form-label">Blood Group</label><input type="text" id="blood_group" name="blood_group" class="form-control" value="<?php echo e($user_data['blood_group']); ?>"></div>
                                        <div class="col-md-3"><label for="weight_kg" class="form-label">Weight (KG)</label><input type="number" step="0.1" id="weight_kg" name="weight_kg" class="form-control" value="<?php echo e($user_data['weight_kg']); ?>"></div>
                                        <div class="col-md-3"><label for="height_cm" class="form-label">Height (CM)</label><input type="number" step="0.1" id="height_cm" name="height_cm" class="form-control" value="<?php echo e($user_data['height_cm']); ?>"></div>
                                        <div class="col-md-3"><label for="your_phone_number" class="form-label">Phone</label><input type="tel" id="your_phone_number" name="your_phone_number" class="form-control" value="<?php echo e($user_data['your_phone_number']); ?>"></div>
                                        <div class="col-md-6"><label for="identification_marks" class="form-label">Identification Marks</label><input type="text" id="identification_marks" name="identification_marks" class="form-control" value="<?php echo e($user_data['identification_marks']); ?>"></div>
                                        <div class="col-md-3"><label for="your_email_id" class="form-label">Email ID</label><input type="email" id="your_email_id" name="your_email_id" class="form-control" value="<?php echo e($user_data['your_email_id']); ?>"></div>
                                        <div class="col-md-3"><label for="emergency_contact_number" class="form-label">Emergency Contact</label><input type="tel" id="emergency_contact_number" name="emergency_contact_number" class="form-control" value="<?php echo e($user_data['emergency_contact_number']); ?>"></div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <h4 class="form-section-title">Document Uploads</h4>
                                    <div class="row g-4">
                                        <div class="col-md-6"><label class="form-label fw-bold">Profile Picture</label><?php echo displayEditableFileLink($user_data['profile_picture_path'], 'profile_picture', 'existing_profile_picture_path'); ?></div>
                                        <div class="col-md-6"><label class="form-label fw-bold">Signature</label><?php echo displayEditableFileLink($user_data['signature_path'], 'signature', 'existing_signature_path'); ?></div>
                                        
                                        <div class="col-md-6"><label class="form-label fw-bold">PAN Card</label><div class="mb-2"><div><input type="radio" id="panAvailableYes" name="pan_available" value="1" class="form-check-input" <?php if(!empty($user_data['pan_available'])) echo 'checked';?>><label for="panAvailableYes"> Yes</label> <input type="radio" id="panAvailableNo" name="pan_available" value="0" class="form-check-input ms-3" <?php if(empty($user_data['pan_available'])) echo 'checked';?>><label for="panAvailableNo"> No</label></div></div><div id="panDetailsDiv" class="conditional-section"><input type="text" name="pan_card_no" class="form-control form-control-sm mb-2" placeholder="PAN Number" value="<?php echo e($user_data['pan_card_no']); ?>"><?php echo displayEditableFileLink($user_data['pan_card_file_path'], 'pan_card_file', 'existing_pan_card_file_path'); ?></div></div>

                                        <div class="col-md-6"><label class="form-label fw-bold">Aadhaar Card</label><div class="mb-2"><div><input type="radio" id="aadharAvailableYes" name="aadhar_available" value="1" class="form-check-input" <?php if(!empty($user_data['aadhar_available'])) echo 'checked';?>><label for="aadharAvailableYes"> Yes</label> <input type="radio" id="aadharAvailableNo" name="aadhar_available" value="0" class="form-check-input ms-3" <?php if(empty($user_data['aadhar_available'])) echo 'checked';?>><label for="aadharAvailableNo"> No</label></div></div><div id="aadharDetailsDiv" class="conditional-section"><input type="text" name="aadhar_number" class="form-control form-control-sm mb-2" placeholder="Aadhaar Number" value="<?php echo e($user_data['aadhar_number']); ?>"><?php echo displayEditableFileLink($user_data['aadhar_card_file_path'], 'aadhar_card_file', 'existing_aadhar_card_file_path'); ?></div></div>

                                        <div class="col-md-6"><label class="form-label fw-bold">Driving Licence</label><div class="mb-2"><div><input type="radio" id="dlAvailableYes" name="dl_available" value="1" class="form-check-input" <?php if(!empty($user_data['dl_available'])) echo 'checked';?>><label for="dlAvailableYes"> Yes</label> <input type="radio" id="dlAvailableNo" name="dl_available" value="0" class="form-check-input ms-3" <?php if(empty($user_data['dl_available'])) echo 'checked';?>><label for="dlAvailableNo"> No</label></div></div><div id="dlDetailsDiv" class="conditional-section"><div class="row g-2"><div class="col-md-6"><input type="text" name="dl_number" class="form-control form-control-sm" placeholder="Licence Number" value="<?php echo e($user_data['dl_number']); ?>"></div><div class="col-md-6"><input type="date" name="dl_expiration_date" class="form-control form-control-sm" value="<?php echo e($user_data['dl_expiration_date']); ?>"></div><div class="col-12 mt-2"><?php echo displayEditableFileLink($user_data['dl_file_path'], 'dl_file', 'existing_dl_file_path'); ?></div></div></div></div>

                                        <div class="col-md-6"><label class="form-label fw-bold">Passport</label><div class="mb-2"><div><input type="radio" id="passportAvailableYes" name="passport_available" value="1" class="form-check-input" <?php if(!empty($user_data['passport_available'])) echo 'checked';?>><label for="passportAvailableYes"> Yes</label> <input type="radio" id="passportAvailableNo" name="passport_available" value="0" class="form-check-input ms-3" <?php if(empty($user_data['passport_available'])) echo 'checked';?>><label for="passportAvailableNo"> No</label></div></div><div id="passportDetailsDiv" class="conditional-section"><div class="row g-2"><div class="col-md-6"><input type="text" name="passport_number" class="form-control form-control-sm" placeholder="Passport Number" value="<?php echo e($user_data['passport_number']); ?>"></div><div class="col-md-6"><input type="date" name="passport_expiration_date" class="form-control form-control-sm" value="<?php echo e($user_data['passport_expiration_date']); ?>"></div><div class="col-12 mt-2"><?php echo displayEditableFileLink($user_data['passport_file_path'], 'passport_file', 'existing_passport_file_path'); ?></div></div></div></div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <h4 class="form-section-title">Family Details</h4>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label for="maritalStatus" class="form-label">Marital Status</label><select id="maritalStatus" name="marital_status" class="form-select"><option value="Single" <?php if(e($user_data['marital_status'])=='Single') echo'selected';?>>Single</option><option value="Married" <?php if(e($user_data['marital_status'])=='Married') echo'selected';?>>Married</option><option value="Widowed" <?php if(e($user_data['marital_status'])=='Widowed') echo'selected';?>>Widowed</option><option value="Divorced" <?php if(e($user_data['marital_status'])=='Divorced') echo'selected';?>>Divorced</option></select></div>
                                    </div>
                                    
                                    <div id="spouseDetailsSection" class="conditional-section">
                                        <h5 class="form-sub-section-title">Spouse's Details</h5>
                                        <div class="row g-3">
                                            <div class="col-md-3"><label class="form-label">Salutation</label><input type="text" name="spouse[salutation]" class="form-control" value="<?php echo e($spouse_data['salutation']??''); ?>"></div>
                                            <div class="col-md-9"><label class="form-label">Name</label><input type="text" name="spouse[name]" class="form-control" value="<?php echo e($spouse_data['name']??''); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="spouse[date_of_birth]" class="form-control" value="<?php echo e($spouse_data['date_of_birth']??''); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Aadhar No.</label><input type="text" name="spouse[aadhar_no]" class="form-control" value="<?php echo e($spouse_data['aadhar_no']??''); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Occupation</label><input type="text" name="spouse[occupation]" class="form-control" value="<?php echo e($spouse_data['occupation']??''); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Mobile</label><input type="tel" name="spouse[mobile_number]" class="form-control" value="<?php echo e($spouse_data['mobile_number']??''); ?>"></div>
                                            <div class="col-md-8"><label class="form-label">Address</label><input type="text" name="spouse[address]" class="form-control" value="<?php echo e($spouse_data['address']??''); ?>"></div>
                                            <div class="col-12"><label class="form-label">Nominee Status</label><div><div class="form-check form-check-inline"><input type="checkbox" name="spouse[is_nominee_pf]" value="1" class="form-check-input" <?php if(!empty($spouse_data['is_nominee_pf'])) echo 'checked';?>><label class="form-check-label">Nominee (PF)</label></div><div class="form-check form-check-inline"><input type="checkbox" name="spouse[is_nominee_esic]" value="1" class="form-check-input" <?php if(!empty($spouse_data['is_nominee_esic'])) echo 'checked';?>><label class="form-check-label">Nominee (ESIC)</label></div><div class="form-check form-check-inline"><input type="checkbox" name="spouse[is_dependent]" value="1" class="form-check-input" <?php if(!empty($spouse_data['is_dependent'])) echo 'checked';?>><label class="form-check-label">Dependent</label></div></div></div>
                                        </div>
                                    </div>

                                    <h5 class="form-sub-section-title">Father's Details</h5>
                                    <div class="row g-3">
                                        <div class="col-md-3"><label class="form-label">Salutation</label><input type="text" name="father[salutation]" class="form-control" value="<?php echo e($father_data['salutation']??''); ?>"></div>
                                        <div class="col-md-9"><label class="form-label">Name</label><input type="text" name="father[name]" class="form-control" value="<?php echo e($father_data['name']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="father[date_of_birth]" class="form-control" value="<?php echo e($father_data['date_of_birth']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Aadhar No.</label><input type="text" name="father[aadhar_no]" class="form-control" value="<?php echo e($father_data['aadhar_no']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Occupation</label><input type="text" name="father[occupation]" class="form-control" value="<?php echo e($father_data['occupation']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Mobile</label><input type="tel" name="father[mobile_number]" class="form-control" value="<?php echo e($father_data['mobile_number']??''); ?>"></div>
                                        <div class="col-md-8"><label class="form-label">Address</label><input type="text" name="father[address]" class="form-control" value="<?php echo e($father_data['address']??''); ?>"></div>
                                        <div class="col-md-12"><label class="form-label">Aadhar Document</label><?php echo displayEditableFileLink($father_data['aadhar_file_path']??'', 'father_aadhar_file', 'existing_father_aadhar_file_path'); ?></div>
                                        <div class="col-12"><label class="form-label">Nominee Status</label><div><div class="form-check form-check-inline"><input type="checkbox" name="father[is_nominee_pf]" value="1" class="form-check-input" <?php if(!empty($father_data['is_nominee_pf'])) echo 'checked';?>><label class="form-check-label">Nominee (PF)</label></div><div class="form-check form-check-inline"><input type="checkbox" name="father[is_nominee_esic]" value="1" class="form-check-input" <?php if(!empty($father_data['is_nominee_esic'])) echo 'checked';?>><label class="form-check-label">Nominee (ESIC)</label></div><div class="form-check form-check-inline"><input type="checkbox" name="father[is_dependent]" value="1" class="form-check-input" <?php if(!empty($father_data['is_dependent'])) echo 'checked';?>><label class="form-check-label">Dependent</label></div></div></div>
                                    </div>

                                    <h5 class="form-sub-section-title">Mother's Details</h5>
                                    <div class="row g-3">
                                        <div class="col-md-3"><label class="form-label">Salutation</label><input type="text" name="mother[salutation]" class="form-control" value="<?php echo e($mother_data['salutation']??''); ?>"></div>
                                        <div class="col-md-9"><label class="form-label">Name</label><input type="text" name="mother[name]" class="form-control" value="<?php echo e($mother_data['name']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="mother[date_of_birth]" class="form-control" value="<?php echo e($mother_data['date_of_birth']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Aadhar No.</label><input type="text" name="mother[aadhar_no]" class="form-control" value="<?php echo e($mother_data['aadhar_no']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Occupation</label><input type="text" name="mother[occupation]" class="form-control" value="<?php echo e($mother_data['occupation']??''); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Mobile</label><input type="tel" name="mother[mobile_number]" class="form-control" value="<?php echo e($mother_data['mobile_number']??''); ?>"></div>
                                        <div class="col-md-8"><label class="form-label">Address</label><input type="text" name="mother[address]" class="form-control" value="<?php echo e($mother_data['address']??''); ?>"></div>
                                        <div class="col-12"><label class="form-label">Nominee Status</label><div><div class="form-check form-check-inline"><input type="checkbox" name="mother[is_nominee_pf]" value="1" class="form-check-input" <?php if(!empty($mother_data['is_nominee_pf'])) echo 'checked';?>><label class="form-check-label">Nominee (PF)</label></div><div class="form-check form-check-inline"><input type="checkbox" name="mother[is_nominee_esic]" value="1" class="form-check-input" <?php if(!empty($mother_data['is_nominee_esic'])) echo 'checked';?>><label class="form-check-label">Nominee (ESIC)</label></div><div class="form-check form-check-inline"><input type="checkbox" name="mother[is_dependent]" value="1" class="form-check-input" <?php if(!empty($mother_data['is_dependent'])) echo 'checked';?>><label class="form-check-label">Dependent</label></div></div></div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <h4 class="form-section-title">Education & Certifications</h4>
                                    <h5 class="form-sub-section-title" style="border-top:none; padding-top:0;">Educational Qualifications</h5>
                                    <div id="educationEntriesContainer">
                                        <?php if (!empty($education_data)): foreach($education_data as $index => $edu): ?>
                                            <div class="dynamic-entry">
                                                <button type="button" class="btn-close remove-entry-btn" aria-label="Close"></button>
                                                <input type="hidden" name="education[<?php echo $index; ?>][id]" value="<?php echo e($edu['education_id']); ?>">
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label class="form-label">Qualification</label><input type="text" name="education[<?php echo $index; ?>][qualification]" class="form-control" value="<?php echo e($edu['qualification']); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Board/University</label><input type="text" name="education[<?php echo $index; ?>][board_university]" class="form-control" value="<?php echo e($edu['board_university']); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">Subject</label><input type="text" name="education[<?php echo $index; ?>][subject]" class="form-control" value="<?php echo e($edu['subject']); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">Enrollment Year</label><input type="number" name="education[<?php echo $index; ?>][enrollment_year]" class="form-control" value="<?php echo e($edu['enrollment_year']); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">Passing Year</label><input type="number" name="education[<?php echo $index; ?>][passing_year]" class="form-control" value="<?php echo e($edu['passing_year']); ?>"></div>
                                                    <div class="col-md-3"><label class="form-label">Percentage/Grade</label><input type="text" name="education[<?php echo $index; ?>][percentage_grade]" class="form-control" value="<?php echo e($edu['percentage_grade']); ?>"></div>
                                                    <div class="col-12"><label class="form-label">Document</label><?php echo displayEditableFileLink($edu['document_path'], "education[{$index}][document]", "education[{$index}][existing_document]"); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <button type="button" id="addEducationBtn" class="btn btn-secondary btn-sm mt-3"><i class="fas fa-plus"></i> Add Qualification</button>
                                    
                                    <h5 class="form-sub-section-title">Certifications</h5>
                                    <div id="certificationEntriesContainer">
                                        <?php if (!empty($certification_data)): foreach($certification_data as $index => $cert): ?>
                                            <div class="dynamic-entry">
                                                <button type="button" class="btn-close remove-entry-btn" aria-label="Close"></button>
                                                <input type="hidden" name="certification[<?php echo $index; ?>][id]" value="<?php echo e($cert['certification_id']); ?>">
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label class="form-label">Certificate Name</label><input type="text" name="certification[<?php echo $index; ?>][certificate_name]" class="form-control" value="<?php echo e($cert['certificate_name']); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Authority</label><input type="text" name="certification[<?php echo $index; ?>][certificate_authority]" class="form-control" value="<?php echo e($cert['certificate_authority']); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Issued On</label><input type="date" name="certification[<?php echo $index; ?>][issued_on]" class="form-control" value="<?php echo e($cert['issued_on']); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Valid Upto</label><input type="date" name="certification[<?php echo $index; ?>][valid_upto]" class="form-control" value="<?php echo e($cert['valid_upto']); ?>"></div>
                                                    <div class="col-12"><label class="form-label">Document</label><?php echo displayEditableFileLink($cert['document_path'], "certification[{$index}][document]", "certification[{$index}][existing_document]"); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <button type="button" id="addCertificationBtn" class="btn btn-secondary btn-sm mt-3"><i class="fas fa-plus"></i> Add Certification</button>
                                </section>

                                <section class="form-section">
                                    <h4 class="form-section-title">Work Experience</h4>
                                    <div class="mb-3"><label class="form-label fw-bold">Has Past Experience?</label><div><input type="radio" id="pastExperienceYes" name="has_past_experience" value="1" class="form-check-input" <?php if(!empty($user_data['has_past_experience'])) echo 'checked';?>><label for="pastExperienceYes"> Yes</label><input type="radio" id="pastExperienceNo" name="has_past_experience" value="0" class="form-check-input ms-3" <?php if(empty($user_data['has_past_experience'])) echo 'checked';?>><label for="pastExperienceNo"> No</label></div></div>
                                    
                                    <div id="workExperienceDetailsDiv" class="conditional-section">
                                        <div class="mb-3"><label class="form-label fw-bold">Has PF Account?</label><div><input type="radio" id="pfAccountYes" name="has_pf_account" value="1" class="form-check-input" <?php if(!empty($user_data['has_pf_account'])) echo 'checked';?>><label for="pfAccountYes"> Yes</label><input type="radio" id="pfAccountNo" name="has_pf_account" value="0" class="form-check-input ms-3" <?php if(empty($user_data['has_pf_account'])) echo 'checked';?>><label for="pfAccountNo"> No</label></div></div>
                                        <div id="pfAccountDetailsDiv" class="row g-3 conditional-section">
                                            <div class="col-md-4"><label class="form-label">UAN No.</label><input type="text" name="pf_uan_no" class="form-control" value="<?php echo e($user_data['pf_uan_no']); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">ESI No.</label><input type="text" name="pf_esi_no" class="form-control" value="<?php echo e($user_data['pf_esi_no']); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Established Code</label><input type="text" name="pf_account_established_code" class="form-control" value="<?php echo e($user_data['pf_account_established_code']); ?>"></div>
                                        </div>
                                        <hr>
                                        <h5 class="form-sub-section-title">Previous Employers</h5>
                                        <div id="workExperienceEntriesContainer">
                                             <?php if (!empty($experience_data)): foreach($experience_data as $index => $exp): ?>
                                                <div class="dynamic-entry">
                                                    <button type="button" class="btn-close remove-entry-btn" aria-label="Close"></button>
                                                    <input type="hidden" name="experience[<?php echo $index; ?>][id]" value="<?php echo e($exp['experience_id']); ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" name="experience[<?php echo $index; ?>][company_name]" class="form-control" value="<?php echo e($exp['company_name']); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Designation</label><input type="text" name="experience[<?php echo $index; ?>][designation]" class="form-control" value="<?php echo e($exp['designation']); ?>"></div>
                                                        <div class="col-md-4"><label class="form-label">From Date</label><input type="date" name="experience[<?php echo $index; ?>][from_date]" class="form-control" value="<?php echo e($exp['from_date']); ?>"></div>
                                                        <div class="col-md-4"><label class="form-label">To Date</label><input type="date" name="experience[<?php echo $index; ?>][to_date]" class="form-control" value="<?php echo e($exp['to_date']); ?>"></div>
                                                        <div class="col-md-4"><label class="form-label">Salary (Per Annum)</label><input type="text" name="experience[<?php echo $index; ?>][salary_per_annum]" class="form-control" value="<?php echo e($exp['salary_per_annum']); ?>"></div>
                                                        <div class="col-12"><label class="form-label">Reason for Leaving</label><input type="text" name="experience[<?php echo $index; ?>][reason_for_leaving]" class="form-control" value="<?php echo e($exp['reason_for_leaving']); ?>"></div>
                                                        <div class="col-12"><label class="form-label">Experience Letter</label><?php echo displayEditableFileLink($exp['experience_letter_path'], "experience[{$index}][document]", "experience[{$index}][existing_document]"); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; endif; ?>
                                        </div>
                                        <button type="button" id="addExperienceBtn" class="btn btn-secondary btn-sm mt-3"><i class="fas fa-plus"></i> Add Experience</button>
                                    </div>
                                </section>

                                <section class="form-section">
                                     <h4 class="form-section-title">Languages, References & Bank Details</h4>
                                     <h5 class="form-sub-section-title" style="border-top:none; padding-top:0;">Language Proficiency</h5>
                                     <div id="languageEntriesContainer">
                                         <?php if (!empty($language_data)): foreach($language_data as $index => $lang): ?>
                                            <div class="dynamic-entry">
                                                <button type="button" class="btn-close remove-entry-btn" aria-label="Close"></button>
                                                <input type="hidden" name="language[<?php echo $index; ?>][id]" value="<?php echo e($lang['language_id']); ?>">
                                                <div class="row g-3 align-items-center">
                                                    <div class="col-md-4"><label class="form-label">Language</label><input type="text" name="language[<?php echo $index; ?>][language_name]" class="form-control" value="<?php echo e($lang['language_name']); ?>"></div>
                                                    <div class="col-md-8"><label class="form-label">Proficiency</label><div><div class="form-check form-check-inline"><input type="checkbox" name="language[<?php echo $index; ?>][can_speak]" value="1" class="form-check-input" <?php if(!empty($lang['can_speak'])) echo 'checked';?>><label class="form-check-label">Speak</label></div><div class="form-check form-check-inline"><input type="checkbox" name="language[<?php echo $index; ?>][can_read]" value="1" class="form-check-input" <?php if(!empty($lang['can_read'])) echo 'checked';?>><label class="form-check-label">Read</label></div><div class="form-check form-check-inline"><input type="checkbox" name="language[<?php echo $index; ?>][can_write]" value="1" class="form-check-input" <?php if(!empty($lang['can_write'])) echo 'checked';?>><label class="form-check-label">Write</label></div><div class="form-check form-check-inline"><input type="checkbox" name="language[<?php echo $index; ?>][can_understand]" value="1" class="form-check-input" <?php if(!empty($lang['can_understand'])) echo 'checked';?>><label class="form-check-label">Understand</label></div></div></div>
                                                </div>
                                            </div>
                                         <?php endforeach; endif; ?>
                                     </div>
                                     <button type="button" id="addLanguageBtn" class="btn btn-secondary btn-sm mt-3"><i class="fas fa-plus"></i> Add Language</button>

                                    <h5 class="form-sub-section-title">References</h5>
                                    <div id="referenceEntriesContainer">
                                        <?php for($i = 0; $i < 3; $i++): $ref = $reference_data[$i] ?? null; ?>
                                            <div class="dynamic-entry">
                                                <h6>Reference <?php echo $i + 1; ?></h6>
                                                <input type="hidden" name="reference[<?php echo $i; ?>][id]" value="<?php echo e($ref['reference_id'] ?? ''); ?>">
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="reference[<?php echo $i; ?>][reference_name]" class="form-control" value="<?php echo e($ref['reference_name'] ?? ''); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Contact No.</label><input type="tel" name="reference[<?php echo $i; ?>][contact_no]" class="form-control" value="<?php echo e($ref['contact_no'] ?? ''); ?>"></div>
                                                    <div class="col-12"><label class="form-label">Address</label><input type="text" name="reference[<?php echo $i; ?>][address]" class="form-control" value="<?php echo e($ref['address'] ?? ''); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Designation/Position</label><input type="text" name="reference[<?php echo $i; ?>][designation_position]" class="form-control" value="<?php echo e($ref['designation_position'] ?? ''); ?>"></div>
                                                    <div class="col-md-6"><label class="form-label">Relation</label><input type="text" name="reference[<?php echo $i; ?>][relation]" class="form-control" value="<?php echo e($ref['relation'] ?? ''); ?>"></div>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <h5 class="form-sub-section-title">Bank Details</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Bank Name</label><input type="text" name="bank[bank_name]" class="form-control" value="<?php echo e($bank_data['bank_name']??''); ?>"></div>
                                        <div class="col-md-6"><label class="form-label">Account Number</label><input type="text" name="bank[account_number]" class="form-control" value="<?php echo e($bank_data['account_number']??''); ?>"></div>
                                        <div class="col-md-6"><label class="form-label">IFSC Code</label><input type="text" name="bank[ifsc_code]" class="form-control" value="<?php echo e($bank_data['ifsc_code']??''); ?>"></div>
                                        <div class="col-md-6"><label class="form-label">MICR Code</label><input type="text" name="bank[micr_code]" class="form-control" value="<?php echo e($bank_data['micr_code']??''); ?>"></div>
                                        <div class="col-12"><label class="form-label">Bank Address</label><input type="text" name="bank[bank_address]" class="form-control" value="<?php echo e($bank_data['bank_address']??''); ?>"></div>
                                        <div class="col-12"><label class="form-label">Passbook/Cheque Document</label><?php echo displayEditableFileLink($bank_data['passbook_document_path']??'', 'bank_passbook_document', 'existing_bank_passbook_document'); ?></div>
                                    </div>
                                </section>

                                <section class="form-section">
                                     <h4 class="form-section-title">Other Details & Declarations</h4>
                                     <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Extra Curricular Activities</label><textarea name="extra_curricular_activities" class="form-control"><?php echo e($user_data['extra_curricular_activities']); ?></textarea></div>
                                        <div class="col-md-6"><label class="form-label">Hobbies</label><textarea name="hobbies" class="form-control"><?php echo e($user_data['hobbies']); ?></textarea></div>

                                        <div class="col-12 mt-3"><label class="form-label fw-bold">Any Medical Disability?</label><div><input type="radio" name="medical_disability_exists" value="1" class="form-check-input" <?php if(!empty($user_data['medical_disability_exists'])) echo 'checked';?>><label> Yes</label><input type="radio" name="medical_disability_exists" value="0" class="form-check-input ms-3" <?php if(empty($user_data['medical_disability_exists'])) echo 'checked';?>><label> No</label></div></div>
                                        <div id="medicalDisabilityDetailsDiv" class="col-12 conditional-section"><label class="form-label">Disability Details</label><textarea name="medical_disability_details" class="form-control"><?php echo e($user_data['medical_disability_details']); ?></textarea></div>

                                        <div class="col-12 mt-3"><label class="form-label fw-bold">Liability with Previous Employer?</label><div><input type="radio" name="prev_employer_liability_exists" value="1" class="form-check-input" <?php if(!empty($user_data['prev_employer_liability_exists'])) echo 'checked';?>><label> Yes</label><input type="radio" name="prev_employer_liability_exists" value="0" class="form-check-input ms-3" <?php if(empty($user_data['prev_employer_liability_exists'])) echo 'checked';?>><label> No</label></div></div>
                                        <div id="prevEmployerLiabilityDetailsDiv" class="col-12 conditional-section"><label class="form-label">Liability Details</label><textarea name="prev_employer_liability_details" class="form-control"><?php echo e($user_data['prev_employer_liability_details']); ?></textarea></div>

                                        <div class="col-md-6 mt-3"><label class="form-label fw-bold">Worked for Simplex Before?</label><div><input type="radio" name="worked_simplex_group" value="1" class="form-check-input" <?php if(!empty($user_data['worked_simplex_group'])) echo 'checked';?>><label> Yes</label><input type="radio" name="worked_simplex_group" value="0" class="form-check-input ms-3" <?php if(empty($user_data['worked_simplex_group'])) echo 'checked';?>><label> No</label></div></div>
                                        <div class="col-md-6 mt-3"><label class="form-label fw-bold">Agree to be Posted Anywhere?</label><div><input type="radio" name="agree_posted_anywhere_india" value="1" class="form-check-input" <?php if(!empty($user_data['agree_posted_anywhere_india'])) echo 'checked';?>><label> Yes</label><input type="radio" name="agree_posted_anywhere_india" value="0" class="form-check-input ms-3" <?php if(empty($user_data['agree_posted_anywhere_india'])) echo 'checked';?>><label> No</label></div></div>

                                        <div class="col-12 mt-4"><div class="form-check"><input type="checkbox" id="declaration_agreed" name="declaration_agreed" value="1" class="form-check-input" <?php if(!empty($user_data['declaration_agreed'])) echo 'checked';?>><label for="declaration_agreed" class="form-check-label">I hereby declare that the information provided is true and correct.</label></div></div>
                                     </div>
                                </section>

                                    <!-- ALL FORM SECTIONS END HERE -->
                                    
                                    <div class="card-footer text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Request for Update</button>
                                        <a href="index.php" class="btn btn-secondary btn-lg">Back to Dashboard</a>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
           <footer class="footer">
          <div class="container-fluid d-flex justify-content-between">
            <nav class="pull-left">
              <ul class="nav">
              </ul>
            </nav>
            <div class="copyright">
              <?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by
              <a href="#">Abhimanyu</a> 
            </div>
            <div>
              For
              <a target="_blank" href="https://www.simplexengg.in/home/"
                >Simplex Engineering & Foundry Works PVT. LTD.</a
              >.
            </div>
          </div>
        </footer>
      </div>
    </div>
      <!-- TEMPLATES FOR DYNAMIC ENTRIES -->
     <template id="educationTemplate">
        <div class="dynamic-entry">
            <button type="button" class="btn-close remove-entry-btn" aria-label="Close"></button>
            <input type="hidden" name="education[new_{{index}}][id]" value="">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Qualification</label><input type="text" name="education[new_{{index}}][qualification]" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Board/University</label><input type="text" name="education[new_{{index}}][board_university]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Subject</label><input type="text" name="education[new_{{index}}][subject]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Enrollment Year</label><input type="number" name="education[new_{{index}}][enrollment_year]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Passing Year</label><input type="number" name="education[new_{{index}}][passing_year]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Percentage/Grade</label><input type="text" name="education[new_{{index}}][percentage_grade]" class="form-control"></div>
                <div class="col-12"><label class="form-label">Document</label><input type="file" name="education[new_{{index}}][document]" class="form-control form-control-sm"></div>
            </div>
        </div>
    </template>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
 <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Function to setup conditional visibility based on radio buttons
        function setupConditionalField(radioName, conditionalDivId) {
            const radios = document.querySelectorAll(`input[name="${radioName}"]`);
            const conditionalDiv = document.getElementById(conditionalDivId);
            if (!radios.length || !conditionalDiv) return;

            const updateVisibility = () => {
                const selectedRadio = document.querySelector(`input[name="${radioName}"]:checked`);
                conditionalDiv.style.display = (selectedRadio && selectedRadio.value === '1') ? 'block' : 'none';
            };
            radios.forEach(radio => radio.addEventListener('change', updateVisibility));
            updateVisibility();
        }

        // Setup all conditional fields
        setupConditionalField('pan_available', 'panDetailsDiv');
        setupConditionalField('aadhar_available', 'aadharDetailsDiv');
        setupConditionalField('dl_available', 'dlDetailsDiv');
        setupConditionalField('passport_available', 'passportDetailsDiv');
        setupConditionalField('has_past_experience', 'workExperienceDetailsDiv');
        setupConditionalField('has_pf_account', 'pfAccountDetailsDiv');
        setupConditionalField('medical_disability_exists', 'medicalDisabilityDetailsDiv');
        setupConditionalField('prev_employer_liability_exists', 'prevEmployerLiabilityDetailsDiv');

        // Conditional Spouse Section
        const maritalStatusSelect = document.getElementById('maritalStatus');
        const spouseSection = document.getElementById('spouseDetailsSection');
        const toggleSpouseSection = () => {
            spouseSection.style.display = (maritalStatusSelect.value === 'Married') ? 'block' : 'none';
        };
        maritalStatusSelect.addEventListener('change', toggleSpouseSection);
        toggleSpouseSection();

        // Same as Permanent Address Logic
        document.getElementById('sameAsPermanent').addEventListener('change', function() {
            const p = 'perm_'; const t = 'present_';
            const fields = ['birth_country', 'birth_state', 'birth_city_village', 'address_line1', 'address_line2', 'address_line3'];
            fields.forEach(f => {
                const permField = document.querySelector(`[name="${p}${f}"]`);
                const presentField = document.querySelector(`[name="${t}${f}"]`);
                if (permField && presentField) {
                    presentField.value = this.checked ? permField.value : '';
                }
            });
        });

        // Function to handle adding and removing dynamic entries
        function setupDynamicEntries(addButtonId, containerId, templateId) {
            const addButton = document.getElementById(addButtonId);
            const container = document.getElementById(containerId);
            const template = document.getElementById(templateId);
            if(!addButton || !container || !template) return;
            
            let counter = container.children.length;

            addButton.addEventListener('click', () => {
                const newEntryHTML = template.innerHTML.replace(/\{\{index\}\}/g, counter);
                const newEntryDiv = document.createElement('div');
                newEntryDiv.innerHTML = newEntryHTML;
                container.appendChild(newEntryDiv.firstElementChild);
                counter++;
            });

            container.addEventListener('click', (e) => {
                if (e.target && e.target.classList.contains('remove-entry-btn')) {
                    e.target.closest('.dynamic-entry').remove();
                }
            });
        }
        
        // Initialize all dynamic sections
        setupDynamicEntries('addEducationBtn', 'educationEntriesContainer', 'educationTemplate');
        // setupDynamicEntries('addCertificationBtn', 'certificationEntriesContainer', 'certificationTemplate');
        // setupDynamicEntries('addExperienceBtn', 'workExperienceEntriesContainer', 'experienceTemplate');
        // setupDynamicEntries('addLanguageBtn', 'languageEntriesContainer', 'languageTemplate');
    });
    </script>
</body>
</html>