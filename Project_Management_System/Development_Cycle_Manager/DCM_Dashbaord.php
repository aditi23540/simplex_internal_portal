<?php
session_set_cookie_params(['path' => '/']);
session_start(); // Start the session at the very beginning

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Assuming your login page is login.php
    exit;
}

// =================== NEW: ADMIN ACCESS CHECK ===================
// After checking login, verify the user role is 'ADMIN'.
// This check is case-insensitive (handles 'ADMIN', 'Admin', or 'admin').
if (!isset($_SESSION['employee_role']) || strtolower($_SESSION['employee_role']) !== 'admin') {
    // If the role is not 'admin', deny access by redirecting.
    header("Location: login.php");
    exit;
}
// =================== END OF ADMIN ACCESS CHECK ===================

// Retrieve user info from session
$original_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; // Get raw username

// Prepare username for display (replace dot with space, capitalize words)
$username_for_display = str_replace('.', ' ', $original_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display))); 

// Corrected: Apply htmlspecialchars directly to $_SESSION values
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 

// Use original_username (with dot, but still htmlspecialchars for safety) for email placeholder
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in'; 

// Corrected: Apply htmlspecialchars directly to $_SESSION values
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
                    // Corrected path: Go up two directories from current file to reach 'ADMIN', then into 'registration_project'
                    $avatar_path = "../../registration_project/".$db_avatar_path; 
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
    <title>SIMPLEX INTERNAL PORTAL - Development Cycle Manager</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="../../../LIBRARY/assets/img/kaiadmin/simplex_icon.ico"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="../../../LIBRARY/assets/js/plugin/webfont/webfont.min.js"></script>
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
          urls: ["../../../LIBRARY/assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../../../LIBRARY/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../../LIBRARY/assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../../../LIBRARY/assets/css/kaiadmin.min.css" />
    <!-- Bootstrap Icons (for icons in Development Cycle Manager) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts for new design -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">


    <!-- Custom CSS for dashboard content & Development Cycle Manager -->
    <style>
      /* General KaiAdmin overrides/additions */
      .page-inner .table th, .page-inner .table td { /* Added to ensure table headers and cells have proper padding */
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
      }
      .page-inner .table thead {
        background-color: #007bff; /* Simplex Blue for table header */
        color: white;
      }
      .page-inner .table tbody tr:hover {
        background-color: #e9ecef;
      }
      .page-inner .page-header-title {
        text-align: center;
        margin-bottom: 30px;
        font-weight: bold;
        color: #003366; /* Darker Simplex Blue */
        padding-bottom: 10px;
        border-bottom: 2px solid #007bff;
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
        /* Ensure avatar images in dropdown are visible */
        .avatar-sm img, .avatar-lg img {
            object-fit: cover; /* Ensures images cover the area, might crop */
            width: 100%; /* Ensure image takes full width of avatar container */
            height: 100%; /* Ensure image takes full height of avatar container */
        }
        .welcome-message {
            text-align: center;
            margin-top: 50px;
            font-size: 1.5rem;
            color: #555;
        }
        .user-box .u-text p.text-muted {
            margin-bottom: 0.25rem; /* Adjust spacing for new lines */
        }

        /* --- NEW Development Cycle Manager Custom Styles (from provided HTML) --- */
        /* Reset for the new content */
        .page-inner * {
            box-sizing: border-box;
        }
        .page-inner {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* New gradient background */
            min-height: 100vh;
            overflow-x: hidden;
            padding: 0 !important; /* Remove default page-inner padding */
        }
        /* The main .container in the PHP template already exists,
           so we'll apply the max-width/margin to the timeline-wrapper directly */
        .page-inner > .container { /* Targeting the new container within page-inner */
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95), rgba(118, 75, 162, 0.95));
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            margin-bottom: 60px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            pointer-events: none;
        }
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideInDown 1s ease-out;
        }
        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 30px;
            animation: slideInUp 1s ease-out 0.3s both;
        }
        @keyframes slideInDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideInUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        /* Timeline Styles */
        .timeline-container {
            position: relative;
            max-width: 1000px; /* Adjusted max-width for inner content */
            margin: 0 auto;
        }
        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
            transform: translateX(-50%);
            border-radius: 2px;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 80px;
            position: relative;
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }
        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }
        .timeline-item:nth-child(1) { animation-delay: 0.1s; }
        .timeline-item:nth-child(2) { animation-delay: 0.2s; }
        .timeline-item:nth-child(3) { animation-delay: 0.3s; }
        .timeline-item:nth-child(4) { animation-delay: 0.4s; }
        .timeline-item:nth-child(5) { animation-delay: 0.5s; }
        @keyframes fadeInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .timeline-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 10;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        .timeline-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }
        .timeline-icon i {
            font-size: 2rem;
            color: white;
        }
        .timeline-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin: 0 40px;
            flex: 1;
            max-width: 450px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .timeline-content:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        .timeline-content::before {
            content: '';
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            background: white;
            transform: translateY(-50%) rotate(45deg);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .timeline-item:nth-child(odd) .timeline-content::before {
            right: -10px;
        }
        .timeline-item:nth-child(even) .timeline-content::before {
            left: -10px;
        }
        .phase-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .phase-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .phase-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .phase-features {
            list-style: none;
            padding: 0;
        }
        .phase-features li {
            padding: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .phase-features li::before {
            content: '';
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            flex-shrink: 0;
        }
        /* New styles for buttons within timeline content */
        .timeline-actions {
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            gap: 10px; /* Space between buttons */
            margin-top: 20px;
            margin-bottom: 10px; /* Space before next section */
            justify-content: flex-start; /* Default for left-aligned content */
        }
        .timeline-item:nth-child(odd) .timeline-actions {
            justify-content: flex-end; /* Align buttons to the right for odd items */
        }
        .timeline-action-button {
            background: linear-gradient(135deg, #e0f7fa, #b2ebf2); /* Lighter gradient */
            color: #2c3e50; /* Dark text */
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none; /* For anchor tags styled as buttons */
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
        .timeline-action-button:hover {
            background: linear-gradient(135deg, #b2ebf2, #e0f7fa); /* Inverted lighter gradient on hover */
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2); /* More pronounced shadow on hover */
            color: #2c3e50; /* Ensure text color remains dark on hover */
        }
        .timeline-action-button i {
            color: #00796b; /* Dark teal icon color */
        }


        /* Expandable System Design Section */
        .expandable-section {
            background: linear-gradient(135deg, #f8f9ff, #e8f2ff);
            border-radius: 15px;
            padding: 30px;
            margin-top: 25px;
            border-left: 4px solid #667eea;
        }
        .expand-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            /* Centering the button within its parent flex container */
            margin-left: auto;
            margin-right: auto;
        }
        .expand-button:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .expand-button i {
            transition: transform 0.3s ease;
        }
        .expand-button[aria-expanded="true"] i {
            transform: rotate(180deg);
        }
        .design-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Wider tiles */
            gap: 20px;
            margin-top: 20px;
        }
        .design-tile {
            background: white;
            padding: 20px; /* Shorter padding for shorter height */
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: flex; /* Use flexbox for internal alignment */
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 140px; /* Shorter tiles */
        }
        .design-tile:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .design-tile i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .design-tile:hover i {
            transform: scale(1.1);
        }
        .design-tile h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .design-tile p {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        /* Removed .design-tile-actions and .design-tile-button styles as buttons are removed */

        /* Progress Indicator */
        .progress-indicator {
            position: fixed;
            top: 50%;
            right: 30px;
            transform: translateY(-50%);
            z-index: 1000;
        }
        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .progress-dot.active {
            background: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.6);
        }
        .progress-dot::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid #667eea;
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.3s ease;
        }
        .progress-dot.active::after {
            transform: translate(-50%, -50%) scale(1);
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .timeline-line {
                left: 30px;
            }
            .timeline-item {
                flex-direction: column;
                align-items: flex-start;
                padding-left: 80px;
            }
            .timeline-item:nth-child(even) {
                flex-direction: column;
                align-items: flex-start;
            }
            .timeline-icon {
                position: absolute;
                left: 0;
                top: 0;
                width: 60px;
                height: 60px;
            }
            .timeline-content {
                margin: 0;
                max-width: 100%;
            }
            .timeline-content::before {
                display: none;
            }
            .progress-indicator {
                display: none;
            }
            .design-tiles {
                grid-template-columns: 1fr;
            }
            .timeline-actions {
                justify-content: center; /* Center buttons on mobile */
            }
            .timeline-item:nth-child(odd) .timeline-actions {
                justify-content: center; /* Override right align for mobile */
            }
        }
        /* Floating particles animation */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s infinite ease-in-out;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 1; }
            50% { transform: translateY(-100px) rotate(180deg); opacity: 0.5; }
        }
        /* Scroll animations */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }
        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
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
              <img
                src="../../../LIBRARY/assets/img/kaiadmin/simplex_icon_2.png"
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
                        <i class="fas fa-user-tie"></i> 
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
                  src="../../../LIBRARY/assets/img/kaiadmin/logo_light.svg" 
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
                  <img src="../../../LIBRARY/assets/img/kaiadmin/simplex_icon.ico" 
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
                        onerror="this.onerror=null; this.src='../../../LIBRARY/assets/img/kaiadmin/default-avatar.png';" 
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
                              onerror="this.onerror=null; this.src='../../../LIBRARY/assets/img/kaiadmin/default-avatar.png';"
                              class="avatar-img rounded"
                            />
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

        <div class="container">
          <div class="page-inner">
            <!-- START: Development Cycle Manager Content -->
            <div class="particles"></div>
            <div class="container">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1 class="hero-title">Development Cycle Manager</h1>
                        <p class="hero-subtitle">Streamlining your software development journey from concept to deployment with precision and excellence</p>
                    </div>
                </div>
                <!-- Timeline Container -->
                <div class="timeline-container">
                    <div class="timeline-line"></div>
                    <!-- Phase 1: Requirement Gathering -->
                    <div class="timeline-item scroll-reveal">
                        <div class="timeline-icon">
                            <i class="bi bi-lightbulb-fill"></i>
                        </div>
                        <div class="timeline-content">
                            <h3 class="phase-title">
                                <span class="phase-number">1</span>
                                Requirement Gathering
                            </h3>
                            <p class="phase-description">
                               Click on the 'Requirement Gathering Form' button below to submit your software/module development request. 
                               If you are a gatherer, please fill out the form as well for record-keeping purposes.
                            </p>
                          
                            <div class="timeline-actions">
                                <a href="https://docs.google.com/forms/d/e/1FAIpQLSfBFoZAKIuO5YfwX-LbVODDhlRU2s8VB1pUwQMnp6NuNmOShA/viewform?usp=dialog" target ="_blank" class="timeline-action-button">Requirement Gathering Form <i class="bi bi-arrow-right"></i></a>
                                <a href="https://docs.google.com/forms/d/1qDnkjLhj0JFFvh8E-5S_bnfuP0YdYaa-c0BZx51vYcs/edit" target ="_blank" class="timeline-action-button">Requirement Gathering Form (Edit) <i class="bi bi-arrow-right"></i></a>
                                <a href="https://docs.google.com/spreadsheets/d/1IeEXruDcnTmrYG5xYR6VgxC98QHc0q6PhmCJt66hCHM/edit?usp=sharing" target ="_blank" class="timeline-action-button">Requirement Gathering Form (Table View) <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Phase 2: System Design -->
                    <div class="timeline-item scroll-reveal">
                        <div class="timeline-icon">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="timeline-content">
                            <h3 class="phase-title">
                                <span class="phase-number">2</span>
                                System Design
                            </h3>
                            <p class="phase-description">
                                Follow each step carefully, from planning to documentation, for effective system design.
                            </p>                                        
                            <div class="expandable-section">
                                <button class="expand-button" type="button" data-bs-toggle="collapse" data-bs-target="#designPhases" aria-expanded="false" aria-controls="designPhases">
                                    Explore Design Phases <i class="bi bi-chevron-down"></i>
                                </button>
                                        
                                <div class="collapse" id="designPhases">
                                    <div class="design-tiles">
                                        <a href="https://docs.google.com/spreadsheets/d/142HP1ymdlAUdY-NKwufEEZwuXz-KxnG0kmqrgkNaaP4/edit?usp=sharing" class="design-tile">
                                            <i class="bi bi-bezier" style="color: #667eea;"></i>
                                            <h4>System Planning</h4>
                                            <p>We are using Google Sheets to manage planning</p>
                                            
                                        <a href="Database_Design_Tracker/index.php" target="_blank" class="design-tile">
                                            <i class="bi bi-database" style="color: #764ba2;"></i>
                                            <h4>Database Designing</h4>
                                            <p>We are using DrawSQL app to design ER & Database.Click on Tile to manage Upload SQL Schema & Track history.</p>
                                        </a>
                                        <a href="UI_Layout_Manager/index.php" class="design-tile">
                                            <i class="bi bi-pencil-square" style="color: #667eea;"></i>
                                            <h4>Wireframe Designing</h4>
                                            <p>We are using Draw.IO for Wireframe Designing</p>
                                        </a>
                                        <a href="Navigation_Flow_Manager/index.php" class="design-tile">
                                            <i class="bi bi-stack-overflow" style="color: #764ba2;"></i>
                                            <h4>Navigation Flow Design</h4>
                                            <p>We are using Draw.IO for Navigation Flow Design</p>
                                        </a>
                                        <a href="https://docs.google.com/spreadsheets/d/1QsaDf9_GCzfQWhTNEgGPYviCasRoMgTyp2mrZlpv1Ls/edit?gid=0#gid=0" class="design-tile">
                                            <i class="bi bi-file-earmark-code-fill" style="color: #667eea;"></i>
                                            <h4>Functional Flow Design</h4>
                                            <p>We are using google Sheets to make functional flow</p>
                                        </a>
                                        <a href="https://docs.google.com/spreadsheets/d/1rxNe0trCjtUJ3KAg-8tJ-htDrqVNsz52WSQqTSfII90/edit?gid=0#gid=0" class="design-tile">
                                            <i class="bi bi-shield-lock" style="color: #764ba2;"></i>
                                            <h4>Role Based Access Control</h4>
                                            <p>We are using google Sheet to define Role based access control for specific model</p>
                                        </a>
                                        <a href="#" class="design-tile">
                                            <i class="bi bi-file-earmark-minus" style="color: #667eea;"></i>
                                            <h4>Software_Documentation_Manager</h4>
                                            <p>We are using napkin.ai & word for documention of project/module/software.</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                           
                        </div>
                    </div>
                    <!-- Phase 3: Development -->
                    <div class="timeline-item scroll-reveal">
                        <div class="timeline-icon">
                            <i class="bi bi-code-slash"></i>
                        </div>
                        <div class="timeline-content">
                            <h3 class="phase-title">
                                <span class="phase-number">3</span>
                                Development
                            </h3>
                            <p class="phase-description">
                                Where ideas come to life through clean, efficient code. Our development phase follows 
                                industry best practices with continuous integration, rigorous testing, and collaborative 
                                code reviews to ensure high-quality deliverables.
                            </p>
                      
                            <div class="timeline-actions">
                                <a href="#" class="timeline-action-button">See Code Samples <i class="bi bi-github"></i></a>
                                <a href="#" class="timeline-action-button">Developer Docs <i class="bi bi-book"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- Phase 4: Testing & Quality Assurance -->
                    <div class="timeline-item scroll-reveal">
                        <div class="timeline-icon">
                            <i class="bi bi-bug-fill"></i>
                        </div>
                        <div class="timeline-content">
                            <h3 class="phase-title">
                                <span class="phase-number">4</span>
                                Quality Assurance
                            </h3>
                            <p class="phase-description">
                                Comprehensive testing ensures your software is reliable, secure, and performs optimally 
                                under various conditions. Our multi-layered testing approach catches issues early and 
                                validates every aspect of functionality.
                            </p>
                       
                            <div class="timeline-actions">
                                <a href="https://docs.google.com/spreadsheets/d/1buMx8gMGnKK4GqXP1JbYCh9jGXPyIrrTUj-RBlyCxHY/edit?gid=0#gid=0" class="timeline-action-button">Test Reports<i class="bi bi-file-earmark-bar-graph"></i></a>
                                
                            </div>
                        </div>
                    </div>
                    <!-- Phase 5: Deployment & Maintenance -->
                    <div class="timeline-item scroll-reveal">
                        <div class="timeline-icon">
                            <i class="bi bi-cloud-upload"></i>
                        </div>
                        <div class="timeline-content">
                            <h3 class="phase-title">
                                <span class="phase-number">5</span>
                                SupportMaintenance
                            </h3>
                            <p class="phase-description">
                                Smooth deployment and ongoing support ensure your software continues to deliver value. 
                                Our maintenance approach includes proactive monitoring, regular updates, and rapid 
                                response to ensure optimal performance and user satisfaction.
                            </p>
                            <div class="timeline-actions">
                                <a href="#" class="timeline-action-button">Access Support <i class="bi bi-life-preserver"></i></a>
                                <a href="#" class="timeline-action-button">View Release Notes <i class="bi bi-journal-text"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-dot active" data-phase="1"></div>
                <div class="progress-dot" data-phase="2"></div>
                <div class="progress-dot" data-phase="3"></div>
                <div class="progress-dot" data-phase="4"></div>
                <div class="progress-dot" data-phase="5"></div>
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
    <!-- Corrected Script Loading Order -->
    <script src="../../../LIBRARY/assets/js/core/jquery-3.7.1.min.js"></script>
    <!-- Load Bootstrap 5 Bundle AFTER jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Other KaiAdmin scripts -->
    <script src="../../../LIBRARY/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../../../LIBRARY/assets/js/kaiadmin.min.js"></script>
    <!-- Custom timeline JS -->
    <script>
        // Floating particles animation
        function createParticles() {
            const particles = document.querySelector('.particles');
            if (!particles) return; // Ensure particles div exists
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particles.appendChild(particle);
            }
        }
        // Scroll reveal animation
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.scroll-reveal');
            reveals.forEach(reveal => {
                const windowHeight = window.innerHeight;
                const revealTop = reveal.getBoundingClientRect().top;
                const revealPoint = 150;
                if (revealTop < windowHeight - revealPoint) {
                    reveal.classList.add('revealed');
                }
            });
        }
        // Progress indicator update
        function updateProgressIndicator() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            const progressDots = document.querySelectorAll('.progress-dot');
            if (timelineItems.length === 0 || progressDots.length === 0) return; // Ensure elements exist
            timelineItems.forEach((item, index) => {
                const rect = item.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                if (rect.top < windowHeight / 2 && rect.bottom > windowHeight / 2) {
                    progressDots.forEach(dot => dot.classList.remove('active'));
                    progressDots[index].classList.add('active');
                }
            });
        }
        // Progress dot click handler
        document.querySelectorAll('.progress-dot').forEach(dot => {
            dot.addEventListener('click', () => {
                const phase = dot.dataset.phase;
                const targetItem = document.querySelector(`.timeline-item:nth-child(${phase})`);
                if (targetItem) {
                    targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });
        // Timeline content click animations
        document.querySelectorAll('.timeline-content').forEach(content => {
            content.addEventListener('click', function(e) {
                // Prevent click animation if the expand button or its child is clicked
                // This check is now less critical as the expand button is removed, but good practice
                if (e.target.classList.contains('expand-button') || e.target.closest('.expand-button')) {
                    return;
                }
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            });
        });
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            revealOnScroll();
            updateProgressIndicator();
        });
        // Event listeners
        window.addEventListener('scroll', () => {
            revealOnScroll();
            updateProgressIndicator();
        });
        // Smooth scrolling for design tiles
        document.querySelectorAll('.design-tile').forEach(tile => {
            tile.addEventListener('click', function(e) {
                // Since these are now direct links, preventDefault is less critical unless
                // you want to add custom JS behavior instead of navigation.
                // e.preventDefault(); 
                this.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            });
        });
        // Enhanced hover effects
        document.querySelectorAll('.timeline-icon').forEach(icon => {
            icon.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.15) rotate(5deg)';
            });
            icon.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotate(0deg)';
            });
        });
    </script>
  </body>
</html>
