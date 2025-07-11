<?php
session_start(); // Start the session at the very beginning

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Assuming your login page is login.php
    exit;
}

// Retrieve user info from session
$original_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; // Get raw username
$username_for_display = str_replace('.', ' ', $original_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display))); 
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in'; 
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';

// --- Database Configuration for Avatar ---
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";       
$db_name = "user_master_db"; 
$avatar_path = "assets/img/kaiadmin/default-avatar.png"; 

if ($empcode !== 'N/A') {
    $conn_avatar = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$conn_avatar->connect_error) {
        $sql_avatar = "SELECT users.profile_picture_path
                       FROM users
                       JOIN user_hr_details ON users.user_id = user_hr_details.user_id
                       WHERE user_hr_details.employee_id_ascent = ?";
        if ($stmt_avatar = $conn_avatar->prepare($sql_avatar)) {
            $stmt_avatar->bind_param("s", $empcode); 
            $stmt_avatar->execute();
            $result_avatar = $stmt_avatar->get_result();
            if ($result_avatar->num_rows > 0) {
                $row_avatar = $result_avatar->fetch_assoc();
                $db_avatar_path = $row_avatar['profile_picture_path']; 
                if (!empty($db_avatar_path)) {
                    // Assuming this main file is at the root, and registration_project is a sibling folder
                    $avatar_path = "../registration_project/".$db_avatar_path;
                }
            }
            $stmt_avatar->close();
        }
        $conn_avatar->close();
    }
}

// --- Logic for Item Group Details page ---
$project_base_path = './'; // Relative path to the project folder
require_once $project_base_path . 'sap_logic.php'; 

