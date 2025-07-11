<?php
// FOR DEBUGGING - REMOVE IN PRODUCTION IF NEEDED
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// /view_users.php

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header("Location: login.php"); exit; }

// --- User info and avatar logic ---
$original_username = $_SESSION['username'] ?? 'User';
$username_for_display = htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $original_username))));
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A';
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in';
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';
$avatar_path = "assets/img/kaiadmin/default-avatar.png";

$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "user_master_db";
if ($empcode !== 'N/A') {
    @$conn_avatar = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn_avatar && !$conn_avatar->connect_error) {
        $sql_avatar = "SELECT users.profile_picture_path FROM users JOIN user_hr_details ON users.user_id = user_hr_details.user_id WHERE user_hr_details.employee_id_ascent = ?";
        if ($stmt_avatar = $conn_avatar->prepare($sql_avatar)) {
            $stmt_avatar->bind_param("s", $empcode);
            $stmt_avatar->execute();
            $result_avatar = $stmt_avatar->get_result();
            if ($result_avatar && $result_avatar->num_rows > 0) {
                $row_avatar = $result_avatar->fetch_assoc();
                $db_avatar_path = $row_avatar['profile_picture_path'];
                if (!empty($db_avatar_path) && file_exists("../registration_project/" . $db_avatar_path)) {
                    $avatar_path = "../registration_project/" . $db_avatar_path;
                }
            }
            if($stmt_avatar) $stmt_avatar->close();
        }
        $conn_avatar->close();
    }
}

$page_specific_title = "Employee Master";
require_once 'includes/db_config.php';

if (!$link) { die("CRITICAL ERROR: Database connection failed. Please check includes/db_config.php"); }

// --- Calculate Quick View Counts ---
$quick_view_counts = [
    'working' => 0, 'resigned' => 0, 'new_users' => 0,
    'hr_needed' => 0, 'it_needed' => 0
];
$four_months_ago_for_count = date('Y-m-d', strtotime('-4 months'));
$counts_sql = "SELECT
    COUNT(CASE WHEN hrd.employee_portal_status = '1' THEN 1 END) AS working_count,
    COUNT(CASE WHEN hrd.employee_portal_status = '0' THEN 1 END) AS resigned_count,
    COUNT(CASE WHEN hrd.date_of_joining >= ? THEN 1 END) AS new_users_count,
    COUNT(CASE WHEN hrd.employee_portal_status = '1' AND (TRIM(IFNULL(hrd.unit, '')) = '' OR TRIM(IFNULL(hrd.department, '')) = '' OR TRIM(IFNULL(hrd.designation, '')) = '' OR hrd.date_of_joining IS NULL OR TRIM(IFNULL(hrd.category, '')) = '' OR TRIM(IFNULL(hrd.grade, '')) = '' OR TRIM(IFNULL(hrd.status, '')) = '' OR TRIM(IFNULL(hrd.leave_group, '')) = '' OR TRIM(IFNULL(hrd.shift_schedule, '')) = '' OR TRIM(IFNULL(hrd.reporting_incharge, '')) = '' OR TRIM(IFNULL(hrd.department_head, '')) = '' OR TRIM(IFNULL(hrd.attendance_policy, '')) = '' OR TRIM(IFNULL(hrd.employee_id_ascent, '')) = '' OR TRIM(IFNULL(hrd.employee_role, '')) = '' OR TRIM(IFNULL(hrd.payroll_code, '')) = '' OR TRIM(IFNULL(hrd.vaccination_code, '')) = '') THEN 1 END) AS hr_needed_count,
    COUNT(CASE WHEN hrd.employee_portal_status = '1' AND (itd.user_id IS NULL OR TRIM(IFNULL(itd.official_phone_number, '')) = '' OR TRIM(IFNULL(itd.official_email, '')) = '' OR TRIM(IFNULL(itd.intercom_number, '')) = '') THEN 1 END) as it_needed_count
    FROM users u
    LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id
    LEFT JOIN user_it_details itd ON u.user_id = itd.user_id";

if ($stmt_counts = $link->prepare($counts_sql)) {
    $stmt_counts->bind_param("s", $four_months_ago_for_count);
    $stmt_counts->execute();
    $result_counts = $stmt_counts->get_result();
    if ($result_counts) {
        $counts = $result_counts->fetch_assoc();
        $quick_view_counts['working'] = $counts['working_count'] ?? 0;
        $quick_view_counts['resigned'] = $counts['resigned_count'] ?? 0;
        $quick_view_counts['new_users'] = $counts['new_users_count'] ?? 0;
        $quick_view_counts['hr_needed'] = $counts['hr_needed_count'] ?? 0;
        $quick_view_counts['it_needed'] = $counts['it_needed_count'] ?? 0;
    }
    $stmt_counts->close();
}


// --- Main filtering and pagination logic ---
$possible_limits = [10, 25, 50, 100, 200];
$records_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $possible_limits) ? (int)$_GET['limit'] : 25;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$view_mode = $_GET['view'] ?? 'working';

