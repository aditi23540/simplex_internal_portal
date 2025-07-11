<?php
session_set_cookie_params(['path' => '/']);
session_start(); // Start the session at the very beginning

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
                    $avatar_path = "../../registration_project/" . $db_avatar_path;
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
    <title>LMS Dashboard - SIMPLEX INTERNAL PORTAL</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700", "Inter:400,500,600,700"] }, // Added Inter
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
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <!-- LMS Dashboard Specific CSS -->
    <!-- Font Awesome Icons (from LMS) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- FullCalendar CSS (from LMS) -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />

    <!-- Custom CSS for dashboard content -->
    <style>
        /* Template Specific Styles */
        .page-inner .table thead {
            background-color: #007bff; /* Simplex Blue for table header */
            color: white;
        }
        .page-inner .table tbody tr:hover {
            background-color: #e9ecef;
        }
        .page-inner a {
            color: #0056b3;
            text-decoration: none;
        }
        .page-inner a:hover {
            text-decoration: underline;
            color: #003366;
        }
        .table-responsive {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .avatar-sm img, .avatar-lg img {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }
        .user-box .u-text p.text-muted {
            margin-bottom: 0.25rem;
        }

        /* LMS Dashboard Specific Styles */
        body {
            font-family: 'Inter', sans-serif;
        }
        .dashboard-section {
            padding: 2.5rem 0;
        }
        .section-title {
            margin-bottom: 2rem;
            font-weight: 700;
            color: #343a40;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }
        .card-tile {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .card-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        .card-tile .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 2rem 1.5rem;
            flex-grow: 1;
        }
        .card-tile .icon {
            font-size: 2.75rem;
            margin-bottom: 1rem;
            line-height: 1;
        }
        .card-tile .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: auto;
            padding-bottom: 1rem;
        }
        .card-tile .btn {
            border-radius: 50rem;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            margin-top: 1rem;
        }
        .progress-wrapper {
            width: 90%;
            text-align: left;
        }
        .progress {
            height: 10px;
            border-radius: 50rem;
        }
        #user-dashboard .section-title { color: #0d6efd; }
        #user-dashboard .icon { color: #0d6efd; }
        #learning-center .section-title { color: #198754; }
        #learning-center .icon { color: #198754; }
        #trainers-panel .section-title { color: #ffc107; }
        #trainers-panel .icon { color: #d9a406; }
        #hr-panel .section-title { color: #dc3545; }
        #hr-panel .icon { color: #dc3545; }
        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }
        .modal-footer {
            border-top: none;
            padding: 0.5rem 1.5rem 1.5rem;
        }
        #scheduleTrainingModal .modal-body {
            background-color: #fdfdff;
        }
        .fc .fc-button-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(13, 110, 253, 0.1);
        }
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
                    <img src="assets/img/kaiadmin/simplex_icon_2.png" alt="navbar brand" class="navbar-brand" height="50" />
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
                        <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
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
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <div class="navbar-brand-wrapper d-flex align-items-center me-auto">
                        <a href="dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: #333;">
                            <img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 60px; margin-right: 10px;" />
                            <span style="font-size: 1.8rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 100%;">
                                Simplex Engineering and Foundry Works
                            </span>
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

        <!-- =========== LMS DASHBOARD CONTENT START =========== -->
        <div class="container">
            <div class="page-inner">
                <!-- 🟦 1. YOUR DASHBOARD (FOR ALL USERS) -->
                <section id="user-dashboard" class="dashboard-section">
                    <h3 class="section-title"><i class="fas fa-user-circle"></i>Your Dashboard</h3>
                    <div class="row g-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-hand-sparkles icon"></i>
                                    <h5 class="card-title">Welcome, <?php echo $username_for_display; ?></h5>
                                    <a href="#" class="btn btn-primary">View Profile</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-award icon"></i>
                                    <h5 class="card-title">Your Training Status</h5>
                                    <p class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2">Up to Date</p>
                                    <a href="#" class="btn btn-outline-primary mt-2">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-calendar-check icon"></i>
                                    <h5 class="card-title">Next Scheduled Exam</h5>
                                    <p class="text-muted">Compliance Test: <strong>July 15, 2025</strong></p>
                                    <a href="#" class="btn btn-outline-primary mt-2">Go to Exam</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-chart-pie icon"></i>
                                    <h5 class="card-title">Training Completion</h5>
                                    <div class="progress-wrapper w-100">
                                        <div class="d-flex justify-content-between mb-1"><small>Progress</small><small>75%</small></div>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <a href="#" class="btn btn-outline-primary">View Progress</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 🟩 2. LEARNING CENTER (FOR TRAINEES) -->
                <section id="learning-center" class="dashboard-section">
                    <h3 class="section-title"><i class="fas fa-book-reader"></i>Learning Center</h3>
                    <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5">
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-paper-plane icon"></i>
                                    <h5 class="card-title">Raise Request for Training</h5>
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestTrainingModal">Raise Request</button>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-book-open icon"></i>
                                    <h5 class="card-title">Your Training Manuals</h5>
                                    <a href="#" class="btn btn-success">View Manuals</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-comment-dots icon"></i>
                                    <h5 class="card-title">Submit Feedback on Training</h5>
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#feedbackModal">Submit</button>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-pencil-alt icon"></i>
                                    <h5 class="card-title">Your Active Allotted Exams</h5>
                                    <a href="#" class="btn btn-success">View Exams</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-tasks icon"></i>
                                    <h5 class="card-title">Compulsory Trainings</h5>
                                    <a href="#" class="btn btn-success">View Assigned</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 🟨 3. TRAINER'S PANEL -->
                <section id="trainers-panel" class="dashboard-section">
                    <h3 class="section-title"><i class="fas fa-chalkboard-teacher"></i>Trainer's Panel</h3>
                    <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5">
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-users icon"></i>
                                    <h5 class="card-title">Your Assigned Trainees</h5>
                                    <a href="#" class="btn btn-warning text-dark">Manage</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-person-chalkboard icon"></i>
                                    <h5 class="card-title">Conduct or Schedule Sessions</h5>
                                    <a href="#" class="btn btn-warning text-dark">Schedule</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-upload icon"></i>
                                    <h5 class="card-title">Upload Session Materials</h5>
                                    <button class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#uploadModal">Upload</button>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-chart-line icon"></i>
                                    <h5 class="card-title">Review Trainee Progress</h5>
                                    <a href="#" class="btn btn-warning text-dark">Review</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-calendar-alt icon"></i>
                                    <h5 class="card-title">Set Your Availability Calendar</h5>
                                    <a href="#" class="btn btn-warning text-dark">Set Calendar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 🟥 4. HR CONTROL PANEL -->
                <section id="hr-panel" class="dashboard-section">
                    <h3 class="section-title"><i class="fas fa-cogs"></i>HR Control Panel</h3>
                    <div class="row g-4">
                        <div class="col-xl col-md-4 col-sm-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-user-plus icon"></i>
                                    <h5 class="card-title">Assign Trainings</h5>
                                    <a href="#" class="btn btn-danger">Assign</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-md-4 col-sm-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-file-signature icon"></i>
                                    <h5 class="card-title">Create Exams</h5>
                                    <a href="#" class="btn btn-danger">Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-md-4 col-sm-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-cloud-upload-alt icon"></i>
                                    <h5 class="card-title">Upload Materials</h5>
                                    <a href="#" class="btn btn-danger">Upload</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-md-4 col-sm-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-inbox icon"></i>
                                    <h5 class="card-title">Manage Requests</h5>
                                    <a href="#" class="btn btn-danger">Manage</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-md-4 col-sm-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-clipboard-list icon"></i>
                                    <h5 class="card-title">View All Reports</h5>
                                    <a href="#" class="btn btn-danger">View Reports</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-md-4 col-sm-6">
                            <div class="card card-tile">
                                <div class="card-body">
                                    <i class="fas fa-calendar-day icon"></i>
                                    <h5 class="card-title">Schedule Trainings</h5>
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#scheduleTrainingModal">Open Calendar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <!-- =========== LMS DASHBOARD CONTENT END =========== -->

        <footer class="footer">
            <div class="container-fluid d-flex justify-content-between">
                <nav class="pull-left">
                    <ul class="nav">
                    </ul>
                </nav>
                <div class="copyright">
                    <?php echo date('Y') ?>, made with <i class="fa fa-heart heart text-danger"></i> by
                    <a href="#">Abhimanyu</a>
                </div>
                <div>
                    For
                    <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering & Foundry Works PVT. LTD.</a>.
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- ========= MODALS ========= -->
<div class="modal fade" id="requestTrainingModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Raise a Training Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <form>
          <div class="mb-3">
            <label for="training-topic" class="form-label">Training Topic</label>
            <input type="text" class="form-control" id="training-topic" placeholder="e.g., Advanced Project Management">
          </div>
          <div class="mb-3">
            <label for="training-reason" class="form-label">Reason for Request</label>
            <textarea class="form-control" id="training-reason" rows="3" placeholder="Explain why you need this training..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Submit Request</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="feedbackModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Submit Feedback</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <form>
          <div class="mb-3">
            <label for="feedback-training" class="form-label">Select Training</label>
            <select class="form-select" id="feedback-training">
                <option selected>Choose a completed training...</option>
                <option value="1">Introduction to Git & GitHub</option>
                <option value="2">Corporate Communication</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="feedback-comments" class="form-label">Comments</label>
            <textarea class="form-control" id="feedback-comments" rows="3" placeholder="What did you like? What could be improved?"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Submit Feedback</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upload Materials</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <form>
          <div class="mb-3">
            <label for="upload-file" class="form-label">Select File (PDF, DOCX, PPT)</label>
            <input class="form-control" type="file" id="upload-file">
          </div>
           <div class="mb-3">
            <label for="upload-description" class="form-label">Description</label>
            <textarea class="form-control" id="upload-description" rows="2" placeholder="Briefly describe the material..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Upload</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="scheduleTrainingModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Training Based on Trainer Availability</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id='calendar'></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save Schedule</button>
            </div>
        </div>
    </div>
