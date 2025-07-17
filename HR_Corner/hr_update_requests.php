<?php
// /hr_update_requests.php

session_set_cookie_params(['path' => '/']);
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Ensure this page is only accessible by authorized roles (e.g., HR, ADMIN)
if (!isset($_SESSION['loggedin']) || ($_SESSION['employee_role'] !== 'HR' && $_SESSION['employee_role'] !== 'ADMIN')) {
    die("Access Denied: You do not have permission to view this page.");
}

require_once 'includes/db_config.php';

// --- Get HR user info for the template header ---
$loggedIn_username = $_SESSION['username'] ?? 'User';
$username_for_display = htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $loggedIn_username))));
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A';
$user_email_placeholder = htmlspecialchars($loggedIn_username) . '@simplexengg.in';
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';
$avatar_path = "assets/img/kaiadmin/default-avatar.png";

if ($empcode !== 'N/A') {
    $sql_avatar = "SELECT u.profile_picture_path FROM users u JOIN user_hr_details hrd ON u.user_id = hrd.user_id WHERE hrd.employee_id_ascent = ?";
    if ($stmt_avatar = $link->prepare($sql_avatar)) {
        $stmt_avatar->bind_param("s", $empcode);
        $stmt_avatar->execute();
        $result_avatar = $stmt_avatar->get_result();
        if($row_avatar = $result_avatar->fetch_assoc()){
            if (!empty($row_avatar['profile_picture_path']) && file_exists($row_avatar['profile_picture_path'])) {
                $avatar_path = $row_avatar['profile_picture_path'];
            }
        }
        $stmt_avatar->close();
    }
}


// --- Helper Functions for Generating Diffs ---
function generateDiffHtml($field_label, $original_value, $new_value) {
    $original_value = htmlspecialchars($original_value ?? 'Not set');
    $new_value = htmlspecialchars($new_value ?? 'Not set');
    
    // This function is now only called when a change is detected.
    return "<div class='mb-2'><strong class='text-body'>".htmlspecialchars($field_label)."</strong><p class='mb-0 ps-3 border-start border-danger border-3'><small class='text-muted'>Original:</small> {$original_value}</p><p class='mb-0 ps-3 border-start border-success border-3'><small class='text-muted'>Requested:</small> {$new_value}</p></div>";
}

function generateNewEntryHtml($title, $data) {
    $html = "<div class='mb-2'><strong class='text-success'>New Entry Added: ".htmlspecialchars($title)."</strong>";
    foreach($data as $key => $value) {
        if(!is_array($value)) $html .= "<p class='mb-0 ps-3'><small class='text-muted'>".htmlspecialchars(ucwords(str_replace('_',' ',$key))).":</small> ".htmlspecialchars($value)."</p>";
    }
    return $html . "</div>";
}

function generateDeletedEntryHtml($title, $data) {
    return "<div class='mb-2'><strong class='text-danger'>Entry Deleted: ".htmlspecialchars($title)."</strong><p class='mb-0 ps-3'><small class='text-muted'>Details:</small> ".htmlspecialchars($data)."</p></div>";
}


// --- Fetch all pending requests ---
$sql_requests = "SELECT r.request_id, r.user_id, r.changed_data_json, r.requested_at, u.first_name, u.surname, hrd.employee_id_ascent
                 FROM user_update_requests r
                 JOIN users u ON r.user_id = u.user_id
                 LEFT JOIN user_hr_details hrd ON r.user_id = hrd.user_id
                 WHERE r.request_status = 'pending' ORDER BY r.requested_at ASC";
$result_requests = mysqli_query($link, $sql_requests);

