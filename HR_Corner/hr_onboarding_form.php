<?php
// /hr_onboarding_form.php
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

// --- Logic to fetch details for the specific user being onboarded ---
require_once 'includes/db_config.php';

$user_id = null;
$user_name = "N/A";
$hr_details = null;
$form_mode = "new";

if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $user_id = (int)$_GET['user_id'];

    // Fetch basic user info to display
    $sql_user = "SELECT user_id, first_name, middle_name, surname FROM users WHERE user_id = ?";
    if ($stmt_user = mysqli_prepare($link, $sql_user)) {
        mysqli_stmt_bind_param($stmt_user, "i", $user_id);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        if ($user_row = mysqli_fetch_assoc($result_user)) {
            $user_name = trim($user_row['first_name'] . ' ' . ($user_row['middle_name'] ? $user_row['middle_name'] . ' ' : '') . $user_row['surname']);
        } else {
            $_SESSION['message'] = "User not found.";
            $_SESSION['message_type'] = "danger";
            header("Location: view_users.php");
            exit();
        }
        mysqli_stmt_close($stmt_user);
    }

    // Fetch existing HR details for this user to pre-fill the form
    $sql_hr = "SELECT * FROM user_hr_details WHERE user_id = ?";
    if ($stmt_hr = mysqli_prepare($link, $sql_hr)) {
        mysqli_stmt_bind_param($stmt_hr, "i", $user_id);
        mysqli_stmt_execute($stmt_hr);
        $result_hr = mysqli_stmt_get_result($stmt_hr);
        if ($hr_row = mysqli_fetch_assoc($result_hr)) {
            $hr_details = $hr_row;
            $form_mode = "edit";
        }
        mysqli_stmt_close($stmt_hr);
    }
} else {
    $_SESSION['message'] = "Invalid User ID provided for HR Onboarding.";
    $_SESSION['message_type'] = "danger";
    header("Location: view_users.php");
    exit();
}

function e_hr($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }

// --- Function to fetch the employee list for DYNAMIC dropdowns ---
function get_employee_list($link) {
    $employee_list = [];
    $query = "SELECT u.first_name, u.middle_name, u.surname, hrd.employee_id_ascent 
              FROM users u
              LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id
              WHERE hrd.employee_id_ascent IS NOT NULL AND hrd.employee_id_ascent != ''
              ORDER BY u.first_name ASC";

    if ($result = mysqli_query($link, $query)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $full_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['surname']);
            $employee_list[$row['employee_id_ascent']] = $full_name . ' (' . $row['employee_id_ascent'] . ')';
        }
    }
    return $employee_list;
}

