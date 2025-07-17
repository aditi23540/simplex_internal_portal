<?php
session_set_cookie_params(['path' => '/']);
//session_start(); // Start the session at the very beginning
require_once 'config.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Assuming your login page is login.php
    exit;
}

// Retrieve user info from session
$original_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; // Get raw username

// Prepare username for display (replace dot with space, capitalize words)
$username_for_display = str_replace('.', ' ', $original_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display))); 

$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 

// Use original_username (with dot, but still htmlspecialchars for safety) for email placeholder
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in'; 

// Retrieve department and role from session
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';


// --- Database Configuration ---
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "user_master_db"; 

// Default avatar path
$avatar_path = "assets/img/kaiadmin/default-avatar.png"; 

if ($empcode !== 'N/A') {
    // Create a database connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        // error_log("Database connection failed: " . $conn->connect_error);
    } else {
        // Prepare SQL statement
        $sql = "SELECT users.profile_picture_path
                FROM users
                JOIN user_hr_details ON users.user_id = user_hr_details.user_id
                WHERE user_hr_details.employee_id_ascent = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $empcode); 
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $db_avatar_path = $row['profile_picture_path']; 
                
                if (!empty($db_avatar_path)) {
                    $avatar_path = "../registration_project/".$db_avatar_path;
                }
            }
            $stmt->close();
        } else {
            // error_log("Failed to prepare SQL statement: " . $conn->error);
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>PO Issues Dashboard - SIMPLEX INTERNAL PORTAL</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/kaiadmin/simplex_icon.ico"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <!-- Custom CSS for this page -->
    <style>
      .page-header-title {
        font-weight: 700;
        color: #333;
        border-bottom: 3px solid #007bff;
        padding-bottom: 10px;
        margin-bottom: 25px;
      }
      .card-stats .card-body { display: flex; flex-direction: column; justify-content: center; min-height: 160px; }
      .card-stats .icon-big { font-size: 2.8em; }
      .card-stats .card-title { font-size: 1.4rem; font-weight: 600; margin-bottom: 0.5rem; }
      .card-stats .card-category{ font-weight: 500; font-size: 0.9rem; margin-bottom: 1rem; color: #6c757d; }
      .card-stats .count-box { display: flex; justify-content: space-around; width: 100%; }
      .card-stats .count-item { text-align: center; }
      .card-stats a.stretched-link:after { content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 1; }
      .card.card-stats { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; position: relative; }
      .card.card-stats:hover { transform: translateY(-5px); box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.1); }
      .tile-label { position: absolute; top: 10px; right: 15px; font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 50rem; z-index: 2; background-color: rgba(255, 255, 255, 0.85); }
      .summary-card { border-left: 4px solid #17a2b8; }
      @keyframes blinker { 50% { opacity: 0.4; } }
      .blink-badge { animation: blinker 1.5s linear infinite; }
    </style>
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
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
            <h1 class="page-header-title mt-4">PO Issues Dashboard</h1>
            
            <!-- First Row of Tiles -->
            <div class="row">
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="card card-stats card-primary card-round">
                        <a href="add_po_issue.php" class="stretched-link" title="Click to raise a new issue"></a>
                        <div class="card-body text-center">
                           <div class="icon-big text-center"><i class="fas fa-plus-circle"></i></div>
                           <h4 class="card-title mt-3">Raise a New PO Issue</h4>
                           <p class="card-category">Report a new problem</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="card card-stats card-info card-round">
                        <a href="track_your_issues.php" class="stretched-link" title="Click to track your submitted issues"></a>
                        <div class="card-body text-center">
                           <div class="icon-big text-center"><i class="fas fa-user-edit"></i></div>
                           <h4 class="card-title mt-3">Track Your Issues</h4>
                           <div class="count-box">
                               <div class="count-item"><span id="count-new-by-you" class="badge bg-danger">0</span><div>New</div></div>
                               <div class="count-item"><span id="count-created-by-you" class="badge bg-secondary">0</span><div>All</div></div>
                           </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Open Issues Summary Section -->
            <div class="mb-4">
              <h5>Open Issues by Type</h5>
              <div id="open-issues-summary" class="row g-2"></div>
            </div>
            
            <!-- Second Row of Tiles -->
            <div class="row">
                 <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-stats card-warning card-round">
                        <span class="tile-label text-warning fw-bold">for all users</span>
                        <a href="unsolved_issues.php" class="stretched-link" title="Click to view all unsolved issues"></a>
                        <div class="card-body text-center">
                            <div class="icon-big text-center"><i class="fas fa-exclamation-triangle"></i></div>
                           <h4 class="card-title mt-3">Unsolved Issues</h4>
                            <div class="count-box">
                               <div class="count-item"><span id="count-new-unsolved" class="badge bg-danger">0</span><div>New</div></div>
                               <div class="count-item"><span id="count-unsolved" class="badge bg-secondary">0</span><div>All</div></div>
                           </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-stats card-success card-round">
                        <span class="tile-label text-success fw-bold">for all users</span>
                        <a href="closed_issues.php" class="stretched-link" title="Click to view all closed issues"></a>
                        <div class="card-body text-center">
                             <div class="icon-big text-center"><i class="fas fa-archive"></i></div>
                            <h4 class="card-title mt-3">Closed Issues</h4>
                            <h3 class="fw-bold" id="count-closed-only">0</h3>
                            <p class="card-category">Total Confirmed & Closed</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 mb-4">
                    <div class="card card-stats card-secondary card-round">
                         <span class="tile-label text-secondary fw-bold">for all users</span>
                        <a href="all_issues.php" class="stretched-link" title="Click to view all issues"></a>
                        <div class="card-body text-center">
                            <div class="icon-big text-center"><i class="fas fa-server"></i></div>
                            <h4 class="card-title mt-3">All Issues</h4>
                            <div class="count-box">
                               <div class="count-item"><span id="count-new-all" class="badge bg-danger">0</span><div>New</div></div>
                               <div class="count-item"><span id="count-all-issues" class="badge bg-secondary">0</span><div>All</div></div>
                           </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
        <footer class="footer"><!-- Footer content --></footer>
      </div>
    </div>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script>
    $(document).ready(function() {
        function updateDashboardCounts() {
            $.ajax({
                url: 'fetch_dashboard_counts.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#count-new-by-you').text(response.new_by_you);
                    $('#count-created-by-you').text(response.created_by_you);
                    $('#count-new-unsolved').text(response.new_unsolved);
                    $('#count-unsolved').text(response.unsolved);
                    $('#count-closed-only').text(response.closed_only);
                    $('#count-new-all').text(response.new_all);
                    $('#count-all-issues').text(response.all_issues);
                    
                    const summaryContainer = $('#open-issues-summary').empty();
                    if (Object.keys(response.open_by_type).length > 0) {
                        for (const type in response.open_by_type) {
                            const count = response.open_by_type[type];
                            const summaryHtml = `<div class="col-lg-2 col-md-4 col-6 mb-2">
                                    <div class="card card-body text-center p-2 summary-card">
                                        <p class="card-category mb-0">${type}</p>
                                        <h5 class="fw-bold text-danger mb-0">${count}</h5>
                                    </div>
                                </div>`;
                            summaryContainer.append(summaryHtml);
                        }
                    } else {
                        summaryContainer.append('<div class="col-12"><p class="text-muted text-center">No open issues at the moment.</p></div>');
                    }
                    
                    if (response.new_by_you > 0) $('#count-new-by-you').addClass('blink-badge'); else $('#count-new-by-you').removeClass('blink-badge');
                    if (response.new_unsolved > 0) $('#count-new-unsolved').addClass('blink-badge'); else $('#count-new-unsolved').removeClass('blink-badge');
                    if (response.new_all > 0) $('#count-new-all').addClass('blink-badge'); else $('#count-new-all').removeClass('blink-badge');
                },
                error: function() { console.error("Failed to fetch dashboard counts."); }
            });
        }
        updateDashboardCounts();
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updateDashboardCounts();
            }
        });
    });
    </script>
  </body>
</html>