$page_specific_title = "User Profile Update Requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo htmlspecialchars($page_specific_title); ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: { families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ["assets/css/fonts.min.css"], },
            active: function () { sessionStorage.fonts = true; },
        });
    </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
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
                <h3 class="fw-bold mb-4"><?php echo htmlspecialchars($page_specific_title); ?></h3>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>
                
                <div class="row">
                    <?php
                    if ($result_requests && mysqli_num_rows($result_requests) > 0) {
                        while ($request = mysqli_fetch_assoc($result_requests)) {
                            $request_id = $request['request_id'];
                            $user_id = $request['user_id'];
                            $new_data = json_decode($request['changed_data_json'], true);

                            // --- Fetch all original data for comparison ---
                            $original_data = [];
                            $stmt_orig = $link->prepare("SELECT * FROM users WHERE user_id = ?"); $stmt_orig->bind_param("i", $user_id); $stmt_orig->execute(); $original_data['users'] = $stmt_orig->get_result()->fetch_assoc(); $stmt_orig->close();
                            $stmt_orig = $link->prepare("SELECT * FROM spouse_details WHERE user_id = ?"); $stmt_orig->bind_param("i", $user_id); $stmt_orig->execute(); $original_data['spouse'] = $stmt_orig->get_result()->fetch_assoc(); $stmt_orig->close();
                            
                            $stmt_orig = $link->prepare("SELECT * FROM parent_details WHERE user_id = ?"); $stmt_orig->bind_param("i", $user_id); $stmt_orig->execute();
                            $parents_res = $stmt_orig->get_result();
                            while($p_row = $parents_res->fetch_assoc()){ $original_data[$p_row['parent_type']] = $p_row; }
                            $stmt_orig->close();

                            $stmt_orig = $link->prepare("SELECT * FROM user_education WHERE user_id = ?"); $stmt_orig->bind_param("i", $user_id); $stmt_orig->execute();
                            $edu_res = $stmt_orig->get_result();
                            $original_data['education'] = [];
                            while($e_row = $edu_res->fetch_assoc()){ $original_data['education'][$e_row['education_id']] = $e_row; }
                            $stmt_orig->close();
                            
                            $stmt_orig = $link->prepare("SELECT * FROM user_bank_details WHERE user_id = ?"); $stmt_orig->bind_param("i", $user_id); $stmt_orig->execute(); $original_data['bank'] = $stmt_orig->get_result()->fetch_assoc(); $stmt_orig->close();
                            
                            $changes = []; // Array to hold all detected changes by section

                            // ✅ --- NEW REFINED COMPARISON LOGIC ---

                            // Compare Personal Details
                            $personal_diff = '';
                            if (!empty($original_data['users'])) {
                                foreach($new_data as $field => $new_value) {
                                    if(isset($original_data['users'][$field]) && trim($original_data['users'][$field]) != trim($new_value)) {
                                        $personal_diff .= generateDiffHtml(ucwords(str_replace('_', ' ', $field)), $original_data['users'][$field], $new_value);
                                    }
                                }
                            }
                            if(!empty($personal_diff)) $changes['Personal Details'] = $personal_diff;

                            // Compare Spouse & Parents
                            foreach(['spouse', 'father', 'mother'] as $type){
                                $diff = '';
                                if(isset($new_data[$type])){
                                    foreach($new_data[$type] as $field => $new_value){
                                        $original_value = $original_data[ucfirst($type)][$field] ?? null;
                                        if (trim($original_value) != trim($new_value)) {
                                            $diff .= generateDiffHtml(ucwords(str_replace('_', ' ', $field)), $original_value, $new_value);
                                        }
                                    }
                                }
                                if(!empty($diff)) $changes[ucfirst($type) . ' Details'] = $diff;
                            }
                             
                            // Compare Bank Details
                            $bank_diff = '';
                            if(isset($new_data['bank'])){
                                foreach($new_data['bank'] as $field => $new_value){
                                     $original_value = $original_data['bank'][$field] ?? null;
                                     if (trim($original_value) != trim($new_value)) {
                                        $bank_diff .= generateDiffHtml(ucwords(str_replace('_', ' ', $field)), $original_value, $new_value);
                                     }
                                }
                            }
                            if(!empty($bank_diff)) $changes['Bank Details'] = $bank_diff;

                            // Compare Dynamic Sections (e.g., Education)
                            $education_diff = '';
                            $original_edu = $original_data['education'] ?? [];
                            $new_edu = $new_data['education'] ?? [];
                            
                            $new_edu_by_id = [];
                            foreach($new_edu as $item){
                                if(!empty($item['id'])) $new_edu_by_id[$item['id']] = $item;
                            }

                            // Check for updates and deletions
                            foreach($original_edu as $id => $orig_item){
                                if(isset($new_edu_by_id[$id])){ // It's an update
                                    $item_diff = '';
                                    foreach($orig_item as $field => $value){
                                        if(isset($new_edu_by_id[$id][$field]) && trim($value) != trim($new_edu_by_id[$id][$field])){
                                            $item_diff .= generateDiffHtml(ucwords(str_replace('_',' ',$field)), $value, $new_edu_by_id[$id][$field]);
                                        }
                                    }
                                    if(!empty($item_diff)) $education_diff .= "<hr><strong>Updated Qualification: {$orig_item['qualification']}</strong>" . $item_diff;
                                } else { // It's a deletion
                                    $education_diff .= generateDeletedEntryHtml("Qualification", $orig_item['qualification']);
                                }
                            }

                            // Check for new entries
                            foreach($new_edu as $item){
                                if(empty($item['id'])){
                                    $education_diff .= generateNewEntryHtml("Qualification", $item);
                                }
                            }
                            if(!empty($education_diff)) $changes['Education Details'] = $education_diff;

                            // --- Add logic for other dynamic sections (Certifications, Experience) in the same way ---

                    ?>
                            <div class="col-lg-6 col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="card-title mb-0">Request for: <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['surname']); ?></h5>
                                            <small>Emp ID: <?php echo htmlspecialchars($request['employee_id_ascent']); ?></small>
                                        </div>
                                        <small class="text-muted">Requested on: <?php echo date('d M Y, h:i A', strtotime($request['requested_at'])); ?></small>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($changes)): ?>
                                            <div class="accordion" id="accordion_<?php echo $request_id; ?>">
                                                <?php $i = 0; foreach($changes as $section => $diff_html): ?>
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading_<?php echo $request_id . '_' . $i; ?>">
                                                            <button class="accordion-button <?php if($i > 0) echo 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $request_id . '_' . $i; ?>">
                                                                Changes in: &nbsp;<strong><?php echo $section; ?></strong>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse_<?php echo $request_id . '_' . $i; ?>" class="accordion-collapse collapse <?php if($i == 0) echo 'show'; ?>" data-bs-parent="#accordion_<?php echo $request_id; ?>">
                                                            <div class="accordion-body">
                                                                <?php echo $diff_html; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php $i++; endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                             <p class="text-muted text-center p-3"><em>No changes to text fields were detected. This request might be for file uploads or adding/deleting dynamic entries.</em></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer text-center">
                                        <form action="actions/process_update_request.php" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this request?');"><i class="fa fa-times"></i> Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<div class='col-12'><div class='alert alert-info'>No pending update requests found.</div></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        <footer class="footer">
                <div class="container-fluid d-flex justify-content-between"><div class="copyright"><?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Abhimanyu</a></div><div>For <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering</a>.</div></div>
            </footer>
    </div>
</div>
<!-- Core JS files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