$employee_dropdown_options = get_employee_list($link);
$unit_options = ["UNIT I Admin" , "UNIT I Shop", "UNIT II", "UNIT III", "Delhi Office", "Chennai Office"];
$department_options = ["Assembly Shop", "Assembly shop(Mc. Str.)","Billing" ,"Blasting", "Default", "Design", "Drilling", "Electrical Maintenance", "Estimation", "Excise", "Execution", "Fabrication", "Fabrication Shop", "Finance and Accounts", "General Administration", "Heat Treatment", "Heat Treatment Shop", "House Keeping", "HRD", "Information Technology", "Loading Unloading", "Machine Shop", "Management", "Marketing", "Mechanical and Electrical Maintenance", "Mechanical Maintenance", "Mess", "Offloading", "Packing", "Painting Shop", "Planning", "Production", "Production CMM", "Production CNC", "Purchase", "Quality", "Quality Control NDT", "Security", "Servicing Department", "Store", "Taxation", "Transport"];
$designation_options = ["Junior Engineer","Engineer","Accountant","Manager","IT Executive","Maintenance","Assistant","Apprentice Trainee(Commercial)","Graduate Apprentice","Machine Shop Officer","Hardware Assistant","Web Developer","Apprentice Trainee Commercial","CNC Operator","Trainee","Graduate Apprenticeship","Service Engineer","Managing Director","Director","Chief Executive Officer","Foreman","Deputy General Manager","Marker","Senior Manager","Packing Labour","Fowler Operator","Senior General Manager","Assistant General Manager","Milling Operator","Office Boy","Watchman","Rigger","EOT Operator","Senior Assistant","Cook","Officer","Tool Grinder","Fabrication Fitter","Vice President","Senior Officer","Chief General Manager","Painter","Radial Drill Operator","Horizontal Boring Operator","Hand Drill Operator","Slotter Operator","Helper","Grinder Man","Deputy Manager","Assistant Foreman","General Manager","Lima Operator","Electrician","Driver","Fitting Fitter Assembly","Articular Driver","Supervisor","CNC Radial Drill Operator","Welder","Assistant Officer","Lathe Operator","Planer Operator","SAW Welder","Vertical Boring Operator","Vertical Lathe Operator","Mechanical Fitter","Senior Engineer","Senior Foreman","Apprentice Trainee (Commercial)","Senior CNC Operator","Gas Cutter","Fitter","Arc Welder","Boring Operator","Heating Operator","Apprentice Trainee (Technical)","Mechanic","Executive","Technical Apprentice","Contractor","Designation-1","President","Hydra Operator","Gardener","NDT Operator","Sweeper","Receptionist","Job In-charge","Apprentice","Apprentice Commercial","Apprentice Technical","Horizontal Lathe Operator","Hacksaw Operator","Blaster","Cleaner","House Keeping","Vertical Drill Operator","Personal Assistant","Company Secretary","Chief Financial Officer","Personal Secretary","Senior Draughtsman","Time Keeper","Draughtsman"];
$category_options = ["Departmental Staff", "Apprentice Worker PF", "Apprentice Staff Stipend", "Apprentice Worker Stipend", "Graduate Apprentice", "Govt. ITI", "Self Contractor", "Labour Contract Services", "Piece Rate Contract", "Department Staff Retired", "Self Service Contract", "Contract Services", "Contract Workers", "Worker Wage Register"];
$grade_options = ["Default", "A", "B", "C", "D", "E", "F", "G", "GA", "Grade-1", "H", "I", "J", "K", "L", "O", "P", "SKA", "SKB", "SKC", "SKD", "SKE", "SSC", "SSA", "SSB", "T", "TA", "US", "HS"];
$status_options = ["Staff", "Worker", "Contractor Worker", "Outsider Staff", "Posted Staff", "Deputed Staff", "Director"];
$leave_group_options = ["Staff", "Worker", "Apprentice Worker PF", "Apprentice Worker ST", "ITI and BGA", "Apprentice Staff PF", "Apprentice Staff ST", "Contractor Workers", "Department Staff Retired", "Contract Services", "Labour Contract Services"];
$shift_schedule_options = [ "GA" => "GA (09:30-18:30)", "PG" => "PG (08:00-17:30)", "GW" => "GW (08:00-17:00)", "FSNG" => "FSNG (06:00-14:00)", "A2" => "A2 (10:00-19:00)", "SP" => "SP (10:00-18:30)", "FS" => "FS (06:00-14:00)", "SS" => "SS (14:00-22:00)", "A3" => "A3 (08:30-17:30)", "P2" => "P2 (09:00-18:00)", "NS" => "NS (22:00-06:00)", "GH" => "GH (09:30-17:00)" ];

// *** CHANGE 1: Replaced the dynamic list with your static list ***
$reporting_incharge_options = [
    "Kandi and MNC", "MTS and CKM", "MNC and SKT", "NULL", "RVS and NMS",
    "UNS and GNS", "AKV and RVS", "HSC and HNS", "RVS and VHS", "MJ and PM",
    "GNS and LVS", "YMD and RVS", "Shankar and GNS", "CSP and GNS", "MNC and RVS",
    "MPJ and MNC", "RD and CKM", "Neeraj and DJ", "HTS and SKT", "NMS",
    "SPU and UNS", "RVS and LVS", "HTS and RVS", "MRP and VHS", "HSC & CPS",
    "HSC and RVS", "MPJ and Kandi", "NMS and Rakesh", "Samal and YMD",
    "SKS and UNS", "Kandi and NMS", "KRS and VHS", "Kamlesh and GNS",
    "RD and RVS", "MPJ and HSC", "NMS and KRS", "DJ and HTS", "MLT and KRS",
    "MRP", "MJ and RVS", "AT & RVS", "PKS and UNS", "RVS and Kandi", "KNS & HSC",
    "HSC", "RKU and KRS", "KNS and Kandi", "Chhavi and AKV", "KRS and PB",
    "GK and RKU", "HSC and SS", "GK and GNS", "Asfar Alam and RVS", "PM and KNS",
    "HSC and RKU", "SS and HSC", "Kandi and Gunjan", "LKV and LVS", "Shankar and LKV",
];
sort($reporting_incharge_options, SORT_STRING | SORT_FLAG_CASE); // Sort the list alphabetically