$sql_select_from = "SELECT 
    u.user_id, u.salutation, u.first_name, u.middle_name, u.surname, u.profile_picture_path, 
    u.gender, u.marital_status, u.category_type, u.blood_group, u.date_of_birth, u.your_email_id, u.your_phone_number,
    u.perm_birth_state, u.perm_birth_city_village, u.pan_available, u.aadhar_available, u.dl_available,
    u.present_birth_state, u.present_birth_city_village,
    hrd.employee_id_ascent, hrd.unit, hrd.department, hrd.designation, hrd.category, hrd.grade, hrd.date_of_joining, 
    hrd.employee_role, hrd.employee_portal_status, hrd.status, hrd.reporting_incharge, hrd.leave_group,
    hrd.shift_schedule, hrd.department_head, hrd.attendance_policy,
    (CASE WHEN hrd.employee_portal_status != '1' THEN 0 WHEN hrd.user_id IS NULL THEN 1 WHEN TRIM(IFNULL(hrd.unit, '')) = '' OR TRIM(IFNULL(hrd.department, '')) = '' OR TRIM(IFNULL(hrd.designation, '')) = '' OR hrd.date_of_joining IS NULL OR TRIM(IFNULL(hrd.category, '')) = '' OR TRIM(IFNULL(hrd.grade, '')) = '' OR TRIM(IFNULL(hrd.status, '')) = '' OR TRIM(IFNULL(hrd.leave_group, '')) = '' OR TRIM(IFNULL(hrd.shift_schedule, '')) = '' OR TRIM(IFNULL(hrd.reporting_incharge, '')) = '' OR TRIM(IFNULL(hrd.department_head, '')) = '' OR TRIM(IFNULL(hrd.attendance_policy, '')) = '' OR TRIM(IFNULL(hrd.employee_id_ascent, '')) = '' OR TRIM(IFNULL(hrd.employee_role, '')) = '' OR TRIM(IFNULL(hrd.payroll_code, '')) = '' OR TRIM(IFNULL(hrd.vaccination_code, '')) = '' THEN 1 ELSE 0 END) AS hr_action_needed,
    (CASE WHEN hrd.employee_portal_status != '1' THEN 0 WHEN itd.user_id IS NULL THEN 1 WHEN TRIM(IFNULL(itd.official_phone_number, '')) = '' OR TRIM(IFNULL(itd.official_email, '')) = '' OR TRIM(IFNULL(itd.intercom_number, '')) = '' THEN 1 ELSE 0 END) AS it_action_needed,
    pd_father.name AS father_name 
    FROM users u 
    LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id 
    LEFT JOIN user_it_details itd ON u.user_id = itd.user_id 
    LEFT JOIN parent_details pd_father ON u.user_id = pd_father.user_id AND pd_father.parent_type = 'Father'";

$sql_where_parts = [];
$sql_params = [];
$sql_types = "";
$having_parts = [];
$order_by_clause = "ORDER BY u.user_id DESC";

switch ($view_mode) {
    case 'new_users':
        $four_months_ago = date('Y-m-d', strtotime('-4 months'));
        $sql_where_parts[] = "hrd.date_of_joining >= ?";
        $sql_params[] = $four_months_ago;
        $sql_types .= "s";
        $order_by_clause = "ORDER BY hrd.date_of_joining DESC, u.user_id DESC";
        break;
    case 'working':
        $sql_where_parts[] = "hrd.employee_portal_status = ?";
        $sql_params[] = '1';
        $sql_types .= "s";
        break;
    case 'resigned':
        $sql_where_parts[] = "hrd.employee_portal_status = ?";
        $sql_params[] = '0';
        $sql_types .= "s";
        break;
    case 'hr_needed':
        $having_parts[] = "hr_action_needed = 1";
        break;
    case 'it_needed':
        $having_parts[] = "it_action_needed = 1";
        break;
}

if (!empty($search_query)) {
    $search_term_like = "%" . $search_query . "%";
    $sql_where_parts[] = "(CONCAT_WS(' ', u.salutation, u.first_name, u.middle_name, u.surname) LIKE ? OR u.user_id LIKE ? OR hrd.employee_id_ascent LIKE ?)";
    for ($i = 0; $i < 3; $i++) { $sql_params[] = $search_term_like; $sql_types .= "s"; }
}

$filter_column_map = [
    'unit' => 'hrd.unit', 'department' => 'hrd.department', 'designation' => 'hrd.designation',
    'category' => 'hrd.category', 'grade' => 'hrd.grade', 'status' => 'hrd.status', 'leave_group' => 'hrd.leave_group',
    'shift_schedule' => 'hrd.shift_schedule', 'reporting_incharge' => 'hrd.reporting_incharge', 'department_head' => 'hrd.department_head',
    'attendance_policy' => 'hrd.attendance_policy', 'employee_role' => 'hrd.employee_role', 'employee_portal_status' => 'hrd.employee_portal_status',
    'gender' => 'u.gender', 'perm_birth_state' => 'u.perm_birth_state', 'perm_birth_city_village' => 'u.perm_birth_city_village',
    'blood_group' => 'u.blood_group', 'pan_available' => 'u.pan_available', 'aadhar_available' => 'u.aadhar_available', 'dl_available' => 'u.dl_available',
    'present_birth_state' => 'u.present_birth_state', 'present_birth_city_village' => 'u.present_birth_city_village'
];

foreach ($filter_column_map as $key => $column) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $sql_where_parts[] = "{$column} = ?";
        $sql_params[] = $_GET[$key];
        $sql_types .= "s";
    }
}

$date_filters = ['date_of_joining', 'date_of_birth'];
foreach ($date_filters as $key) {
    $from = $_GET[$key . '_from'] ?? '';
    $to = $_GET[$key . '_to'] ?? '';
    $db_col = ($key == 'date_of_joining') ? 'hrd.date_of_joining' : 'u.date_of_birth';

    if (!empty($from) && !empty($to)) {
        $sql_where_parts[] = "{$db_col} BETWEEN ? AND ?";
        $sql_params[] = $from;
        $sql_params[] = $to;
        $sql_types .= "ss";
    } elseif (!empty($from)) {
        $sql_where_parts[] = "{$db_col} >= ?";
        $sql_params[] = $from;
        $sql_types .= "s";
    } elseif (!empty($to)) {
        $sql_where_parts[] = "{$db_col} <= ?";
        $sql_params[] = $to;
        $sql_types .= "s";
    }
}

