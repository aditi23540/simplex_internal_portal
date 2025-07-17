<?php
// Main Dashboard File (e.g., index.php)

// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit;
}

// --- Main template user info logic ---
$original_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$username_for_display = str_replace('.', ' ', $original_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display))); 
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in'; 
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';
$avatar_path = "assets/img/kaiadmin/default-avatar.png"; 

// --- Database Configuration for Avatar ---
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

// --- Page Router ---
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard'; 
$page_specific_title = "Dashboard"; // Default title

// You can add more pages to the router here in the future
if ($page == 'view_users') { 
    $page_specific_title = "Employee Master"; 
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
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    
    <?php if ($page == 'dashboard'): ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="assets/style.css"> <?php endif; ?>
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
                                            <div class="dropdown-divider"></div><a class="dropdown-item" href="#">Account Setting</a>
                                            <div class="dropdown-divider"></div><a class="dropdown-item" href="logout.php">Logout</a> 
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
                    <?php if ($page == 'dashboard'): ?>
                        <div id="loader" class="text-center p-5">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading Dashboard Data...</p>
                        </div>

                        <div id="dashboard-content" class="d-none">
                            <h1 class="h2 mb-4">HR Analytics Dashboard</h1>
                            
                            <section class="mb-4">
                                <div class="row">
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm p-3 kpi-card" style="border-color: #198754;"><h6>Active Employees</h6><span id="kpi-active-employees" class="display-4">0</span></div></div>
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm p-3 kpi-card" style="border-color: #ffc107;"><h6>Inactive (ID not Assigned)</h6><span id="kpi-inactive-employees" class="display-4">0</span></div></div>
                                </div>
                            </section>

                            <section id="management" class="mb-5">
                                <h2 class="h4">Management Overview</h2>
                                <hr class="mt-1 mb-4">
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <div class="card shadow-sm">
                                            <div class="card-header card-header-chart">Employees per Department Head</div>
                                            <div class="card-body border-bottom">
                                                <div class="row align-items-end">
                                                    <div class="col-md-9">
                                                        <label for="dept-head-filter" class="form-label fw-bold">Select Department Heads</label>
                                                        <select class="form-select" id="dept-head-filter" multiple="multiple"></select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="btn-group w-100" style="margin-top: 2rem;">
                                                            <button class="btn btn-outline-secondary btn-sm" id="show-top15-head-btn">Top 15</button>
                                                            <button class="btn btn-outline-secondary btn-sm" id="show-all-head-btn">Show All</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body chart-container" id="dept-head-breakdown-chart"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-4">
                                       <div class="card shadow-sm">
                                            <div class="card-header card-header-chart">Department Head Details</div>
                                            <div class="card-body accordion-scroll-container" id="dept-head-accordion-container">
                                                <div class="accordion" id="dept-head-accordion"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            
                            <section id="overview" class="mb-4">
                                <h2 class="h4">Workforce Breakdown</h2>
                                <hr class="mt-1 mb-4">
                                <div class="row">
                                    <div class="col-lg-12 mb-4">
                                        <div class="card shadow-sm">
                                            <div class="card-header card-header-chart">Departments by Headcount</div>
                                            <div class="card-body border-bottom">
                                                <div class="row align-items-end">
                                                    <div class="col-md-9"><label for="dept-filter" class="form-label fw-bold">Select Departments</label><select class="form-select" id="dept-filter" multiple="multiple"></select></div>
                                                    <div class="col-md-3"><div class="btn-group w-100" style="margin-top: 2rem;"><button class="btn btn-outline-secondary btn-sm" id="show-top15-dept-btn">Top 15</button><button class="btn btn-outline-secondary btn-sm" id="show-all-dept-btn">Show All</button></div></div>
                                                </div>
                                            </div>
                                            <div class="card-body chart-container" id="dept-breakdown-chart"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm"><div class="card-header card-header-chart">Unit Wise Headcount (Top 15)</div><div class="card-body chart-container" id="unit-breakdown-chart"></div></div></div>
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm"><div class="card-header card-header-chart">Designation Wise Headcount</div><div class="card-body border-bottom"><div class="row align-items-end"><div class="col-md-8"><label for="desg-filter" class="form-label fw-bold">Select Designations</label><select class="form-select" id="desg-filter" multiple="multiple"></select></div><div class="col-md-4"><div class="btn-group w-100" style="margin-top: 2rem;"><button class="btn btn-outline-secondary btn-sm" id="show-top15-desg-btn">Top 15</button><button class="btn btn-outline-secondary btn-sm" id="show-all-desg-btn">Show All</button></div></div></div></div><div class="card-body chart-container" id="designation-breakdown-chart"></div></div></div>
                                </div>
                            </section>

                            <section id="hiring-diversity" class="mb-5">
                                <h2 class="h4">Hiring & Diversity Analysis</h2>
                                <hr class="mt-1 mb-4">
                                <div class="row">
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm h-100"><div class="card-header card-header-chart">Headcount Trend (Joiners by Year)</div><div class="card-body chart-container" id="headcount-trend-chart"></div></div></div>
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm h-100"><div class="card-header card-header-chart">Hiring by Decade</div><div class="card-body chart-container" id="decade-chart"></div></div></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm h-100"><div class="card-header card-header-chart">Gender Distribution</div><div class="card-body chart-container" id="gender-chart"></div><div class="card-footer donut-legend" id="gender-chart-legend"></div></div></div>
                                    <div class="col-lg-6 mb-4"><div class="card shadow-sm h-100"><div class="card-header card-header-chart">Type of Employees (by Policy)</div><div class="card-body chart-container" id="attendance-policy-chart"></div><div class="card-footer donut-legend" id="attendance-policy-chart-legend"></div></div></div>
                                </div>
                            </section>
                        </div>
                        <?php elseif ($page == 'view_users'): ?>
                        <p>Employee Master page is loading...</p>

                    <?php else: ?>
                        <div class="text-center p-5">Page not found.</div>
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

    <?php if ($page == 'dashboard'): ?>
        <script src="https://d3js.org/d3.v7.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="assets/js/dashboard_charts.js"></script>
    <?php endif; ?>
    
</body>
</html>
<?php 
if (isset($link) && $link instanceof mysqli) {
    $link->close(); 
}
?>