</div>

<!-- Core JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<!-- FullCalendar JS (from LMS) -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var scheduleModalEl = document.getElementById('scheduleTrainingModal');
        var calendar;

        // Initialize FullCalendar when the modal is shown
        scheduleModalEl.addEventListener('shown.bs.modal', function () {
            if (!calendar) { // Initialize calendar only once
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    editable: true,
                    selectable: true,
                    events: [
                        {
                            title: 'Trainer A - Available',
                            start: '2025-07-10T10:00:00',
                            end: '2025-07-10T12:00:00',
                            backgroundColor: '#198754',
                            borderColor: '#198754'
                        },
                        {
                            title: 'Project Management Training',
                            start: '2025-07-14',
                            end: '2025-07-16',
                            backgroundColor: '#0d6efd',
                            borderColor: '#0d6efd'
                        }
                    ],
                    select: function(info) {
                        var title = prompt('Enter New Training Title:');
                        if (title) {
                            calendar.addEvent({
                                title: title,
                                start: info.startStr,
                                end: info.endStr,
                                allDay: info.allDay,
                                backgroundColor: '#dc3545',
                                borderColor: '#dc3545'
                            });
                        }
                        calendar.unselect();
                    },
                    eventClick: function(info) {
                        alert('Event: ' + info.event.title);
                    }
                });
                calendar.render();
            } else {
                calendar.render(); // Re-render if modal is opened again
            }
        });
    });
</script>

</body>
</html>