$attendance_policy_options = [ "Staff", "Worker"];
$page_specific_title = ($form_mode === 'edit' ? 'Edit' : 'Complete') . " HR Onboarding";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>SIMPLEX INTERNAL PORTAL - <?php echo htmlspecialchars($page_specific_title); ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({ google: { families: ["Public Sans:300,400,500,600,700", "Inter:400,500,600,700"] }, custom: { families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ["assets/css/fonts.min.css"], }, active: function () { sessionStorage.fonts = true; }, });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <div class="logo-header" data-background-color="dark">
            <a href="dashboard.php" class="logo"><img src="assets/img/kaiadmin/simplex_icon_2.png" alt="navbar brand" class="navbar-brand" height="50"/></a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
              <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
            </div>
            <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
          </div>
        </div>
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
                          <a href="#" target="_self">
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
              <div class="main-header-logo">
                  <div class="logo-header" data-background-color="dark">
                      <a href="index.php" class="logo"><img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" /></a>
                      <div class="nav-toggle">
                          <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                          <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                      </div>
                      <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                  </div>
              </div>
              <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                  <div class="container-fluid">
                      <div class="navbar-brand-wrapper d-flex align-items-center me-auto">
                          <a href="index.php" style="display: flex; align-items: center; text-decoration: none; color: #333;">
                              <img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 40px; margin-right: 10px;" />
                              <span style="font-size: 1.5rem; font-weight: 500; white-space: nowrap;">Simplex Engineering</span>
                          </a>
                      </div>
                      <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                          <li class="nav-item topbar-user dropdown hidden-caret">
                              <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                  <div class="avatar-sm"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User Avatar" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';" /></div>
                                  <span class="profile-username"><span class="op-7">Hi,</span> <span class="fw-bold"><?php echo $username_for_display; ?></span></span>
                              </a>
                              <ul class="dropdown-menu dropdown-user animated fadeIn">
                                  <div class="dropdown-user-scroll scrollbar-outer">
                                      <li>
                                          <div class="user-box">
                                              <div class="avatar-lg"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="image profile" class="avatar-img rounded" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';" /></div>
                                              <div class="u-text">
                                                  <h4><?php echo $username_for_display; ?></h4> 
                                                  <p class="text-muted"><?php echo $user_email_placeholder; ?></p> 
                                                  <p class="text-muted">Emp Code: <?php echo $empcode; ?></p> 
                                                  <p class="text-muted">Dept: <?php echo $department_display; ?></p>
                                                  <p class="text-muted">Role: <?php echo $employee_role_display; ?></p>
                                              </div>
                                          </div>
                                      </li>
                                      <li>
                                          <div class="dropdown-divider"></div><a class="dropdown-item" href="#">My Profile</a>
                                          <div class="dropdown-divider"></div><a class="dropdown-item" href="../../LOGIN/logout.php">Logout</a> 
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
                    <div class="card shadow-lg mx-auto" style="max-width: 900px;">
                        <div class="card-header">
                            <div class="card-title text-center">
                                <h2 class="fw-bold"><?php echo ($form_mode ?? 'new') === 'edit' ? 'Update' : 'Complete'; ?> HR Onboarding</h2>
                                <p class="card-category">For User: <strong><?php echo e_hr($user_name ?? ''); ?></strong> (ID: <?php echo e_hr($user_id ?? ''); ?>)</p>
                            </div>
                        </div>
                        <div class="card-body">
                             <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>
                            <?php if (isset($_SESSION['form_errors_hr'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <strong>Please correct the following errors:</strong>
                                    <ul>
                                        <?php foreach ($_SESSION['form_errors_hr'] as $error): ?>
                                            <li>- <?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php unset($_SESSION['form_errors_hr']); endif; ?>

                            <form action="actions/save_hr_details.php" method="POST">
                                <input type="hidden" name="user_id" value="<?php echo e_hr($user_id); ?>">
                                <input type="hidden" name="form_mode" value="<?php echo $form_mode; ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                                        <select id="unit" name="unit" class="form-select" required>
                                            <option value="">-- Select Unit --</option>
                                            <?php foreach($unit_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['unit']) && strtolower(trim($hr_details['unit'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                        <select id="department" name="department" required placeholder="Search for a department...">
                                            <option value="">-- Select Department --</option>
                                             <?php foreach($department_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['department']) && strtolower(trim($hr_details['department'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                     <div class="col-md-6">
                                        <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                                        <select id="designation" name="designation" required placeholder="Search for a designation...">
                                            <option value="">-- Select Designation --</option>
                                            <?php foreach($designation_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['designation']) && strtolower(trim($hr_details['designation'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_of_joining" class="form-label">Date of Joining <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo e_hr($hr_details['date_of_joining'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select id="category" name="category" class="form-select" required>
                                            <option value="">-- Select Category --</option>
                                            <?php foreach($category_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['category']) && strtolower(trim($hr_details['category'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="grade" class="form-label">Grade <span class="text-danger">*</span></label>
                                        <select id="grade" name="grade" class="form-select" required>
                                            <option value="">-- Select Grade --</option>
                                            <?php foreach($grade_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['grade']) && strtolower(trim($hr_details['grade'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select id="status" name="status" class="form-select" required>
                                             <option value="">-- Select Status --</option>
                                             <?php foreach($status_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['status']) && strtolower(trim($hr_details['status'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                     <div class="col-md-6">
                                        <label for="leave_group" class="form-label">Leave Group <span class="text-danger">*</span></label>
                                        <select id="leave_group" name="leave_group" class="form-select" required>
                                            <option value="">-- Select Leave Group --</option>
                                            <?php foreach($leave_group_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['leave_group']) && strtolower(trim($hr_details['leave_group'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="shift_schedule" class="form-label">Shift Schedule <span class="text-danger">*</span></label>
                                        <select id="shift_schedule" name="shift_schedule" class="form-select" required>
                                            <option value="">-- Select Shift Schedule --</option>
                                            <?php foreach($shift_schedule_options as $value => $text): ?>
                                            <option value="<?php echo e_hr($value); ?>" <?php echo (isset($hr_details['shift_schedule']) && strtolower(trim($hr_details['shift_schedule'])) === strtolower(trim($value))) ? 'selected' : ''; ?>><?php echo e_hr($text); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="reporting_incharge" class="form-label">Reporting Incharge <span class="text-danger">*</span></label>
                                        <select id="reporting_incharge" name="reporting_incharge" required placeholder="Search for an incharge...">
                                            <option value="">-- Select Incharge --</option>
                                            <?php // *** CHANGE 2: Corrected the loop for the new simple array *** ?>
                                            <?php foreach($reporting_incharge_options as $option): ?>
                                            <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['reporting_incharge']) && trim($hr_details['reporting_incharge']) === trim($option)) ? 'selected' : ''; ?>>
                                                <?php echo e_hr($option); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                     <div class="col-md-6">
                                        <label for="department_head" class="form-label">Department Head <span class="text-danger">*</span></label>
                                        <select id="department_head" name="department_head" required placeholder="Search for a department head...">
                                            <option value="">-- Select Head --</option>
                                            <?php foreach($employee_dropdown_options as $emp_id => $emp_name_display): ?>
                                            <option value="<?php echo e_hr($emp_id); ?>" <?php echo (isset($hr_details['department_head']) && trim($hr_details['department_head']) === trim($emp_id)) ? 'selected' : ''; ?>><?php echo e_hr($emp_name_display); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                     <div class="col-md-6">
                                        <label for="attendance_policy" class="form-label">Attendance Policy <span class="text-danger">*</span></label>
                                        <select id="attendance_policy" name="attendance_policy" class="form-select" required>
                                            <option value="">-- Select Policy --</option>
                                            <?php foreach($attendance_policy_options as $option): ?>
                                                <option value="<?php echo e_hr($option); ?>" <?php echo (isset($hr_details['attendance_policy']) && strtolower(trim($hr_details['attendance_policy'])) === strtolower(trim($option))) ? 'selected' : ''; ?>><?php echo e_hr($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="employee_id_ascent" class="form-label">Employee ID (from Ascent) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="employee_id_ascent" name="employee_id_ascent" value="<?php echo e_hr($hr_details['employee_id_ascent'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="payroll_code" class="form-label">Payroll Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="payroll_code" name="payroll_code" value="<?php echo e_hr($hr_details['payroll_code'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="vaccination_code" class="form-label">Vaccination Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="vaccination_code" name="vaccination_code" value="<?php echo e_hr($hr_details['vaccination_code'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="view_users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back To Employee Master</a>
                                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> <?php echo $form_mode === 'edit' ? 'Update HR Details' : 'Save HR Details'; ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
              <div class="container-fluid d-flex justify-content-between">
                  <div class="copyright"><?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Abhimanyu</a></div>
              </div>
          </footer>
        </div>
    </div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script> 

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tomSelectSettings = {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            };

            // Initialize TomSelect for searchable dropdowns
            new TomSelect('#department', tomSelectSettings);
            new TomSelect('#designation', tomSelectSettings);
            new TomSelect('#reporting_incharge', tomSelectSettings);
            new TomSelect('#department_head', tomSelectSettings);
        });
    </script>
</body>
</html>