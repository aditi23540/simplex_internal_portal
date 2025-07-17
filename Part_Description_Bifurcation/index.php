<?php
// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit;
}

// Retrieve user info from session
$original_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$username_for_display = str_replace('.', ' ', $original_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display))); 
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in'; 
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';

// --- Database Configuration for Avatar ---
$db_host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "user_master_db"; 
$avatar_path = "assets/img/kaiadmin/default-avatar.png"; 
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
                if (!empty($db_avatar_path)) { $avatar_path = "../registration_project/".$db_avatar_path; }
            }
            $stmt_avatar->close();
        }
        $conn_avatar->close();
    }
}

// --- Configuration for the current page: Part Description Bifurcation ---
$project_base_path = './'; // Relative path to the project folder
require_once $project_base_path . 'config.php'; // Contains $columnConfigs or $columnConfigsGlobal

$page_specific_title = "Part Description Bifurcation"; 
$columnConfigsJS = json_encode($columnConfigsGlobal ?? $columnConfigs ?? []); // Prepare for JS

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
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <style>
        /* Main template's existing custom styles */
        .page-inner .table thead th { background-color: #007bff; color: white; }
        .page-inner .table tbody tr:hover { background-color: #e9ecef; }
        .page-inner a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link) { color: #0056b3; text-decoration: none; }
        .page-inner a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link):hover { text-decoration: underline; color: #003366; }
        .table-responsive { box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px; }
        .avatar-sm img, .avatar-lg img { object-fit: cover; width: 100%; height: 100%; }
        .user-box .u-text p.text-muted { margin-bottom: 0.25rem; }
        .page-inner .page-header-title { margin-bottom: 1rem !important; font-weight: bold; color: #002A4E; }

        /* Styles from Part Description Bifurcation, adapted for .page-inner context */
        .page-inner { 
            --simplex-primary-pdb: #00529B; 
            --simplex-secondary-pdb: #007bff; 
            --simplex-light-blue-pdb: #F0F8FF; 
            --simplex-dark-text-pdb: #002A4E; 
            --simplex-white-pdb: #FFFFFF;
            --simplex-gray-text-pdb: #495057;
            --simplex-border-color-pdb: #dee2e6;
            --simplex-hover-bg-pdb: #cfe2ff; 
        }
        .page-inner .filter-section { 
            background-color: var(--simplex-white-pdb); padding: 25px; border-radius: 0.5rem; 
            margin-bottom: 25px; border: 1px solid var(--simplex-border-color-pdb);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .page-inner .filter-section .form-label { font-weight: 500; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--simplex-dark-text-pdb); }
        .page-inner .filter-section .form-control, .page-inner .filter-section .form-select,
        .page-inner .filter-section .select2-container--bootstrap-5 .select2-selection--single { 
            height: calc(1.5em + .875rem + 2px); padding: .4375rem .875rem; 
            font-size: .875rem; border-radius: 0.375rem; border-color: #ced4da; 
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .page-inner .filter-section .form-control:focus, .page-inner .filter-section .form-select:focus,
        .page-inner .filter-section .select2-container--bootstrap-5.select2-container--focus .select2-selection--single { 
            border-color: var(--simplex-secondary-pdb); box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .page-inner .filter-section .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0; }
        .page-inner .filter-section .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
        .page-inner .filter-section .btn { transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, opacity 0.15s ease-in-out; }
        .page-inner .filter-section .btn:hover { opacity: 0.85; }
        .page-inner .filter-section .btn:active, .page-inner .filter-section .btn:focus { box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.35); }
        
        .page-inner .analytics-section { margin-bottom: 30px; }
        .page-inner .analytics-section h2.section-title { color: var(--simplex-dark-text-pdb); margin-bottom: 15px; font-size: 1.25rem; font-weight: 600;}
        .page-inner .stats-container { display: flex; justify-content: space-around; gap: 15px; flex-wrap: wrap; }
        .page-inner .stat-card {
            background-color: var(--simplex-white-pdb); padding: 15px; border-radius: 0.375rem; 
            text-align: center; flex-grow: 1; min-width: 170px; 
            border: 1px solid var(--simplex-border-color-pdb); box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: transform 0.1s ease-in-out, box-shadow 0.1s ease-in-out;
        }
        .page-inner .stat-card.analytic-card-button { cursor: pointer; }
        .page-inner .stat-card.analytic-card-button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
        .page-inner .stat-card h3 { margin-bottom: 8px; color: var(--simplex-primary-pdb); font-size: 1rem; font-weight: 600; }
        .page-inner .stat-card p { margin-bottom: 0.3rem; line-height: 1.3; } 
        .page-inner .stat-card .count-value, .page-inner .stat-card .prompt-message { font-size: 1.2em; font-weight: 500; color: var(--simplex-dark-text-pdb); min-height: 1.3em; display: inline-block;}
        .page-inner .stat-card .prompt-message {font-style: italic; color: var(--simplex-gray-text-pdb); font-size: 0.9em; }
        .page-inner .stat-card .count-label { font-size: 0.75em; color: var(--simplex-gray-text-pdb); text-transform: uppercase; }

        .page-inner #bifurcationTable thead th { 
            background-color: #e9ecef; color: var(--simplex-dark-text-pdb); vertical-align: middle; 
            white-space: nowrap; font-size:0.9rem; font-weight: 600;
            border-bottom: 2px solid var(--simplex-border-color-pdb);
        }
        .page-inner #bifurcationTable tbody tr:hover { background-color: var(--simplex-hover-bg-pdb) !important; cursor: default; }
        .page-inner #bifurcationTable td { font-size: 0.875rem; vertical-align: middle; color: var(--simplex-gray-text-pdb); }
        .page-inner .table-responsive-custom { 
            width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; 
            border: 1px solid var(--simplex-border-color-pdb); border-radius: 0.5rem;
            background-color: var(--simplex-white-pdb); box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .page-inner .dt-footer-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 1rem; }
        .page-inner #userFeedback .alert { font-size: 0.9rem; }
        .select2-container--open { z-index: 1056 !important; }
        .page-inner .type-specific-filter-group { display: none; } 
        .page-inner .filter-group-title { color: var(--simplex-secondary-pdb); font-size: 0.95rem; font-weight: 600; margin-top: 0.5rem;}
        .page-inner #lengthMenuContainerBifurcation { display: flex; align-items: center; font-size: 0.875rem; color: var(--simplex-dark-text-pdb); }
        .page-inner #lengthMenuContainerBifurcation label { margin-bottom: 0; margin-right: 0.5rem; font-weight: 500; }
        .page-inner #lengthMenuContainerBifurcation .form-select-sm { /* ... specific styles ... */ }
        .page-inner #filter-placeholder-message { text-align: center; padding: 20px; color: var(--simplex-gray-text-pdb); font-style: italic; }

        @media (max-width: 1199.98px) { .page-inner .stat-card { min-width: calc(20% - 15px); } }
        @media (max-width: 991.98px) { .page-inner .stat-card { min-width: calc(33.333% - 15px); } }
        @media (max-width: 575.98px) { .page-inner .stat-card { min-width: calc(50% - 15px); } }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="index.php" class="logo"> 
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
                        <a href="index.php" class="logo"> 
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
                            <a href="index.php" style="display: flex; align-items: center; text-decoration: none; color: #333;">
                                <img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 40px; margin-right: 10px;" />
                                <span style="font-size: 1.5rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%;"> Simplex Engineering and Foundry Works </span>
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
                        </div>
                
                    <div id="userFeedback" class="mb-3">
                        <?php
                        // Assuming feedback messages are set in $_SESSION['feedback_messages'] by config.php or script.js interactions
                        if (isset($_SESSION['feedback_messages']) && !empty($_SESSION['feedback_messages'])) {
                            foreach ($_SESSION['feedback_messages'] as $msg) {
                                $alert_type = htmlspecialchars($msg['type'] ?? 'info');
                                $alert_text = htmlspecialchars($msg['text'] ?? 'Unknown status.');
                                echo "<div class='alert alert-{$alert_type} alert-dismissible fade show' role='alert'>{$alert_text}<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
                            }
                            unset($_SESSION['feedback_messages']); 
                        }
                        ?>
                    </div>

                    <section class="analytics-section">
                        <h2 class="section-title mb-3">Analytics Overview</h2>
                        <div class="stats-container">
                            <div class="stat-card analytic-card-button" data-type="Screw Nut Bolt">
                                <h3>Fasteners</h3>
                                <p><span class="count-label">Filtered: </span><span id="fastener-filtered-count" class="count-value prompt-message">Click to View</span></p>
                                <p><span class="count-label">Total: </span><span id="fastener-total-static-count" class="count-value">0</span></p>
                            </div>
                            <div class="stat-card analytic-card-button" data-type="Pipes">
                                <h3>Pipes</h3>
                                <p><span class="count-label">Filtered: </span><span id="pipe-filtered-count" class="count-value prompt-message">Click to View</span></p>
                                <p><span class="count-label">Total: </span><span id="pipe-total-static-count" class="count-value">0</span></p>
                            </div>
                            <div class="stat-card analytic-card-button" data-type="Plate">
                                <h3>Plates</h3>
                                <p><span class="count-label">Filtered: </span><span id="plate-filtered-count" class="count-value prompt-message">Click to View</span></p>
                                <p><span class="count-label">Total: </span><span id="plate-total-static-count" class="count-value">0</span></p>
                            </div>
                            <div class="stat-card analytic-card-button" data-type="Other">
                                <h3>Other</h3>
                                <p><span class="count-label">Filtered: </span><span id="other-filtered-count" class="count-value prompt-message">Click to View</span></p>
                                <p><span class="count-label">Total: </span><span id="other-total-static-count" class="count-value">0</span></p>
                            </div>
                            <div class="stat-card"> 
                                <h3>Total Items</h3>
                                <p><span class="count-label">Filtered: </span><span id="total-item-filtered-count" class="count-value">0</span></p>
                                <p><span class="count-label">Grand Total: </span><span id="total-item-static-count" class="count-value">0</span></p>
                            </div>
                        </div>
                    </section>

                    <section class="filter-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 flex-grow-1" style="color: var(--simplex-primary-pdb);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-funnel-fill me-2" viewBox="0 0 16 16"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.777.416l-3-1.5A.5.5 0 0 1 6 11.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z"/></svg>
                                Filter Options
                            </h5>
                        </div>
                        <hr class="mt-0 mb-3">
                        <div id="filter-placeholder-message">
                            <p class="text-center text-muted">Please select a category from the Analytics Overview above to see filters and data.</p>
                        </div>
                        <form id="bifurcation-filter-form" style="display: none;">
                            <div class="row g-3 mb-3"> 
                                <div class="col-md-6 col-lg-3" style="display: none !important;">
                                    <label for="filter-main_type" class="form-label"><strong>Selected Bifurcation Type:</strong></label>
                                    <select id="filter-main_type" name="main_type" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Select Primary Type...">
                                        <option value=""></option>
                                        <option value="Screw Nut Bolt">Fasteners (Screw/Nut/Bolt)</option>
                                        <option value="Pipes">Pipes</option>
                                        <option value="Plate">Plate</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filter-material_no" class="form-label">Material No:</label>
                                    <select id="filter-material_no" name="material_no" class="form-select form-select-sm select2-ajax-dropdown rounded-3" data-placeholder="Type to search Material No...">
                                        <option value=""></option> 
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filter-material_description" class="form-label">Description (contains):</label>
                                    <select id="filter-material_description" name="material_description" class="form-select form-select-sm select2-ajax-dropdown rounded-3" data-placeholder="Type to search Description...">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label for="filter-plant" class="form-label">Plant:</label>
                                    <select id="filter-plant" name="plant" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Select Plant...">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                            <div id="type-specific-filters-container">
                                <div class="type-specific-filter-group" data-filter-group-for="Pipes">
                                    <div class="col-12"><h6 class="filter-group-title border-bottom pb-1 mb-2">Pipe Attributes</h6></div>
                                    <div class="row g-3">
                                        <div class="col-md-4 col-lg-2"><label for="filter-nb-pipe" class="form-label">NB:</label><select id="filter-nb-pipe" name="nb" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="NB..."><option value=""></option></select></div>
                                        <div class="col-md-4 col-lg-2"><label for="filter-od-pipe" class="form-label">OD:</label><select id="filter-od-pipe" name="od" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="OD..."><option value=""></option></select></div>
                                        <div class="col-md-4 col-lg-3"><label for="filter-thickness-pipe" class="form-label">Thickness:</label><select id="filter-thickness-pipe" name="thickness" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Thickness..."><option value=""></option></select></div>
                                        <div class="col-md-6 col-lg-3"><label for="filter-standard-pipe" class="form-label">Standard:</label><select id="filter-standard-pipe" name="standard" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Standard..."><option value=""></option></select></div>
                                        <div class="col-md-6 col-lg-2"><label for="filter-uom-pipe" class="form-label">UOM:</label><select id="filter-uom-pipe" name="uom" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="UOM..."><option value="M">M</option></select></div>
                                    </div>
                                </div>
                                <div class="type-specific-filter-group" data-filter-group-for="Plate">
                                    <div class="col-12"><h6 class="filter-group-title border-bottom pb-1 mb-2">Plate Attributes</h6></div>
                                    <div class="row g-3">
                                        <div class="col-md-4 col-lg-3"><label for="filter-thickness-plate" class="form-label">Thickness:</label><select id="filter-thickness-plate" name="thickness" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Thickness..."><option value=""></option></select></div>
                                        <div class="col-md-4 col-lg-3"><label for="filter-grade-plate" class="form-label">Grade:</label><select id="filter-grade-plate" name="grade" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Grade..."><option value=""></option></select></div>
                                        <div class="col-md-6 col-lg-3"><label for="filter-standard-plate" class="form-label">Standard:</label><select id="filter-standard-plate" name="standard" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Standard..."><option value=""></option></select></div>
                                        <div class="col-md-6 col-lg-3"><label for="filter-uom-plate" class="form-label">UOM:</label><select id="filter-uom-plate" name="uom" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="UOM..."><option value="Kg">Kg</option></select></div>
                                    </div>
                                </div>
                                <div class="type-specific-filter-group" data-filter-group-for="Screw Nut Bolt">
                                    <div class="col-12"><h6 class="filter-group-title border-bottom pb-1 mb-2">Fastener Attributes</h6></div>
                                    <div class="row g-3">
                                        <div class="col-md-4 col-lg-2"><label for="filter-diameter-snb" class="form-label">Diameter:</label><select id="filter-diameter-snb" name="diameter" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Diameter..."><option value=""></option></select></div>
                                        <div class="col-md-4 col-lg-2"><label for="filter-length-snb" class="form-label">Length:</label><select id="filter-length-snb" name="length" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Length..."><option value=""></option></select></div>
                                        <div class="col-md-4 col-lg-2"><label for="filter-class-snb" class="form-label">Class:</label><select id="filter-class-snb" name="class" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Class..."><option value=""></option></select></div>
                                        <div class="col-md-6 col-lg-3 mt-md-2 mt-lg-2"><label for="filter-standard-snb" class="form-label">Standard:</label><select id="filter-standard-snb" name="standard" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="Standard..."><option value=""></option></select></div>
                                        <div class="col-md-6 col-lg-3 mt-md-2 mt-lg-2"><label for="filter-uom-snb" class="form-label">UOM:</label><select id="filter-uom-snb" name="uom" class="form-select form-select-sm select2-dropdown rounded-3" data-placeholder="UOM..."><option value="Pcs">Pcs</option></select></div>
                                    </div>
                                </div>
                            </div> 
                            <div class="row g-3 mt-3 align-items-center">
                                <div class="col-md-auto" id="lengthMenuContainerBifurcation"></div>
                                <div class="col-md text-md-end mt-2 mt-md-0">
                                    <button type="button" id="clear-filters-bifurcation-button" class="btn btn-outline-secondary btn-sm rounded-pill">
                                        Clear All Filters
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>

                    <div class="table-responsive-custom mt-4 shadow-sm">
                        <table id="bifurcationTable" class="table table-sm table-striped table-bordered table-hover dt-responsive nowrap" style="width:100%">
                            <thead id="bifurcation-table-head"></thead>
                            <tbody id="bifurcation-table-body"></tbody>
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

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        const columnConfigsGlobal = <?php echo $columnConfigsJS; ?>;
        const projectBasePath = '<?php echo $project_base_path; ?>'; 
        // If your script.js for bifurcation relies on a global ajaxUrl, define it here:
        // const ajaxUrl = projectBasePath + "bifurcation_ajax_endpoint.php"; 
    </script>
    <script src="<?php echo $project_base_path; ?>script.js?v=<?php echo time(); ?>"></script>

</body>
</html>