$sql_where = !empty($sql_where_parts) ? " WHERE " . implode(" AND ", $sql_where_parts) : "";
$sql_having = !empty($having_parts) ? " HAVING " . implode(" AND ", $having_parts) : "";

$total_records_sql = "SELECT COUNT(*) as total FROM (SELECT u.user_id, "
    . "(CASE WHEN hrd.employee_portal_status != '1' THEN 0 WHEN hrd.user_id IS NULL THEN 1 WHEN TRIM(IFNULL(hrd.unit, '')) = '' OR TRIM(IFNULL(hrd.department, '')) = '' OR TRIM(IFNULL(hrd.designation, '')) = '' OR hrd.date_of_joining IS NULL OR TRIM(IFNULL(hrd.category, '')) = '' OR TRIM(IFNULL(hrd.grade, '')) = '' OR TRIM(IFNULL(hrd.status, '')) = '' OR TRIM(IFNULL(hrd.leave_group, '')) = '' OR TRIM(IFNULL(hrd.shift_schedule, '')) = '' OR TRIM(IFNULL(hrd.reporting_incharge, '')) = '' OR TRIM(IFNULL(hrd.department_head, '')) = '' OR TRIM(IFNULL(hrd.attendance_policy, '')) = '' OR TRIM(IFNULL(hrd.employee_id_ascent, '')) = '' OR TRIM(IFNULL(hrd.employee_role, '')) = '' OR TRIM(IFNULL(hrd.payroll_code, '')) = '' OR TRIM(IFNULL(hrd.vaccination_code, '')) = '' THEN 1 ELSE 0 END) AS hr_action_needed, "
    . "(CASE WHEN hrd.employee_portal_status != '1' THEN 0 WHEN itd.user_id IS NULL THEN 1 WHEN TRIM(IFNULL(itd.official_phone_number, '')) = '' OR TRIM(IFNULL(itd.official_email, '')) = '' OR TRIM(IFNULL(itd.intercom_number, '')) = '' THEN 1 ELSE 0 END) AS it_action_needed "
    . "FROM users u LEFT JOIN user_hr_details hrd ON u.user_id=hrd.user_id LEFT JOIN user_it_details itd ON u.user_id=itd.user_id "
    . $sql_where . $sql_having . ") AS subquery";

$total_records = 0;
if ($stmt_total = $link->prepare($total_records_sql)) {
    if (!empty($sql_types)) { $stmt_total->bind_param($sql_types, ...$sql_params); }
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    if($result_total) { $total_records = $result_total->fetch_assoc()['total']; }
    $stmt_total->close();
}

$total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 1;
if ($current_page > $total_pages) { $current_page = $total_pages > 0 ? $total_pages : 1; }
$offset = ($current_page - 1) * $records_per_page;

$users = [];
$sql_final = $sql_select_from . $sql_where . $sql_having . " " . $order_by_clause . " LIMIT ? OFFSET ?";

$final_params = $sql_params;
$final_params[] = $records_per_page;
$final_params[] = $offset;
$final_types = $sql_types . "ii";