$page_specific_title = "Item Group Details (Smart Sync)"; // Title for this specific page

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
        google: { families: ["Public Sans:300,400,500,600,700", "Inter:300,400,500,600,700"] }, // Added Inter font
        custom: {
          families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () { sessionStorage.fonts = true; },
      });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" /> <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"> <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <style>
        /* Main template's custom styles (from your last version) */
        .page-inner .table thead { background-color: #007bff; color: white; }
        .page-inner .table tbody tr:hover { background-color: #e9ecef; }
        /* .page-inner .page-header-title is defined below from project styles */
        .page-inner a:not(.btn):not(.nav-link):not(.dropdown-item) { color: #0056b3; text-decoration: none; } /*Scoped project links*/
        .page-inner a:not(.btn):not(.nav-link):not(.dropdown-item):hover { text-decoration: underline; color: #003366; }
        .table-responsive { box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px; /* overflow: hidden; */ /* Kaiadmin .table-responsive has this */ }
        .avatar-sm img, .avatar-lg img { object-fit: cover; width: 100%; height: 100%; }
        .welcome-message { text-align: center; margin-top: 50px; font-size: 1.5rem; color: #555; }
        .user-box .u-text p.text-muted { margin-bottom: 0.25rem; }

        /* Styles from Item Group Details, adapted for .page-inner context */
        /* Ensure these variables don't clash or are preferred for this section */
        .page-inner { 
            --simplex-primary-project: #00529B; 
            --simplex-secondary-project: #007bff; 
            --simplex-light-blue-project: #F0F8FF; 
            --simplex-dark-text-project: #002A4E; 
            --simplex-white-project: #FFFFFF;
            --simplex-gray-text-project: #495057;
            --simplex-border-color-project: #dee2e6;
            --simplex-hover-bg-project: #cfe2ff; 
        }
        .page-inner .page-header-title { /* Style for the H3 "Item Group Details" */
            /* text-align: center; */ /* Main template H3 is usually left */
            margin-bottom: 1rem !important; /* Align with Kaiadmin spacing */
            font-weight: bold;
            color: var(--simplex-dark-text-project, #002A4E); 
            /* padding-bottom: 10px; */
            /* border-bottom: 2px solid var(--simplex-secondary-project, #007bff); */
        }

        .page-inner #syncStatusIndicator {
            font-size: 0.8rem; padding: 0.25rem 0.75rem; border-radius: 0.25rem;
            font-weight: 500; display: none; 
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .page-inner #syncStatusIndicator.syncing { background-color: #ffc107; color: #383d41; display: inline-block; }
        .page-inner #syncStatusIndicator.synced { background-color: #198754; color: white; display: inline-block; }
        .page-inner #syncStatusIndicator.error { background-color: #dc3545; color: white; display: inline-block; }
        
        .page-inner .filter-section { 
            background-color: var(--simplex-white-project, #FFFFFF); padding: 25px; border-radius: 0.5rem; 
            margin-bottom: 25px; border: 1px solid var(--simplex-border-color-project, #dee2e6);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .page-inner .filter-section .form-label { font-weight: 500; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--simplex-dark-text-project, #002A4E); }
        .page-inner .filter-section .form-control-sm, 
        .page-inner .filter-section .select2-container--bootstrap-5 .select2-selection--single { 
            height: calc(1.5em + .875rem + 2px); padding: .4375rem .875rem; font-size: .875rem; 
            border-radius: 0.375rem; border-color: #ced4da; 
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .page-inner .filter-section .form-control-sm:focus,
        .page-inner .filter-section .select2-container--bootstrap-5.select2-container--focus .select2-selection--single { 
            border-color: var(--simplex-secondary-project, #007bff); box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .page-inner #lengthMenuContainer .dataTables_length { display: flex; align-items: center; justify-content: flex-start; height: 100%; padding-top: 0.4375rem; }
        .page-inner #lengthMenuContainer .dataTables_length label { margin-bottom: 0 !important; font-weight: 500; font-size: 0.875rem; color: var(--simplex-dark-text-project, #002A4E); display: flex; align-items: center; }
        .page-inner #lengthMenuContainer .dataTables_length select.form-select-sm { border-radius: 0.375rem !important; padding: 0.4375rem 1.75rem 0.4375rem 0.875rem !important; background-position: right 0.6rem center !important; background-size: 14px 10px !important; border: 1px solid #ced4da !important; font-size: .875rem !important; color: #212529 !important; line-height: 1.5 !important; appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important; margin-left: 0.5rem !important; margin-right: 0.25rem !important; max-width: 100px; height: calc(1.5em + .875rem + 2px); }
        
        .page-inner #itemGroupTable thead th { background-color: #e9ecef; color: var(--simplex-dark-text-project, #002A4E); vertical-align: middle; white-space: nowrap; font-size:0.9rem; font-weight: 600; border-bottom: 2px solid var(--simplex-border-color-project, #dee2e6); }
        .page-inner #itemGroupTable tbody tr:hover { background-color: var(--simplex-hover-bg-project, #cfe2ff) !important; }
        .page-inner #itemGroupTable td { font-size: 0.875rem; vertical-align: middle; color: var(--simplex-gray-text-project, #495057); }
        
        .page-inner .table-responsive-custom { border: 1px solid var(--simplex-border-color-project, #dee2e6); border-radius: 0.5rem; background-color: var(--simplex-white-project, #FFFFFF); box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .page-inner #userFeedback .alert { font-size: 0.9rem; margin-bottom: 0.75rem; }
        .select2-container--open { z-index: 1056 !important; } /* Ensure Select2 dropdown is above other elements, adjust if needed */
        
        .page-inner .dt-footer-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding-top: 1rem; padding-bottom: 0.5rem; padding-left: 1rem; padding-right: 1rem; }
        .page-inner .dt-footer-row .dataTables_info { margin-bottom: 0.5rem; }
        .page-inner .dt-footer-row .dataTables_paginate { margin-bottom: 0.5rem; }
        .page-inner .dataTables_paginate .pagination { margin-bottom: 0; justify-content: flex-end; }
        @media (max-width: 767.98px) { .page-inner .dt-footer-row { justify-content: center; text-align: center; padding-left: 0.5rem; padding-right: 0.5rem; } .page-inner .dt-footer-row .dataTables_info, .page-inner .dt-footer-row .dataTables_paginate { width: 100%; justify-content: center; } .page-inner .dataTables_paginate .pagination { justify-content: center; } }
        .page-inner .page-item.active .page-link { background-color: var(--simplex-primary-project, #00529B); border-color: var(--simplex-primary-project, #00529B); box-shadow: 0 0 0 0.1rem rgba(0, 82, 155, 0.5); }
        .page-inner .page-item:not(.disabled) .page-link:hover { background-color: var(--simplex-hover-bg-project, #cfe2ff); border-color: var(--simplex-secondary-project, #007bff); color: var(--simplex-primary-project, #00529B); }
        
        .page-inner table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before, 
        .page-inner table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before { background-color: var(--simplex-primary-project, #00529B); border: 2px solid white; border-radius: 50%; box-shadow: 0 0 3px rgba(0,0,0,0.5); top: 50%; left: 5px; height: 16px; width: 16px; margin-top: -10px; line-height: 13px; font-weight: bold; color: white; content: '+'; }
        .page-inner table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td.dtr-control:before, 
        .page-inner table.dataTable.dtr-inline.collapsed>tbody>tr.parent>th.dtr-control:before { background-color: var(--simplex-secondary-project, #007bff); content: '-'; }

    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="dashboard.php" class="logo"> 
                        <img src="assets/img/kaiadmin/simplex_icon_2.png" alt="navbar brand" class="navbar-brand" height="50" />
                    </a>
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
                        <a href="dashboard.php" class="logo"> 
                            <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
                        </a>
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
                            <a href="dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: #333;">
                                <img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 40px; margin-right: 10px;" /> <span style="font-size: 1.5rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%;"> Simplex Engineering and Foundry Works </span>
                            </a>
                        </div>
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User Avatar" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';" />
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
                                                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="image profile" class="avatar-img rounded" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';" />
                                                </div>
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
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">My Profile</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Account Setting</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="logout.php">Logout</a> 
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
                        <h3 class="fw-bold mb-3 page-header-title"><?php echo htmlspecialchars($page_specific_title); ?></h3>
                        <span id="syncStatusIndicator" class="ms-3"></span> 
                    </div>
                
                    <div id="userFeedback" class="mb-3">
                        <?php
                        if (isset($_SESSION['sync_feedback_messages']) && !empty($_SESSION['sync_feedback_messages'])) {
                            foreach ($_SESSION['sync_feedback_messages'] as $msg) {
                                $alert_type = htmlspecialchars($msg['type'] ?? 'info');
                                $alert_text = htmlspecialchars($msg['text'] ?? 'Unknown status.');
                                echo "<div class='alert alert-{$alert_type} alert-dismissible fade show' role='alert'>{$alert_text}<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
                            }
                            unset($_SESSION['sync_feedback_messages']); 
                        }
                        ?>
                    </div>

                    <div class="filter-section"> <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 flex-grow-1" style="color: var(--simplex-primary-project);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-funnel-fill me-2" viewBox="0 0 16 16"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.777.416l-3-1.5A.5.5 0 0 1 6 11.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z"/></svg>
                                Filter Options
                            </h5>
                            <button type="button" id="clearFiltersButtonDb" class="btn btn-outline-secondary btn-sm rounded-pill ms-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle me-1" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                Clear Filters
                            </button>
                        </div>
                        <hr class="mt-0 mb-3">
                        <form id="filterForm">
                            <div class="row g-2 mb-3 align-items-end"> 
                                <div class="col-md-auto" id="lengthMenuContainer" style="min-width: 200px;"></div>
                            </div>
                            <div class="row g-3"> 
                                <div class="col-md-6 col-lg-3">
                                    <label for="globalSearchInput" class="form-label">Global Search:</label>
                                    <input type="text" id="globalSearchInput" class="form-control form-control-sm rounded-3" placeholder="Search displayed fields...">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="material_no_filter_input" class="form-label">Material No:</label>
                                    <select id="material_no_filter_input" name="material_no_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Type or Select Material No">
                                        <option value=""></option> 
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="description_filter_input" class="form-label">Description:</label>
                                    <select id="description_filter_input" name="description_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Type or Select Description">
                                        <option value=""></option> 
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="plant_filter" class="form-label">Plant:</label>
                                    <select id="plant_filter" name="plant_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Select Plant">
                                        <option value=""></option> 
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6 col-lg-3">
                                    <label for="product_group_filter" class="form-label">Product Group:</label>
                                    <select id="product_group_filter" name="product_group_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Select Prod. Group">
                                        <option value=""></option> 
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="product_group_name_filter" class="form-label">Prod. Group Name:</label>
                                    <select id="product_group_name_filter" name="product_group_name_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Select Prod. Grp. Name">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="ext_material_group_filter" class="form-label">External Mat. Group:</label>
                                    <select id="ext_material_group_filter" name="ext_material_group_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Select Ext. Mat. Group">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="ext_material_group_name_filter" class="form-label">Ext. Mat. Group Name:</label>
                                    <select id="ext_material_group_name_filter" name="ext_material_group_name_filter" class="form-select form-select-sm select2-dropdown-local rounded-3" data-placeholder="Select Ext. Mat. Grp. Name">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive-custom mt-4"> <table id="itemGroupTable" class="table table-sm table-striped table-bordered table-hover dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Material No</th><th>Description</th><th>Plant</th><th>Prod. Group</th><th>Prod. Group Name</th><th>Ext. Group</th><th>Ext. Group Name</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left"><ul class="nav"></ul></nav>
                    <div class="copyright">
                        <?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by
                        <a href="#">Abhimanyu</a> 
                    </div>
                    <div>
                        For <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering & Foundry Works PVT. LTD.</a>.
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <script src="assets/js/kaiadmin.min.js"></script> 

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        const ajaxUrl = "<?php echo $project_base_path; ?>sap_logic.php"; 
    </script>
    <script src="<?php echo $project_base_path; ?>app_scripts.js?v=<?php echo time(); ?>"></script>

</body>
</html>