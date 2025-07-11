<?php
// /registartion_page.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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
$page_specific_title = "New Employee Registration";
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
        google: { families: ["Public Sans:300,400,500,600,700", "Inter:400,500,600,700"] },
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
    
    <style>
        .form-page { display: none; }
        .form-page.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .form-section-title { font-size: 1.25rem; font-weight: 600; padding-bottom: 0.75rem; border-bottom: 1px solid #dee2e6; margin-bottom: 1.5rem; }
        .dynamic-entry { border-top: 1px dashed #dee2e6; padding-top: 1.5rem; margin-top: 1.5rem; }
        .form-label.required::after { content: " *"; color: #dc3545; }
        .error-message { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; display: none; }
    </style>
</head>
<body>
   <div class="wrapper">
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
          <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
              <div class="navbar-brand-wrapper d-flex align-items-center me-auto">
                <a href="dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: #333;">
                  <img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 60px; margin-right: 10px;" /> 
                  <span style="font-size: 1.8rem; font-weight: 500;">
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
                        <li>
                            <div class="dropdown-user-scroll scrollbar-outer">
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
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../registration_project/my_profile.php">My Profile</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">Account Setting</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../../LOGIN/logout.php">Logout</a>
                            </div>
                        </li>
                    </ul>
                </li>
              </ul>
              </div>
          </nav>
        </div>
        
        <div class="container">
          <div class="page-inner">
              <div class="card shadow-lg">
                  <div class="card-header">
                      <div class="d-flex align-items-center justify-content-between">
                          <div class="text-center flex-grow-1">
                              <h2 class="card-title fw-bold mb-1">Multi-Step Registration</h2>
                              <p class="card-category">Complete all steps to finalize registration.</p>
                          </div>
                          <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                              <i class="fas fa-question-circle"></i> Help
                          </button>
                      </div>
                  </div>
                  <div class="card-body">
                      <div class="progress" style="height: 12px;">
                        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 25%;"></div>
                      </div>
                      <hr>

                     <form id="multiPageForm" action="actions/save_registration.php" method="POST" enctype="multipart/form-data" class="mt-4">
                                
                               <div class="form-page active" id="page1">
                                    <div class="accordion mb-4" id="instructionsAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingOne">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                                    <strong>Important Instructions</strong>
                                                </button>
                                            </h2>
                                            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#instructionsAccordion">
                                                <div class="accordion-body">
                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item">Please use only proper case (e.g., <strong>Rahul Kumar Sharma</strong>).</li>
                                                        <li class="list-group-item">All mandatory (<span class="text-danger">*</span>) fields are to be filled.</li>
                                                        <li class="list-group-item">Digital profile picture and signature must be less than <strong>500 KB</strong>.</li>
                                                        <li class="list-group-item">Submit copies of Identity proof, Bank details, Qualification, and Experience certificates.</li>
                                                        <li class="list-group-item">If any problem please contact our <strong>Simplex HR or IT Team</strong>.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <fieldset>
                                        <legend class="form-section-title">Personal Identification</legend>
                                        <div class="row g-3">
                                            <div class="col-md-6"><label for="nameAsFor" class="form-label required">Name as per</label><select id="nameAsFor" name="nameAsFor" class="form-select"><option value="">Select...</option><option value="10th_certificate">10th certificate</option><option value="aadhar_card">Aadhar card</option><option value="pan_card">PAN Card</option></select></div>
                                            <div class="col-md-6"><label for="salutation" class="form-label required">Salutation</label><select id="salutation" name="salutation" class="form-select"><option value="">Select...</option><option value="Mr.">Mr.</option><option value="Miss">Miss</option><option value="Mrs.">Mrs.</option></select></div>
                                            <div class="col-md-4"><label for="firstName" class="form-label required">First Name</label><input type="text" id="firstName" name="firstName" class="form-control"></div>
                                            <div class="col-md-4"><label for="middleName" class="form-label">Middle Name</label><input type="text" id="middleName" name="middleName" class="form-control"></div>
                                            <div class="col-md-4"><label for="surname" class="form-label">Surname</label><input type="text" id="surname" name="surname" class="form-control"></div>
                                            <div class="col-md-6"><label for="nationality" class="form-label required">Nationality</label><select id="nationality" name="nationality" class="form-select"><option value="">Select...</option><option value="India">India</option><option value="USA">United States</option></select></div>
                                            <div class="col-md-6"><label for="gender" class="form-label required">Gender</label><select id="gender" name="gender" class="form-select"><option value="">Select...</option><option value="male">Male</option><option value="female">Female</option></select></div>
                                            <div class="col-md-4"><label for="religion" class="form-label required">Religion</label><select id="religion" name="religion" class="form-select"><option value="">Select...</option><option value="Hindu">Hindu</option><option value="Muslim">Muslim</option></select></div>
                                            <div class="col-md-4"><label for="categoryType" class="form-label required">Category</label><select id="categoryType" name="categoryType" class="form-select"><option value="">Select...</option><option value="General">General</option><option value="OBC">OBC</option><option value="SC">SC</option><option value="ST">ST</option></select></div>
                                            <div class="col-md-4"><label for="dob" class="form-label required">Date Of Birth</label><input type="date" id="dob" name="dob" class="form-control"></div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Address & Physical Details</legend>
                                        <div class="row g-3">
                                            <h5 class="text-primary mt-3">Permanent Address</h5>
                                            <div class="col-md-4"><label for="permBirthCountry" class="form-label required">Country</label><select id="permBirthCountry" name="permBirthCountry" class="form-select"><option value="">Select...</option><option value="India">India</option></select></div>
                                            <div class="col-md-4"><label for="permBirthState" class="form-label required">State</label><select id="permBirthState" name="permBirthState" class="form-select"><option value="">Select...</option><option value="Delhi">Delhi</option></select></div>
                                            <div class="col-md-4"><label for="permBirthCity" class="form-label required">City/Village</label><input type="text" id="permBirthCity" name="permBirthCity" class="form-control"></div>
                                            <div class="col-12"><label for="permAddress1" class="form-label required">Address Line 1</label><input type="text" id="permAddress1" name="permAddress1" class="form-control"></div>
                                            <div class="col-12"><label for="permAddress2" class="form-label">Address Line 2</label><input type="text" id="permAddress2" name="permAddress2" class="form-control"></div>
                                            
                                            <h5 class="text-primary mt-3">Present Address</h5>
                                            <div class="col-12"><div class="form-check"><input type="checkbox" id="sameAsPermanent" name="sameAsPermanent" class="form-check-input"><label for="sameAsPermanent" class="form-check-label">Same as Permanent Address</label></div></div>
                                            <div class="col-md-4"><label for="presentBirthCountry" class="form-label required">Country</label><select id="presentBirthCountry" name="presentBirthCountry" class="form-select"><option value="">Select...</option><option value="India">India</option></select></div>
                                            <div class="col-md-4"><label for="presentBirthState" class="form-label required">State</label><select id="presentBirthState" name="presentBirthState" class="form-select"><option value="">Select...</option><option value="Delhi">Delhi</option></select></div>
                                            <div class="col-md-4"><label for="presentBirthCity" class="form-label required">City/Village</label><input type="text" id="presentBirthCity" name="presentBirthCity" class="form-control"></div>
                                            <div class="col-12"><label for="presentAddress1" class="form-label required">Address Line 1</label><input type="text" id="presentAddress1" name="presentAddress1" class="form-control"></div>
                                            <div class="col-12"><label for="presentAddress2" class="form-label">Address Line 2</label><input type="text" id="presentAddress2" name="presentAddress2" class="form-control"></div>

                                            <h5 class="text-primary mt-3">Physical & Contact Details</h5>
                                            <div class="col-md-4"><label for="bloodGroup" class="form-label">Blood Group</label><select id="bloodGroup" name="bloodGroup" class="form-select"><option value="">Select...</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option></select></div>
                                            <div class="col-md-4"><label for="weightKg" class="form-label">Weight (KG)</label><input type="number" id="weightKg" name="weightKg" class="form-control"></div>
                                            <div class="col-md-4"><label for="heightCm" class="form-label">Height (CM)</label><input type="number" id="heightCm" name="heightCm" class="form-control"></div>
                                            <div class="col-12"><label for="identificationMarks" class="form-label required">Identification Marks</label><input type="text" id="identificationMarks" name="identificationMarks" class="form-control"></div>
                                            <div class="col-md-6"><label for="yourEmailId" class="form-label">Your Email ID</label><input type="email" id="yourEmailId" name="yourEmailId" class="form-control"></div>
                                            <div class="col-md-6"><label for="yourPhoneNumber" class="form-label required">Your Phone Number</label><input type="tel" id="yourPhoneNumber" name="yourPhoneNumber" class="form-control"></div>
                                            <div class="col-md-6"><label for="emergencyContactNumber" class="form-label required">Emergency Contact</label><input type="tel" id="emergencyContactNumber" name="emergencyContactNumber" class="form-control"></div>
                                        </div>
                                    </fieldset>
                                    
                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Document Uploads</legend>
                                        <div class="row g-3">
                                            <div class="col-md-6"><label for="uploadPicture" class="form-label required">Upload Profile Picture</label><input type="file" id="uploadPicture" name="uploadPicture" class="form-control" accept="image/*,.pdf"></div>
                                            <div class="col-md-6"><label for="uploadSign" class="form-label required">Upload Signature</label><input type="file" id="uploadSign" name="uploadSign" class="form-control" accept="image/*,.pdf"></div>
                                            
                                            <div class="col-12 mt-3"><label class="form-label required">Pan Card Available?</label><div><div class="form-check form-check-inline"><input type="radio" id="panAvailableYes" name="panAvailable" value="yes" class="form-check-input"><label for="panAvailableYes" class="form-check-label">Yes</label></div><div class="form-check form-check-inline"><input type="radio" id="panAvailableNo" name="panAvailable" value="no" class="form-check-input"><label for="panAvailableNo" class="form-check-label">No</label></div></div></div>
                                            <div id="panDetailsDiv" class="row g-3 conditional-section">
                                                <div class="col-md-6"><label for="panCardNo" class="form-label required">PAN Card No</label><input type="text" id="panCardNo" name="panCardNo" class="form-control"></div>
                                                <div class="col-md-6"><label for="panCardFile" class="form-label required">Upload PAN Card</label><input type="file" id="panCardFile" name="panCardFile" class="form-control" accept="image/*,.pdf"></div>
                                            </div>

                                            <div class="col-12 mt-3"><label class="form-label required">Aadhaar Card Available?</label><div><div class="form-check form-check-inline"><input type="radio" id="aadharAvailableYes" name="aadharAvailable" value="yes" class="form-check-input"><label for="aadharAvailableYes" class="form-check-label">Yes</label></div><div class="form-check form-check-inline"><input type="radio" id="aadharAvailableNo" name="aadharAvailable" value="no" class="form-check-input"><label for="aadharAvailableNo" class="form-check-label">No</label></div></div></div>
                                            <div id="aadharDetailsDiv" class="row g-3 conditional-section">
                                                <div class="col-md-6"><label for="aadharNumberField" class="form-label required">Aadhaar Number</label><input type="text" id="aadharNumberField" name="aadharNumberField" class="form-control"></div>
                                                <div class="col-md-6"><label for="aadharCardFile" class="form-label required">Upload Aadhaar Card</label><input type="file" id="aadharCardFile" name="aadharCardFile" class="form-control" accept="image/*,.pdf"></div>
                                            </div>

                                            <div class="col-12 mt-3"><label class="form-label">Driving Licence Available?</label><div><div class="form-check form-check-inline"><input type="radio" id="dlAvailableYes" name="dlAvailable" value="yes" class="form-check-input"><label for="dlAvailableYes" class="form-check-label">Yes</label></div><div class="form-check form-check-inline"><input type="radio" id="dlAvailableNo" name="dlAvailable" value="no" class="form-check-input"><label for="dlAvailableNo" class="form-check-label">No</label></div></div></div>
                                            <div id="dlDetailsDiv" class="row g-3 conditional-section">
                                                <div class="col-md-6"><label for="dlNumber" class="form-label required">Licence Number</label><input type="text" id="dlNumber" name="dlNumber" class="form-control"></div>
                                                <div class="col-md-6"><label for="dlExpiry" class="form-label required">Expiry Date</label><input type="date" id="dlExpiry" name="dlExpiry" class="form-control"></div>
                                                <div class="col-md-6"><label for="dlFile" class="form-label required">Upload Driving Licence</label><input type="file" id="dlFile" name="dlFile" class="form-control" accept="image/*,.pdf"></div>
                                            </div>

                                            <div class="col-12 mt-3"><label class="form-label">Passport Available?</label><div><div class="form-check form-check-inline"><input type="radio" id="passportAvailableYes" name="passportAvailable" value="yes" class="form-check-input"><label for="passportAvailableYes" class="form-check-label">Yes</label></div><div class="form-check form-check-inline"><input type="radio" id="passportAvailableNo" name="passportAvailable" value="no" class="form-check-input"><label for="passportAvailableNo" class="form-check-label">No</label></div></div></div>
                                            <div id="passportDetailsDiv" class="row g-3 conditional-section">
                                                <div class="col-md-6"><label for="passportNumberField" class="form-label required">Passport Number</label><input type="text" id="passportNumberField" name="passportNumberField" class="form-control"></div>
                                                <div class="col-md-6"><label for="passportExpiry" class="form-label required">Expiry Date</label><input type="date" id="passportExpiry" name="passportExpiry" class="form-control"></div>
                                                <div class="col-md-6"><label for="passportFile" class="form-label required">Upload Passport</label><input type="file" id="passportFile" name="passportFile" class="form-control" accept="image/*,.pdf"></div>
                                            </div>
                                        </div>
                                    </fieldset>
                               </div>

                               <div class="form-page" id="page2">
                                    <fieldset>
                                        <legend class="form-section-title">Family Details</legend>
                                        <div class="row g-3">
                                            <div class="col-md-6"><label for="maritalStatus" class="form-label required">Marital Status</label><select id="maritalStatus" name="maritalStatus" class="form-select"><option value="">Select...</option><option value="Single">Single</option><option value="Married">Married</option><option value="Widowed">Widowed</option><option value="Divorced">Divorced</option></select></div>
                                        </div>
                                    </fieldset>

                                    <fieldset id="spouseDetailsSection" class="mt-4 conditional-section" style="display: none;">
                                        <legend class="form-section-title">Spouse's Details</legend>
                                        <div class="row g-3">
                                            <div class="col-md-6"><label for="spouseName" class="form-label required">Spouse's Name</label><input type="text" id="spouseName" name="spouseName" class="form-control"></div>
                                            <div class="col-md-6"><label for="spouseDob" class="form-label required">Spouse's Date of Birth</label><input type="date" id="spouseDob" name="spouseDob" class="form-control"></div>
                                            <div class="col-md-6"><label for="spouseOccupation" class="form-label required">Spouse's Occupation</label><input type="text" id="spouseOccupation" name="spouseOccupation" class="form-control"></div>
                                            <div class="col-md-6"><label for="spouseMobile" class="form-label">Spouse's Mobile</label><input type="tel" id="spouseMobile" name="spouseMobile" class="form-control"></div>
                                            <div class="col-12"><label for="spouseAddress" class="form-label required">Spouse's Address</label><input type="text" id="spouseAddress" name="spouseAddress" class="form-control"></div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Parent's Details</legend>
                                        <div class="row g-3">
                                            <h5 class="text-primary">Father's Details</h5>
                                            <div class="col-md-6"><label for="fatherNameP2" class="form-label required">Father's Name</label><input type="text" id="fatherNameP2" name="fatherNameP2" class="form-control"></div>
                                            <div class="col-md-6"><label for="fatherDob" class="form-label required">Father's Date of Birth</label><input type="date" id="fatherDob" name="fatherDob" class="form-control"></div>
                                            <div class="col-md-6"><label for="fatherOccupation" class="form-label required">Father's Occupation</label><input type="text" id="fatherOccupation" name="fatherOccupation" class="form-control"></div>
                                            <div class="col-md-6"><label for="fatherAadharFile" class="form-label">Upload Father's Aadhar</label><input type="file" id="fatherAadharFile" name="fatherAadharFile" class="form-control" accept="image/*,.pdf"></div>
                                            
                                            <h5 class="text-primary mt-3">Mother's Details</h5>
                                            <div class="col-md-6"><label for="motherNameP2" class="form-label required">Mother's Name</label><input type="text" id="motherNameP2" name="motherNameP2" class="form-control"></div>
                                            <div class="col-md-6"><label for="motherDob" class="form-label required">Mother's Date of Birth</label><input type="date" id="motherDob" name="motherDob" class="form-control"></div>
                                            <div class="col-md-6"><label for="motherOccupation" class="form-label required">Mother's Occupation</label><input type="text" id="motherOccupation" name="motherOccupation" class="form-control"></div>
                                        </div>
                                    </fieldset>
                               </div>

                               <div class="form-page" id="page3">
                                    <fieldset>
                                        <legend class="form-section-title">Educational Qualifications</legend>
                                        <div id="educationEntriesContainer">
                                            <div class="row g-3 education-entry">
                                                <div class="col-md-6"><label for="eduQualification_0" class="form-label required">Qualification</label><input type="text" id="eduQualification_0" name="eduQualification[]" class="form-control"></div>
                                                <div class="col-md-6"><label for="eduBoardUniversity_0" class="form-label required">Board/University</label><input type="text" id="eduBoardUniversity_0" name="eduBoardUniversity[]" class="form-control"></div>
                                                <div class="col-md-4"><label for="eduSubject_0" class="form-label required">Subject</label><input type="text" id="eduSubject_0" name="eduSubject[]" class="form-control"></div>
                                                <div class="col-md-4"><label for="eduPassingYear_0" class="form-label required">Passing Year</label><input type="number" id="eduPassingYear_0" name="eduPassingYear[]" class="form-control"></div>
                                                <div class="col-md-4"><label for="eduPercentageGrade_0" class="form-label required">Percentage/Grade</label><input type="text" id="eduPercentageGrade_0" name="eduPercentageGrade[]" class="form-control"></div>
                                                <div class="col-12"><label for="eduDocument_0" class="form-label">Upload Certificate</label><input type="file" id="eduDocument_0" name="eduDocument[]" class="form-control" accept="image/*,.pdf"></div>
                                            </div>
                                        </div>
                                        <button type="button" id="addEducationBtn" class="btn btn-secondary btn-sm mt-3">Add Another Qualification</button>
                                    </fieldset>

                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Certifications</legend>
                                        <div id="certificationEntriesContainer">
                                          </div>
                                        <button type="button" id="addCertificationBtn" class="btn btn-secondary btn-sm mt-3">Add Certification</button>
                                    </fieldset>

                                  <fieldset class="mt-4">
    <legend class="form-section-title">Work Experience</legend>
            <div class="row g-3">
            <div class="col-12"><label class="form-label required">Do you have past experience?</label><div><div class="form-check form-check-inline"><input type="radio" id="pastExperienceYes" name="pastExperience" value="yes" class="form-check-input"><label for="pastExperienceYes" class="form-check-label">Yes</label></div><div class="form-check form-check-inline"><input type="radio" id="pastExperienceNo" name="pastExperience" value="no" class="form-check-input"><label for="pastExperienceNo" class="form-check-label">No</label></div></div></div>
        </div>
    <div id="workExperienceDetailsDiv" class="conditional-section mt-2">
        
        <div class="row g-3 mb-3">
            <div class="col-12">
                <label class="form-label required">Do you have a PF account?</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input type="radio" id="pfAccountYes" name="pfAccount" value="yes" class="form-check-input">
                        <label for="pfAccountYes" class="form-check-label">Yes</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" id="pfAccountNo" name="pfAccount" value="no" class="form-check-input">
                        <label for="pfAccountNo" class="form-check-label">No</label>
                    </div>
                </div>
            </div>
        </div>
        <div id="pfAccountDetailsDiv" class="row g-3 conditional-section">
            <div class="col-md-6">
                <label for="pfUanNo" class="form-label required">UAN No.</label>
                <input type="text" id="pfUanNo" name="pfUanNo" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="pfEsiNo" class="form-label">ESI No.</label>
                <input type="text" id="pfEsiNo" name="pfEsiNo" class="form-control">
            </div>
        </div>
        <hr>
        <div id="workExperienceEntriesContainer"></div>
        <button type="button" id="addExperienceBtn" class="btn btn-secondary btn-sm mt-3">Add Another Experience</button>
    </div>
</fieldset>
                               </div>

                               <div class="form-page" id="page4">
                                    <fieldset>
                                        <legend class="form-section-title">Language Proficiency</legend>
                                        <div id="languageEntriesContainer">
                                            <div class="row g-3 language-entry align-items-end">
                                                <div class="col-md-4"><label for="langName_0" class="form-label required">Language</label><input type="text" id="langName_0" name="langName[]" class="form-control"></div>
                                                <div class="col-md-8"><label class="form-label">Proficiency</label><div class="mt-1"><div class="form-check form-check-inline"><input type="checkbox" id="langSpeak_0" name="langProficiency_0[]" value="speak" class="form-check-input"><label for="langSpeak_0" class="form-check-label">Speak</label></div><div class="form-check form-check-inline"><input type="checkbox" id="langRead_0" name="langProficiency_0[]" value="read" class="form-check-input"><label for="langRead_0" class="form-check-label">Read</label></div><div class="form-check form-check-inline"><input type="checkbox" id="langWrite_0" name="langProficiency_0[]" value="write" class="form-check-input"><label for="langWrite_0" class="form-check-label">Write</label></div></div></div>
                                            </div>
                                        </div>
                                        <button type="button" id="addLanguageBtn" class="btn btn-secondary btn-sm mt-3">Add Another Language</button>
                                    </fieldset>
                                   
                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Reference Details</legend>
                                        <div id="referenceEntriesContainer">
                                            <div class="card card-body mb-3 reference-block">
                                                <h5 class="card-title">Reference 1</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label for="refName_0" class="form-label required">Name</label><input type="text" id="refName_0" name="refName[]" class="form-control"></div>
                                                    <div class="col-md-6"><label for="refContactNo_0" class="form-label required">Contact No.</label><input type="tel" id="refContactNo_0" name="refContactNo[]" class="form-control"></div>
                                                    <div class="col-12"><label for="refAddress_0" class="form-label required">Address</label><input type="text" id="refAddress_0" name="refAddress[]" class="form-control"></div>
                                                </div>
                                            </div>
                                            <div class="card card-body mb-3 reference-block">
                                                <h5 class="card-title">Reference 2</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label for="refName_1" class="form-label required">Name</label><input type="text" id="refName_1" name="refName[]" class="form-control"></div>
                                                    <div class="col-md-6"><label for="refContactNo_1" class="form-label required">Contact No.</label><input type="tel" id="refContactNo_1" name="refContactNo[]" class="form-control"></div>
                                                    <div class="col-12"><label for="refAddress_1" class="form-label required">Address</label><input type="text" id="refAddress_1" name="refAddress[]" class="form-control"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Bank Details</legend>
                                        <div class="row g-3">
                                            <div class="col-md-6"><label for="bankName" class="form-label required">Bank Name</label><input type="text" id="bankName" name="bankName" class="form-control"></div>
                                            <div class="col-md-6"><label for="bankAccountNumber" class="form-label required">Account Number</label><input type="text" id="bankAccountNumber" name="bankAccountNumber" class="form-control"></div>
                                            <div class="col-md-6"><label for="bankIfsc" class="form-label required">IFSC Code</label><input type="text" id="bankIfsc" name="bankIfsc" class="form-control"></div>
                                            <div class="col-md-6"><label for="bankPassbookDoc" class="form-label required">Upload Passbook/Cheque</label><input type="file" id="bankPassbookDoc" name="bankPassbookDoc" class="form-control" accept="image/*,.pdf"></div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="mt-4">
                                        <legend class="form-section-title">Final Declaration</legend>
                                        <div class="form-check">
                                            <input type="checkbox" id="declarationCheck" name="declarationCheck" class="form-check-input">
                                            <label for="declarationCheck" class="form-check-label required">I hereby declare that all the information provided is true and correct to the best of my knowledge.</label>
                                        </div>
                                    </fieldset>
                               </div>

                            </form>
                      
                      <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                          <button type="button" id="prevBtn" class="btn btn-secondary" onclick="nextPrev(-1)" style="display:none;">Previous</button>
                          <button type="button" id="nextBtn" class="btn btn-primary" onclick="nextPrev(1)">Next</button>
                      </div>
                  </div>
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
    
        <div class="modal fade" id="helpModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Important Instructions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <ul class="list-group list-group-flush">
                  <li class="list-group-item">Please use only proper case to fill the joining form, e.g., <strong>Rahul Kumar Sharma</strong>.</li>
                  <li class="list-group-item">All mandatory (<span class="text-danger">*</span>) fields must be filled.</li>
                  <li class="list-group-item">The form has four stages: Personal Details, Family Details, Education/Work, and Final Details.</li>
                  <li class="list-group-item">Digital profile picture and signature must be less than <strong>500 KB</strong>.</li>
                  <li class="list-group-item">Please submit a copy of your Identity proof, Bank details, Qualification, and Experience certificates.</li>
                  <li class="list-group-item">If you encounter any problems, please contact our <strong>Simplex HR or IT Team</strong>.</li>
              </ul>
          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script> 
    <script>
        let currentPage = 0; 
        const pages = document.getElementsByClassName("form-page");
        const progressBar = document.getElementById("progressBar");
        const prevBtn = document.getElementById("prevBtn");
        const nextBtn = document.getElementById("nextBtn");
        let educationEntryCount = 1; 
        let certificationEntryCount = 0;
        let experienceEntryCount = 0; 
        let languageEntryCount = 1; 

        showPage(currentPage);

        function showPage(pageIndex) {
            for (let i = 0; i < pages.length; i++) {
                pages[i].classList.remove("active");
            }
            if (pages[pageIndex]) {
                 pages[pageIndex].classList.add("active");
            }
            prevBtn.style.display = (pageIndex === 0) ? "none" : "inline-block";
            nextBtn.innerHTML = (pageIndex === (pages.length - 1)) ? "Submit" : "Next";
            updateProgressBar(pageIndex);
        }

        function updateProgressBar(pageIndex) {
            const progressPercentage = ((pageIndex + 1) / pages.length) * 100;
            progressBar.style.width = progressPercentage + "%";
        }

        function nextPrev(n) {
            // Add validation logic here if needed before proceeding
            currentPage += n;
            if (currentPage >= pages.length) {
                document.getElementById("multiPageForm").submit();
                return false;
            }
            showPage(currentPage);
        }
        
        function setupConditionalField(radioName, conditionalDivId) {
            const radios = document.querySelectorAll(`input[name="${radioName}"]`);
            const conditionalDiv = document.getElementById(conditionalDivId);
            if (!radios.length || !conditionalDiv) return;

            const updateVisibility = () => {
                const selectedRadio = document.querySelector(`input[name="${radioName}"]:checked`);
                const show = selectedRadio && selectedRadio.value === 'yes';
                conditionalDiv.style.display = show ? '' : 'none'; // Use '' to revert to default CSS display
                
                // Set required attribute for inputs within the conditional div
                conditionalDiv.querySelectorAll('input, select').forEach(input => {
                    if (show) {
                        input.setAttribute('required', 'required');
                    } else {
                        input.removeAttribute('required');
                    }
                });
            };

            radios.forEach(radio => radio.addEventListener('change', updateVisibility));
            updateVisibility(); // Initial check
        }
        
        // Dynamic Entry Addition for Education
        document.getElementById('addEducationBtn').addEventListener('click', function() {
            const container = document.getElementById('educationEntriesContainer');
            const newEntry = document.createElement('div');
            newEntry.className = 'row g-3 education-entry dynamic-entry';
            newEntry.innerHTML = `
                <div class="col-md-6"><label for="eduQualification_${educationEntryCount}" class="form-label required">Qualification</label><input type="text" id="eduQualification_${educationEntryCount}" name="eduQualification[]" class="form-control"></div>
                <div class="col-md-6"><label for="eduBoardUniversity_${educationEntryCount}" class="form-label required">Board/University</label><input type="text" id="eduBoardUniversity_${educationEntryCount}" name="eduBoardUniversity[]" class="form-control"></div>
                <div class="col-md-4"><label for="eduSubject_${educationEntryCount}" class="form-label required">Subject</label><input type="text" id="eduSubject_${educationEntryCount}" name="eduSubject[]" class="form-control"></div>
                <div class="col-md-4"><label for="eduPassingYear_${educationEntryCount}" class="form-label required">Passing Year</label><input type="number" id="eduPassingYear_${educationEntryCount}" name="eduPassingYear[]" class="form-control"></div>
                <div class="col-md-4"><label for="eduPercentageGrade_${educationEntryCount}" class="form-label required">Percentage/Grade</label><input type="text" id="eduPercentageGrade_${educationEntryCount}" name="eduPercentageGrade[]" class="form-control"></div>
                <div class="col-12"><label for="eduDocument_${educationEntryCount}" class="form-label">Upload Certificate</label><input type="file" id="eduDocument_${educationEntryCount}" name="eduDocument[]" class="form-control" accept="image/*,.pdf"></div>
            `;
            container.appendChild(newEntry);
            educationEntryCount++;
        });

        // Dynamic Entry Addition for Certifications
        document.getElementById('addCertificationBtn').addEventListener('click', function() {
            const container = document.getElementById('certificationEntriesContainer');
            const newEntry = document.createElement('div');
            newEntry.className = 'row g-3 certification-entry dynamic-entry';
            newEntry.innerHTML = `
                <div class="col-md-6"><label for="certName_${certificationEntryCount}" class="form-label">Certificate Name</label><input type="text" id="certName_${certificationEntryCount}" name="certName[]" class="form-control"></div>
                <div class="col-md-6"><label for="certAuthority_${certificationEntryCount}" class="form-label">Issuing Authority</label><input type="text" id="certAuthority_${certificationEntryCount}" name="certAuthority[]" class="form-control"></div>
                <div class="col-md-6"><label for="certIssuedOn_${certificationEntryCount}" class="form-label">Issued On</label><input type="date" id="certIssuedOn_${certificationEntryCount}" name="certIssuedOn[]" class="form-control"></div>
                <div class="col-md-6"><label for="certDocument_${certificationEntryCount}" class="form-label">Upload Certificate</label><input type="file" id="certDocument_${certificationEntryCount}" name="certDocument[]" class="form-control" accept="image/*,.pdf"></div>
            `;
            container.appendChild(newEntry);
            certificationEntryCount++;
            if (certificationEntryCount === 1) { // Add a default entry if it's the first click
                this.click();
            }
        });
        
        // Dynamic Entry Addition for Work Experience
        function createWorkExperienceEntry() {
            const entryHtml = `
                <div class="work-experience-entry dynamic-entry card card-body mt-3">
                    <h5 class="card-title">Experience #${experienceEntryCount + 1}</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="expCompanyName_${experienceEntryCount}" class="form-label required">Company Name</label><input type="text" id="expCompanyName_${experienceEntryCount}" name="expCompanyName[]" class="form-control"></div>
                        <div class="col-md-6"><label for="expDesignation_${experienceEntryCount}" class="form-label required">Designation</label><input type="text" id="expDesignation_${experienceEntryCount}" name="expDesignation[]" class="form-control"></div>
                        <div class="col-md-6"><label for="expFromDate_${experienceEntryCount}" class="form-label required">From</label><input type="date" id="expFromDate_${experienceEntryCount}" name="expFromDate[]" class="form-control"></div>
                        <div class="col-md-6"><label for="expToDate_${experienceEntryCount}" class="form-label required">To</label><input type="date" id="expToDate_${experienceEntryCount}" name="expToDate[]" class="form-control"></div>
                        <div class="col-12"><label for="expReasonLeaving_${experienceEntryCount}" class="form-label required">Reason for leaving</label><input type="text" id="expReasonLeaving_${experienceEntryCount}" name="expReasonLeaving[]" class="form-control"></div>
                        <div class="col-md-6"><label for="expLetter_${experienceEntryCount}" class="form-label">Experience Letter</label><input type="file" id="expLetter_${experienceEntryCount}" name="expLetter[]" class="form-control" accept="image/*,.pdf"></div>
                    </div>
                </div>`;
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = entryHtml.trim();
            return tempDiv.firstChild;
        }
        
        document.getElementById('addExperienceBtn').addEventListener('click', function() {
            const container = document.getElementById('workExperienceEntriesContainer');
            container.appendChild(createWorkExperienceEntry());
            experienceEntryCount++;
        });

        // Dynamic Entry Addition for Language
        document.getElementById('addLanguageBtn').addEventListener('click', function() {
            const container = document.getElementById('languageEntriesContainer');
            const newEntry = document.createElement('div');
            newEntry.className = 'row g-3 language-entry align-items-end dynamic-entry';
            newEntry.innerHTML = `
                <div class="col-md-4"><label for="langName_${languageEntryCount}" class="form-label required">Language</label><input type="text" id="langName_${languageEntryCount}" name="langName[]" class="form-control"></div>
                <div class="col-md-8"><label class="form-label">Proficiency</label><div class="mt-1"><div class="form-check form-check-inline"><input type="checkbox" id="langSpeak_${languageEntryCount}" name="langProficiency_${languageEntryCount}[]" value="speak" class="form-check-input"><label for="langSpeak_${languageEntryCount}" class="form-check-label">Speak</label></div><div class="form-check form-check-inline"><input type="checkbox" id="langRead_${languageEntryCount}" name="langProficiency_${languageEntryCount}[]" value="read" class="form-check-input"><label for="langRead_${languageEntryCount}" class="form-check-label">Read</label></div><div class="form-check form-check-inline"><input type="checkbox" id="langWrite_${languageEntryCount}" name="langProficiency_${languageEntryCount}[]" value="write" class="form-check-input"><label for="langWrite_${languageEntryCount}" class="form-check-label">Write</label></div></div></div>
            `;
            container.appendChild(newEntry);
            languageEntryCount++;
        });

        // Same as Permanent Address Logic
        document.getElementById('sameAsPermanent').addEventListener('change', function() {
            const fields = [ 'BirthCountry', 'BirthState', 'BirthCity', 'Address1', 'Address2' ];
            if (this.checked) {
                fields.forEach(field => {
                    const permField = document.getElementById('perm' + field);
                    const presentField = document.getElementById('present' + field);
                    if (permField && presentField) {
                        presentField.value = permField.value;
                    }
                });
            } else {
                fields.forEach(field => {
                    const presentField = document.getElementById('present' + field);
                    if (presentField) presentField.value = '';
                });
            }
        });

        // Setup all conditional fields on document ready
        document.addEventListener('DOMContentLoaded', () => {
            // Document uploads
            setupConditionalField('panAvailable', 'panDetailsDiv');
            setupConditionalField('aadharAvailable', 'aadharDetailsDiv');
            setupConditionalField('dlAvailable', 'dlDetailsDiv');
            setupConditionalField('passportAvailable', 'passportDetailsDiv');

            // PF Account
    setupConditionalField('pfAccount', 'pfAccountDetailsDiv');
            
            // Marital status
            const maritalStatusSelect = document.getElementById('maritalStatus');
            const spouseSection = document.getElementById('spouseDetailsSection');
            const toggleSpouseSection = () => {
                const show = maritalStatusSelect.value === 'Married';
                spouseSection.style.display = show ? '' : 'none';
                spouseSection.querySelectorAll('input, select').forEach(input => {
                    if (show) input.setAttribute('required', 'required');
                    else input.removeAttribute('required');
                });
            };
            maritalStatusSelect.addEventListener('change', toggleSpouseSection);
            toggleSpouseSection();

            // Work Experience
            const pastExperienceRadios = document.querySelectorAll('input[name="pastExperience"]');
            const workExperienceDiv = document.getElementById('workExperienceDetailsDiv');
            const toggleWorkExperience = () => {
                const selected = document.querySelector('input[name="pastExperience"]:checked');
                const show = selected && selected.value === 'yes';
                workExperienceDiv.style.display = show ? '' : 'none';
                if(show && experienceEntryCount === 0) {
                   document.getElementById('addExperienceBtn').click();
                } else if (!show) {
                   document.getElementById('workExperienceEntriesContainer').innerHTML = '';
                   experienceEntryCount = 0;
                }
            };
            pastExperienceRadios.forEach(radio => radio.addEventListener('change', toggleWorkExperience));
            toggleWorkExperience();

            // Trigger initial dynamic entries if needed
            if(document.getElementById('certificationEntriesContainer').children.length === 0) {
                 document.getElementById('addCertificationBtn').click();
            }
        });
    </script>
</body>
</html>