if ($stmt = $link->prepare($sql_final)) {
    if (!empty($final_types)) { $stmt->bind_param($final_types, ...$final_params); }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        if(empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
           die("Error fetching results: mysqli_stmt::get_result() failed. Please ensure the 'mysqlnd' PHP extension is installed and enabled.");
        }
    }
    $stmt->close();
} else {
    if(empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        die("Error preparing SQL statement: " . $link->error);
    }
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $pagination_info = [
        'showing_text' => "Showing " . ($total_records > 0 ? min($offset + 1, $total_records) : 0) . " to " . min($offset + $records_per_page, $total_records) . " of " . $total_records . " entries",
        'current_page' => $current_page,
        'total_pages' => $total_pages
    ];
    header('Content-Type: application/json');
    echo json_encode(['users' => $users, 'pagination' => $pagination_info]);
    exit;
}
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
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700", "Inter:300,400,500,600,700"] },
        custom: {
          families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () { sessionStorage.fonts = true; },
      });
    </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <style>
        .profile-thumbnail-container { width: 45px; height: 45px; overflow: hidden; position: relative; border-radius: 0.375rem; cursor: pointer; margin: 0; }
        .profile-thumbnail-container:hover .profile-thumbnail { position: absolute; width: 120px; height: 150px; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 10px 30px rgba(0,0,0,0.35); z-index: 1051; border: 3px solid white; border-radius: 0.25rem; }
        .profile-thumbnail { width: 100%; height: 100%; object-fit: cover; border-radius: 0.375rem; }
        .no-pic-placeholder { width: 45px; height: 45px; background-color: #f1f1f1; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #686868; border-radius: 0.375rem; }
        .table-action-btn { margin-right: 5px; margin-bottom: 5px; }
        .table-container-fixed-height { max-height: 60vh; overflow-y: auto; }
        #add-filter-menu, #columnToggler { max-height: 300px; overflow-y: auto; cursor: pointer; }
        #columnToggler .dropdown-item { user-select: none; }
        .dynamic-filter-item { display: flex; align-items: center; gap: 10px; }
        #quick-view-container .btn { font-size: 0.8rem; padding: 0.4rem 0.6rem; }
        @keyframes blinker { 50% { opacity: 0.2; } }
        .blinking-badge {
            animation: blinker 1.5s linear infinite;
            background-color: #dc3545; /* Bootstrap's danger red */
            color: white;
            font-weight: bold;
        }
        .ts-control {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem !important;
            min-height: calc(1.5em + 0.5rem + 2px);
        }
        .ts-dropdown { font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="wrapper">
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
                          <a href="../registration_project/hr_update_requests.php" target="_self">
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
                                          <div class="dropdown-divider"></div><a class="dropdown-item" href="my_profile.php">My Profile</a>
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
                  <div class="d-flex justify-content-between align-items-center pt-2 pb-4 flex-wrap">
                      <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($page_specific_title); ?></h3> 
                  </div>
                  
                  <div class="card">
                      <div class="card-body">
                          <form id="searchForm" onsubmit="return false;">
                              <div class="d-flex flex-wrap justify-content-between">
                                  <div class="d-flex align-items-center mb-3 mb-md-0">
                                      <label for="limit" class="me-2 form-label">Show:</label>
                                      <select name="limit" id="limit" class="form-select form-select-sm" style="width: auto;">
                                          <?php foreach($possible_limits as $lim): ?>
                                          <option value="<?php echo $lim; ?>" <?php if ($records_per_page == $lim) echo 'selected'; ?>><?php echo $lim; ?></option>
                                          <?php endforeach; ?>
                                      </select>
                                      <span class="ms-2">entries</span>
                                  </div>
                                  <div class="input-group" style="max-width: 320px;">
                                      <input type="search" name="search" id="searchInput" placeholder="Live search..." class="form-control form-control-sm">
                                  </div>
                              </div>
                          </form>
                      </div>
                  </div>

                  <div class="card">
                      <div class="card-body">
                          <div id="quick-view-container" class="btn-group w-100 mb-3" role="group" aria-label="Quick View Toggles">
                            <button type="button" class="btn btn-outline-primary" data-view="new_users">New Users (4 Mo) <span class="badge bg-secondary ms-1"></span></button>
                            <button type="button" class="btn btn-outline-primary active" data-view="working">Currently Working <span class="badge bg-secondary ms-1"></span></button>
                            <button type="button" class="btn btn-outline-primary" data-view="resigned">Resigned <span class="badge bg-secondary ms-1"></span></button>
                            <button type="button" class="btn btn-outline-primary" data-view="hr_needed">HR Action Needed <span class="badge ms-1"></span></button>
                            <button type="button" class="btn btn-outline-primary" data-view="it_needed">IT Action Needed <span class="badge ms-1"></span></button>
                          </div>
                          <hr>
                          <div class="d-flex flex-wrap justify-content-between align-items-center">
                              <h5 class="card-title mb-2 mb-md-0">Advanced Filters</h5>
                              <div class="btn-group">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-plus"></i> Add Filter</button>
                                    <ul class="dropdown-menu" id="add-filter-menu"></ul>
                                </div>
                                <button class="btn btn-outline-secondary btn-sm ms-2" id="resetFiltersBtn">Reset All</button>
                                <div class="btn-group ms-2">
                                    <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show/Hide Table Columns"><i class="fa fa-columns"></i> Columns</button>
                                    <div class="dropdown-menu dropdown-menu-end" id="columnToggler"></div>
                                </div>
                              </div>
                          </div>
                          <div id="dynamic-filter-container" class="row gx-3 gy-3 mt-2"></div>
                      </div>
                  </div>

                  <div class="card mt-4">
                      <div class="card-body">
                          <div class="table-responsive table-container-fixed-height">
                              <table class="table table-striped table-hover" id="main-user-table">
                                  <thead id="main-table-head"></thead>
                                  <tbody id="main-table-body"></tbody>
                              </table>
                          </div>
                      </div>
                  </div>
                  
                  <div class="d-flex justify-content-between mt-4 align-items-center flex-wrap">
                      <div class="text-muted" id="pagination-info-text"></div>
                      <div id="pagination-container"></div>
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
        const quickViewCounts = <?php echo json_encode($quick_view_counts); ?>;
    </script>

   <script>
        document.addEventListener("DOMContentLoaded", function() {
            // =========================================================================
            // 1. CONFIGURATION & STATE
            // =========================================================================
            const basePath = './';
            let currentUsersData = []; 
            let activeAdvancedFilters = {};
            let currentViewMode = 'working';

            const columnConfig = {
                s_no: { header: 'S.No', type: 'serial' },
                user_id: { header: 'User ID', dataKey: 'user_id' },
                employee_id_ascent: { header: 'Emp. ID', dataKey: 'employee_id_ascent' },
                profile_picture_path: { header: 'Profile Pic', type: 'image', dataKey: 'profile_picture_path' },
                full_name: { header: 'Full Name', type: 'special' },
                unit: { header: 'Unit', dataKey: 'unit' },
                department: { header: 'Department', dataKey: 'department' },
                designation: { header: 'Designation', dataKey: 'designation' },
                date_of_joining: { header: 'DOJ', type: 'date', dataKey: 'date_of_joining' },
                category: { header: 'Category', dataKey: 'category' },
                grade: { header: 'Grade', dataKey: 'grade' },
                status: { header: 'Status', dataKey: 'status' },
                leave_group: { header: 'Leave Group', dataKey: 'leave_group' },
                shift_schedule: { header: 'Shift Schedule', dataKey: 'shift_schedule' },
                reporting_incharge: { header: 'Reporting Incharge', dataKey: 'reporting_incharge' },
                department_head: { header: 'Dept Head', dataKey: 'department_head' },
                attendance_policy: { header: 'Attendance Policy', dataKey: 'attendance_policy' },
                employee_role: { header: 'Role', dataKey: 'employee_role' },
                employee_portal_status: { header: 'Portal Status', type: 'badge', dataKey: 'employee_portal_status' },
                gender: { header: 'Gender', dataKey: 'gender' },
                date_of_birth: { header: 'Birth Date', type: 'date', dataKey: 'date_of_birth' },
                perm_birth_state: { header: 'Perm. Birth State', dataKey: 'perm_birth_state' },
                present_birth_state: { header: 'Pres. Birth State', dataKey: 'present_birth_state' },
                perm_birth_city_village: { header: 'Perm. Birth City', dataKey: 'perm_birth_city_village' },
                present_birth_city_village: { header: 'Pres. Birth City', dataKey: 'present_birth_city_village' },
                blood_group: { header: 'Blood Group', dataKey: 'blood_group' },
                pan_available: { header: 'PAN', type: 'bool', dataKey: 'pan_available' },
                aadhar_available: { header: 'Aadhar', type: 'bool', dataKey: 'aadhar_available' },
                dl_available: { header: 'DL', type: 'bool', dataKey: 'dl_available' },
                hr_status: { header: 'HR Status', type: 'status_badge', dataKey: 'hr_action_needed' },
                it_status: { header: 'IT Status', type: 'status_badge', dataKey: 'it_action_needed' },
                actions: { header: 'Actions', type: 'actions' }
            };

            const defaultColumns = [ 's_no', 'user_id', 'employee_id_ascent', 'profile_picture_path', 'full_name', 'unit', 'department', 'designation', 'date_of_joining', 'actions' ];
            
            const masterFilterList = {};
            Object.keys(columnConfig).forEach(key => {
                const config = columnConfig[key];
                if(config.dataKey && !['special', 'status_badge', 'actions', 'image', 'serial'].includes(config.type)) {
                    masterFilterList[key] = {
                        label: config.header,
                        filterType: config.type === 'date' ? 'date_range' : 'select'
                    };
                }
            });
            
            let visibleColumns = JSON.parse(localStorage.getItem('visibleColumns')) || defaultColumns;
            
            const searchInput = document.getElementById('searchInput');
            const resetFiltersBtn = document.getElementById('resetFiltersBtn');
            const addFilterMenu = document.getElementById('add-filter-menu');
            const dynamicFilterContainer = document.getElementById('dynamic-filter-container');
            const table = document.getElementById('main-user-table');
            const tableHead = document.getElementById('main-table-head');
            const tableBody = document.getElementById('main-table-body');
            const paginationContainer = document.getElementById('pagination-container');
            const paginationInfoText = document.getElementById('pagination-info-text');
            const columnToggler = document.getElementById('columnToggler');
            const limitSelect = document.getElementById('limit');
            const quickViewContainer = document.getElementById('quick-view-container');

            // =========================================================================
            // 2. DYNAMIC TABLE & UI RENDERING
            // =========================================================================

            function renderStaticHead() {
                let headHtml = '<tr>';
                Object.keys(columnConfig).forEach((key) => {
                    headHtml += `<th>${columnConfig[key].header}</th>`;
                });
                tableHead.innerHTML = headHtml + '</tr>';
            }

            function updateColumnVisibility() {
                const styleSheet = document.getElementById('dynamic-column-styles') || document.createElement('style');
                styleSheet.id = 'dynamic-column-styles';
                let css = '';
                Object.keys(columnConfig).forEach((key, index) => {
                    if (!visibleColumns.includes(key)) {
                        css += `#main-user-table th:nth-child(${index + 1}), #main-user-table td:nth-child(${index + 1}) { display: none; }\n`;
                    } else {
                        css += `#main-user-table th:nth-child(${index + 1}), #main-user-table td:nth-child(${index + 1}) { display: table-cell; }\n`;
                    }
                });
                styleSheet.innerHTML = css;
                document.head.appendChild(styleSheet);
            }

            function renderBody() {
                let bodyHtml = '';
                if (currentUsersData.length === 0) {
                    const colspan = visibleColumns.length;
                    bodyHtml = `<tr><td colspan="${colspan}" class="text-center py-4">No users found matching criteria.</td></tr>`;
                } else {
                    const offset = (getCurrentPage() - 1) * parseInt(limitSelect.value, 10);
                    currentUsersData.forEach((user, index) => {
                        bodyHtml += '<tr>';
                        Object.keys(columnConfig).forEach(key => {
                             bodyHtml += `<td>${getCellContent(user, key, offset + index)}</td>`;
                        });
                        bodyHtml += '</tr>';
                    });
                }
                tableBody.innerHTML = bodyHtml;
                updateColumnVisibility();
            }
            
            function getCellContent(user, key, serialIndex) {
                const config = columnConfig[key];
                if (!config) return '';
                const dataKeyValue = config.dataKey || key;
                const value = user[dataKeyValue] ?? null;
                const escape = (str) => String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);

                switch (config.type) {
                    case 'serial':
                        return serialIndex + 1;

                    case 'image':
                        const path = user.profile_picture_path || '';
                        return path ? `<div class="profile-thumbnail-container"><img src="${escape(path)}" class="profile-thumbnail" alt="Photo" data-full-path="${escape(path)}" onerror="this.style.display='none'; this.parentElement.innerHTML = '<div class=\\'no-pic-placeholder\\'>No Pic</div>';"></div>` : '<div class="no-pic-placeholder">No Pic</div>';

                    case 'date':
                        return value ? new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A';

                    case 'badge':
                        if (value === null) return 'N/A';
                        const isActive = value === '1';
                        return `<span class="badge ${isActive ? 'bg-success' : 'bg-danger'}">${isActive ? 'Active' : 'Inactive'}</span>`;

                    case 'status_badge':
                        const needsAction = value == '1';
                        return `<span class="badge ${needsAction ? 'bg-danger' : 'bg-success'}">${needsAction ? 'Action Needed' : 'Completed'}</span>`;

                    case 'bool':
                        return value == '1' ? 'Yes' : 'No';

                    case 'special':
                        return escape([user.salutation, user.first_name, user.middle_name, user.surname].filter(Boolean).join(' '));

                    case 'actions':
    const hrClass = user.hr_action_needed == '0' ? 'btn-success' : 'btn-primary';
    const itClass = user.it_action_needed == '0' ? 'btn-success' : 'btn-dark';
    const hrButtonText = user.hr_action_needed == '0' ? 'Edit HR' : 'Add HR';
    const itButtonText = user.it_action_needed == '0' ? 'Edit IT' : 'Add IT';

    // This single flex container with a 'gap' provides the clean, horizontal layout.
    return `<div class="d-flex flex-wrap" style="gap: 0.25rem;">
        <a href="view_user_details.php?user_id=${user.user_id}" class="btn btn-info btn-sm table-action-btn" title="View Details">
            <i class="fa fa-eye me-1"></i>View
        </a>
        <a href="edit_user_form.php?user_id=${user.user_id}" class="btn btn-warning btn-sm table-action-btn" title="Edit Basic Info">
            <i class="fa fa-edit me-1"></i>Edit
        </a>
        <a href="hr_onboarding_form.php?user_id=${user.user_id}" class="btn btn-sm table-action-btn ${hrClass}" title="${hrButtonText}">
            <i class="fa fa-users me-1"></i>${hrButtonText}
        </a>
        <a href="it_setup_form.php?user_id=${user.user_id}" class="btn btn-sm table-action-btn ${itClass}" title="${itButtonText}">
            <i class="fa fa-laptop me-1"></i>${itButtonText}
        </a>
        <a href="actions/delete_user.php?user_id=${user.user_id}" class="btn btn-danger btn-sm table-action-btn" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?');">
            <i class="fa fa-trash me-1"></i>Delete
        </a>
    </div>`;

                    default:
                        return escape(value ?? 'N/A');
                }
            }

            function renderPagination(currentPage, totalPages) {
                if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }
                let html = '<ul class="pagination mb-0">';
                const createPageItem = (p, text, isDisabled = false, isActive = false) => `<li class="page-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${text}</a></li>`;
                html += createPageItem(currentPage - 1, 'Previous', currentPage === 1);
                const range = 2;
                let start = Math.max(1, currentPage - range), end = Math.min(totalPages, currentPage + range);
                if (start > 1) html += createPageItem(1, '1') + (start > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '');
                for (let i = start; i <= end; i++) html += createPageItem(i, i, false, i === currentPage);
                if (end < totalPages) html += (end < totalPages - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : '') + createPageItem(totalPages, totalPages);
                html += createPageItem(currentPage + 1, 'Next', currentPage === totalPages);
                html += '</ul>';
                paginationContainer.innerHTML = html;
            }

            // =========================================================================
            // 3. DATA FETCHING & STATE MANAGEMENT
            // =========================================================================

            function debounce(func, delay) {
                let timeout;
                return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); };
            }

            async function fetchUsers(page = 1) {
                tableBody.innerHTML = `<tr><td colspan="${Object.keys(columnConfig).length}" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>`;
                paginationInfoText.textContent = 'Loading...';
                paginationContainer.innerHTML = '';

                const params = new URLSearchParams({ search: searchInput.value, limit: limitSelect.value, page: page, view: currentViewMode });
                Object.entries(activeAdvancedFilters).forEach(([key, filterObj]) => {
                    if (filterObj.config.filterType === 'date_range') {
                        const fromVal = filterObj.el.querySelector(`[name="${key}_from"]`).value;
                        const toVal = filterObj.el.querySelector(`[name="${key}_to"]`).value;
                        if (fromVal) params.append(`${key}_from`, fromVal);
                        if (toVal) params.append(`${key}_to`, toVal);
                    } else {
                        const value = filterObj.instance?.getValue();
                        if (value) params.append(key, value);
                    }
                });

                try {
                    const response = await fetch(`view_users.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`HTTP error! status: ${response.status}. Response: ${errorText}`);
                    }
                    const data = await response.json();
                    
                    currentUsersData = data.users || [];
                    renderBody();
                    paginationInfoText.textContent = data.pagination.showing_text;
                    renderPagination(data.pagination.current_page, data.pagination.total_pages);
                    window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
                } catch (error) {
                    console.error('Error fetching data:', error);
                    tableBody.innerHTML = `<tr><td colspan="${Object.keys(columnConfig).length}" class="text-center py-4 text-danger">Failed to load data. Check console for details.</td></tr>`;
                }
            }
            
            const debouncedFetchUsers = debounce(() => fetchUsers(1), 400);
            const getCurrentPage = () => parseInt(new URLSearchParams(window.location.search).get('page') || 1, 10);

            // =========================================================================
            // 4. EVENT HANDLERS & COMPONENT LOGIC
            // =========================================================================

            function addAdvancedFilter(key) {
                if (activeAdvancedFilters[key]) return;
                const filterConfig = masterFilterList[key];
                
                let colClass = 'col-md-4';
                let filterHtml = '';
                if (filterConfig.filterType === 'date_range') {
                    colClass = 'col-md-6';
                    filterHtml = `<div class="input-group input-group-sm"><span class="input-group-text">${filterConfig.label} From</span><input type="date" class="form-control" name="${key}_from"><span class="input-group-text">To</span><input type="date" class="form-control" name="${key}_to"><button class="btn btn-outline-danger" type="button" data-remove-key="${key}">&times;</button></div>`;
                } else {
                    filterHtml = `<div class="input-group input-group-sm"><span class="input-group-text">${filterConfig.label}</span><select name="${key}" placeholder="-- Select ${filterConfig.label} --"></select><button class="btn btn-outline-danger" type="button" data-remove-key="${key}">&times;</button></div>`;
                }

                const col = document.createElement('div');
                col.className = colClass;
                col.dataset.filterKey = key;
                col.innerHTML = filterHtml;
                dynamicFilterContainer.appendChild(col);
                
                const filterControl = (filterConfig.filterType === 'date_range') ? col : col.querySelector('select');
                
                addFilterMenu.querySelector(`[data-filter-key="${key}"]`)?.classList.add('disabled');
                col.querySelector(`[data-remove-key]`).addEventListener('click', () => removeAdvancedFilter(key));

                if (filterConfig.filterType === 'select') {
                    const tomSelectInstance = new TomSelect(filterControl, {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        load: function(query, callback) {
                            const params = new URLSearchParams({ filter: key, view: currentViewMode });
                            Object.entries(activeAdvancedFilters).forEach(([otherKey, otherFilter]) => {
                                if (key !== otherKey) {
                                     if (otherFilter.config.filterType === 'date_range') {
                                        const fromVal = otherFilter.el.querySelector(`[name="${otherKey}_from"]`).value;
                                        const toVal = otherFilter.el.querySelector(`[name="${otherKey}_to"]`).value;
                                        if (fromVal) params.append(`${otherKey}_from`, fromVal);
                                        if (toVal) params.append(`${otherKey}_to`, toVal);
                                    } else {
                                        const otherValue = otherFilter.instance?.getValue();
                                        if(otherValue) params.append(otherKey, otherValue);
                                    }
                                }
                            });
                            fetch(`${basePath}actions/get_filter_options.php?${params.toString()}`)
                                .then(response => response.json())
                                .then(options => {
                                    callback(options.map(opt => ({value: opt, text: opt})));
                                }).catch(() => callback());
                        },
                        onChange: () => {
                            repopulateAllFilters(key);
                            debouncedFetchUsers();
                        }
                    });
                    activeAdvancedFilters[key] = { el: filterControl, config: filterConfig, instance: tomSelectInstance };
                    tomSelectInstance.load();
                } else {
                    activeAdvancedFilters[key] = { el: filterControl, config: filterConfig, instance: null };
                    col.querySelectorAll('input[type="date"]').forEach(input => {
                        input.addEventListener('change', () => {
                            repopulateAllFilters();
                            debouncedFetchUsers();
                        });
                    });
                }
            }

            function removeAdvancedFilter(key) {
                const filter = activeAdvancedFilters[key];
                if (filter && filter.instance) {
                    filter.instance.destroy();
                }
                dynamicFilterContainer.querySelector(`[data-filter-key="${key}"]`)?.remove();
                delete activeAdvancedFilters[key];
                addFilterMenu.querySelector(`[data-filter-key="${key}"]`)?.classList.remove('disabled');
                repopulateAllFilters();
                fetchUsers(1);
            }
            
            function repopulateAllFilters(changedKey = null) {
                Object.keys(activeAdvancedFilters).forEach(key => {
                    if (key !== changedKey) {
                        const filter = activeAdvancedFilters[key];
                        if (filter.instance) {
                            const currentValue = filter.instance.getValue();
                            filter.instance.load(callback => {
                                filter.instance.settings.load.call(filter.instance, '', items => {
                                    callback(items);
                                    const newOptions = items.map(item => item.value);
                                    if (newOptions.includes(currentValue)) {
                                        filter.instance.setValue(currentValue, true);
                                    }
                                });
                            });
                        }
                    }
                });
            }

            Object.entries(masterFilterList).forEach(([key, config]) => {
                addFilterMenu.innerHTML += `<li><a class="dropdown-item" href="#" data-filter-key="${key}">${config.label}</a></li>`;
            });
            addFilterMenu.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); const key = e.target.dataset.filterKey; if (key && !e.target.classList.contains('disabled')) addAdvancedFilter(key); });
            
            searchInput.addEventListener('input', () => {
                quickViewContainer.querySelectorAll('button.active').forEach(b => b.classList.remove('active'));
                debouncedFetchUsers();
            });

            limitSelect.addEventListener('change', () => fetchUsers(1));
            resetFiltersBtn.addEventListener('click', () => {
                currentViewMode = 'working'; 
                searchInput.value = ''; 
                dynamicFilterContainer.innerHTML = ''; 
                activeAdvancedFilters = {}; 
                addFilterMenu.querySelectorAll('a.disabled').forEach(opt => opt.classList.remove('disabled')); 
                quickViewContainer.querySelectorAll('button').forEach(btn => btn.classList.toggle('active', btn.dataset.view === currentViewMode)); 
                fetchUsers(1); 
            });
            
            quickViewContainer.addEventListener('click', function(e) { 
                if (e.target.tagName === 'BUTTON') { 
                    const view = e.target.dataset.view; 
                    if (view) { 
                        currentViewMode = view; 
                        searchInput.value = ''; 
                        dynamicFilterContainer.innerHTML = ''; 
                        activeAdvancedFilters = {}; 
                        addFilterMenu.querySelectorAll('a.disabled').forEach(opt => opt.classList.remove('disabled')); 
                        quickViewContainer.querySelectorAll('button').forEach(btn => btn.classList.remove('active')); 
                        e.target.classList.add('active');
                        fetchUsers(1); 
                    } 
                } 
            });

            paginationContainer.addEventListener('click', function(e) { if (e.target.tagName === 'A' && e.target.dataset.page) { e.preventDefault(); const page = parseInt(e.target.dataset.page, 10); if (!isNaN(page) && page > 0) fetchUsers(page); } });

            function setupColumnToggler() {
                columnToggler.innerHTML = '';
                Object.entries(columnConfig).forEach(([key, config]) => {
                    if (config.type === 'actions' || config.type === 'serial') return;
                    const isChecked = visibleColumns.includes(key);
                    columnToggler.innerHTML += `<li><div class="dropdown-item"><input type="checkbox" class="form-check-input me-2" data-key="${key}" id="toggle_col_${key}" ${isChecked ? 'checked' : ''}><label for="toggle_col_${key}" class="form-check-label">${config.header}</label></div></li>`;
                });
            }

            function updateColumnTogglerCheckboxes() {
                 columnToggler.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = visibleColumns.includes(cb.dataset.key);
                });
            }

            columnToggler.addEventListener('change', (e) => {
                if (e.target.type === 'checkbox') {
                    const key = e.target.dataset.key;
                    if (e.target.checked) { if (!visibleColumns.includes(key)) { const originalPos = Object.keys(columnConfig).indexOf(key); let inserted = false; for (let i = 0; i < visibleColumns.length; i++) { const currentPos = Object.keys(columnConfig).indexOf(visibleColumns[i]); if (currentPos > originalPos) { visibleColumns.splice(i, 0, key); inserted = true; break; } } if (!inserted) visibleColumns.push(key); } } else { visibleColumns = visibleColumns.filter(c => c !== key); }
                    localStorage.setItem('visibleColumns', JSON.stringify(visibleColumns));
                    updateColumnVisibility();
                }
            });
            columnToggler.addEventListener('click', (e) => e.stopPropagation());

            function updateQuickViewCounts(counts) {
                if (!counts) return;
                const updateButton = (view, count) => {
                    const button = quickViewContainer.querySelector(`[data-view="${view}"]`);
                    const badge = button?.querySelector('.badge');
                    if (badge) {
                        badge.textContent = count;
                        badge.style.display = count > 0 ? '' : 'none';
                        if ((view === 'hr_needed' || view === 'it_needed') && count > 0) {
                            badge.className = 'badge ms-1 blinking-badge';
                        } else {
                            badge.className = 'badge bg-secondary ms-1';
                        }
                    }
                };
                updateButton('working', counts.working);
                updateButton('resigned', counts.resigned);
                updateButton('new_users', counts.new_users);
                updateButton('hr_needed', counts.hr_needed);
                updateButton('it_needed', counts.it_needed);
                const defaultButtonBadge = quickViewContainer.querySelector('[data-view="default"] .badge');
                if(defaultButtonBadge) defaultButtonBadge.style.display = 'none';
            }

            // =========================================================================
            // 5. INITIALIZATION
            // =========================================================================
            function initialize() {
                const initialParams = new URLSearchParams(window.location.search);
                currentViewMode = initialParams.get('view') || 'working'; 
                quickViewContainer.querySelectorAll('button').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.view === currentViewMode);
                });
                searchInput.value = initialParams.get('search') || '';
                limitSelect.value = initialParams.get('limit') || 25;
                initialParams.forEach((value, key) => { if (masterFilterList[key]) {
                    if (key.endsWith('_from') || key.endsWith('_to')) {
                        const baseKey = key.replace(/_from$|_to$/, '');
                        if (!activeAdvancedFilters[baseKey]) addAdvancedFilter(baseKey);
                        document.querySelector(`[name="${key}"]`).value = value;
                    } else {
                        addAdvancedFilter(key, value);
                    }
                } });
                renderStaticHead();
                setupColumnToggler();
                updateQuickViewCounts(quickViewCounts);
                setTimeout(() => fetchUsers(initialParams.get('page') || 1), 50);

                let profilePopup = null; // Variable to hold the popup element

            tableBody.addEventListener('mouseover', function(e) {
                const thumbnail = e.target.closest('.profile-thumbnail');
                if (thumbnail && thumbnail.dataset.fullPath && !profilePopup) {
                    
                    profilePopup = document.createElement('div');
                    profilePopup.style.position = 'absolute';
                    profilePopup.style.zIndex = '1080'; // High z-index to appear over other elements
                    profilePopup.style.border = '3px solid white';
                    profilePopup.style.boxShadow = '0 5px 15px rgba(0,0,0,0.4)';
                    profilePopup.style.borderRadius = '0.25rem';
                    profilePopup.style.pointerEvents = 'none'; // Prevent mouse events on the popup itself
                    
                    const fullImage = document.createElement('img');
                    fullImage.src = thumbnail.dataset.fullPath;
                    fullImage.style.width = '150px'; // Passport-size width
                    fullImage.style.height = 'auto';
                    fullImage.style.display = 'block';

                    profilePopup.appendChild(fullImage);
                    document.body.appendChild(profilePopup);

                    // Position next to the cursor
                    const xOffset = 15;
                    const yOffset = 15;
                    let left = e.pageX + xOffset;
                    let top = e.pageY + yOffset;

                    // Adjust if it goes off-screen
                    if (left + 150 > window.innerWidth) {
                        left = e.pageX - 150 - xOffset;
                    }
                    
                    profilePopup.style.left = `${left}px`;
                    profilePopup.style.top = `${top}px`;
                }
            });

            tableBody.addEventListener('mouseout', function(e) {
                const thumbnail = e.target.closest('.profile-thumbnail');
                if (thumbnail && profilePopup) {
                    if (document.body.contains(profilePopup)) {
                         document.body.removeChild(profilePopup);
                    }
                    profilePopup = null;
                }
            });
            }
            
            initialize();
        });
    </script>
</body>
</html>