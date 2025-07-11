<?php
// /it_setup_form.php
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

// --- Logic to fetch details for IT Setup ---
require_once 'includes/db_config.php';

$user_id = null; $user_name = "N/A"; $it_details = null; $hr_details_for_role = null; $form_mode = "new";

if (isset($_GET['user_id']) && filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $user_id = (int)$_GET['user_id'];
    $sql_user = "SELECT user_id, first_name, middle_name, surname FROM users WHERE user_id = ?";
    if ($stmt_user = mysqli_prepare($link, $sql_user)) { mysqli_stmt_bind_param($stmt_user, "i", $user_id); mysqli_stmt_execute($stmt_user); $result_user = mysqli_stmt_get_result($stmt_user); if ($user_row = mysqli_fetch_assoc($result_user)) { $user_name = trim($user_row['first_name'] . ' ' . ($user_row['middle_name'] ? $user_row['middle_name'] . ' ' : '') . $user_row['surname']); } else { $_SESSION['message'] = "User not found."; $_SESSION['message_type'] = "danger"; header("Location: view_users.php"); exit(); } mysqli_stmt_close($stmt_user); }
    $sql_it = "SELECT * FROM user_it_details WHERE user_id = ?";
    if ($stmt_it = mysqli_prepare($link, $sql_it)) { mysqli_stmt_bind_param($stmt_it, "i", $user_id); mysqli_stmt_execute($stmt_it); $result_it = mysqli_stmt_get_result($stmt_it); if ($it_row = mysqli_fetch_assoc($result_it)) { $it_details = $it_row; $form_mode = "edit"; } mysqli_stmt_close($stmt_it); }
    $sql_hr = "SELECT employee_role FROM user_hr_details WHERE user_id = ?";
    if ($stmt_hr = mysqli_prepare($link, $sql_hr)) { mysqli_stmt_bind_param($stmt_hr, "i", $user_id); mysqli_stmt_execute($stmt_hr); $result_hr = mysqli_stmt_get_result($stmt_hr); $hr_details_for_role = mysqli_fetch_assoc($result_hr); mysqli_stmt_close($stmt_hr); }
} else { $_SESSION['message'] = "Invalid User ID."; $_SESSION['message_type'] = "danger"; header("Location: view_users.php"); exit(); }

function e_it($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }

$employee_role_options = ["USER", "ADMIN"];
$page_specific_title = "IT Setup Form";
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
                <div class="main-header-logo"><div class="logo-header" data-background-color="dark"><a href="index.php" class="logo"><img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" /></a><div class="nav-toggle"><button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button><button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button></div><button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button></div></div>
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <div class="navbar-brand-wrapper d-flex align-items-center me-auto"><a href="index.php" style="display: flex; align-items: center; text-decoration: none; color: #333;"><img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 40px; margin-right: 10px;" /><span style="font-size: 1.5rem; font-weight: 500;">Simplex Engineering</span></a></div>
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret"><a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false"><div class="avatar-sm"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User Avatar" class="avatar-img rounded-circle"/></div><span class="profile-username"><span class="op-7">Hi,</span> <span class="fw-bold"><?php echo $username_for_display; ?></span></span></a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn"><div class="dropdown-user-scroll scrollbar-outer"><li><div class="user-box"><div class="avatar-lg"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="image profile" class="avatar-img rounded"/></div><div class="u-text"><h4><?php echo $username_for_display; ?></h4><p class="text-muted"><?php echo $user_email_placeholder; ?></p><p class="text-muted">Emp Code: <?php echo $empcode; ?></p></div></div></li><li><div class="dropdown-divider"></div><a class="dropdown-item" href="#">My Profile</a><div class="dropdown-divider"></div><a class="dropdown-item" href="../../LOGIN/logout.php">Logout</a></li></div></ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <div class="container">
                <div class="page-inner">
                    <div class="card shadow-lg mx-auto" style="max-width: 800px;">
                        <div class="card-header">
                            <div class="card-title text-center">
                                <h2 class="fw-bold"><?php echo $form_mode === 'edit' ? 'Update' : 'Complete'; ?> IT Setup Details</h2>
                                <p class="card-category">For User: <strong><?php echo e_it($user_name); ?></strong> (ID: <?php echo e_it($user_id); ?>)</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="actions/save_it_details.php" method="POST">
                                <input type="hidden" name="user_id" value="<?php echo e_it($user_id); ?>">
                                <input type="hidden" name="form_mode" value="<?php echo $form_mode; ?>">
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="official_email" class="form-label">Official Email <span class="text-danger">*</span></label>
                                        <input type="email" id="official_email" name="official_email" class="form-control" value="<?php echo e_it($it_details['official_email'] ?? ''); ?>" placeholder="e.g., user@company.com" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="official_phone_number" class="form-label">Official Phone Number</label>
                                        <input type="tel" id="official_phone_number" name="official_phone_number" class="form-control" value="<?php echo e_it($it_details['official_phone_number'] ?? ''); ?>" placeholder="e.g., +91-XXXXXXXXXX">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="intercom_number" class="form-label">Intercom Number</label>
                                        <input type="text" id="intercom_number" name="intercom_number" class="form-control" value="<?php echo e_it($it_details['intercom_number'] ?? ''); ?>" placeholder="e.g., 1234">
                                    </div>
                                    <div class="col-12">
                                        <label for="employee_role" class="form-label">Portal Role <span class="text-danger">*</span></label>
                                        <select id="employee_role" name="employee_role" class="form-select" required>
                                            <option value="">-- Select Role --</option>
                                            <?php 
                                                $selected_role = $hr_details_for_role['employee_role'] ?? 'USER';
                                            ?>
                                            <?php foreach($employee_role_options as $option): ?>
                                            <option value="<?php echo e_it($option); ?>" <?php echo ($selected_role === $option) ? 'selected' : ''; ?>><?php echo e_it($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="view_users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back To Employee Master</a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> <?php echo $form_mode === 'edit' ? 'Update IT Details' : 'Save IT Details'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left"><ul class="nav"></ul></nav>
                    <div class="copyright"><?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Abhimanyu</a></div>
                    <div>For <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering</a>.</div>
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