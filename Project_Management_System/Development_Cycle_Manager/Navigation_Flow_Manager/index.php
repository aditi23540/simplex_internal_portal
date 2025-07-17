<?php
session_set_cookie_params(['path' => '/']);
session_start(); // Start the session at the very beginning

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../../LOGIN/login.php"); // Adjust path to your main login page
    exit;
}

// =================== ADMIN ACCESS CHECK ===================
// After checking login, verify the user role is 'ADMIN'.
// This check is case-insensitive (handles 'ADMIN', 'Admin', or 'admin').
if (!isset($_SESSION['employee_role']) || strtolower($_SESSION['employee_role']) !== 'admin') {
    // If the role is not 'admin', deny access by redirecting.
    header("Location: ../../LOGIN/login.php"); // Or a permission denied page, adjust path
    exit;
}
// =================== END OF ADMIN ACCESS CHECK ===================

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


// --- Database Configuration for User Master (for avatar) ---
// This connection is to your main user_master_db for avatar retrieval
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "user_master_db"; 

// Default avatar path (relative to this new project's root)
$avatar_path = "../assets/img/kaiadmin/default-avatar.png"; // Adjusted path

if ($empcode !== 'N/A') {
    // Create a database connection for user_master_db
    $user_master_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($user_master_conn->connect_error) {
        // error_log("Database connection failed: " . $user_master_conn->connect_error);
    } else {
        // Prepare SQL statement
        $sql = "SELECT users.profile_picture_path
                FROM users
                JOIN user_hr_details ON users.user_id = user_hr_details.user_id
                WHERE user_hr_details.employee_id_ascent = ?";
        
        if ($stmt = $user_master_conn->prepare($sql)) {
            $stmt->bind_param("s", $empcode); 
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $db_avatar_path = $row['profile_picture_path']; 
                
                if (!empty($db_avatar_path)) {
                    // This path is relative to the main 'registration_project'
                    // We need to adjust it to be relative to this 'Navigation_Flow_Manager' project
                    $avatar_path = "../../registration_project/".$db_avatar_path; // Adjusted path
                }
            }
            $stmt->close();
        } else {
            // error_log("Failed to prepare SQL statement: " . $user_master_conn->error);
        }
        $user_master_conn->close();
    }
}

// --- Include CRUD Application Configuration for THIS project ---
// This file contains database credentials and establishes the $crud_conn
require_once 'config.php'; 

// --- Helper Functions for CRUD App ---

/**
 * Sanitize input string to prevent XSS.
 * @param string $data The input string.
 * @return string The sanitized string.
 */
function sanitize_crud_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Uploads a file to the specified directory.
 * @param array $file The $_FILES array for the uploaded file.
 * @param string $target_dir The directory to upload the file to.
 * @param array $allowed_types An array of allowed file extensions (e.g., ['jpg', 'png']).
 * @return string|false The path to the uploaded file relative to the script, or false on failure.
 */
function upload_crud_file($file, $target_dir, $allowed_types) {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false; // No file uploaded or an error occurred
    }

    // Ensure the target directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create directory recursively with full permissions
    }

    $file_name = basename($file['name']);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file type is allowed
    if (!in_array($file_type, $allowed_types)) {
        echo "<p class='text-red-500'>Error: Invalid file type for " . htmlspecialchars($file['name']) . ".</p>";
        return false;
    }

    // Generate a unique file name to prevent overwriting
    $unique_file_name = uniqid() . '_' . $file_name;
    $target_unique_file = $target_dir . $unique_file_name;

    if (move_uploaded_file($file['tmp_name'], $target_unique_file)) {
        // Return path relative to the application root for database storage
        // This assumes the script is in the root of Navigation_Flow_Manager/ and uploads/ is relative to it
        return rtrim($target_dir, '/') . '/' . $unique_file_name;
    } else {
        echo "<p class='text-red-500'>Error uploading file " . htmlspecialchars($file['name']) . ".</p>";
        return false;
    }
}

// --- CRUD Operations Logic ---

// Set default action to 'list' to show CRUD table by default
$action = $_GET['action'] ?? 'list'; 
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions for CRUD app
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_ids = sanitize_crud_input($_POST['request_ids'] ?? '');
    $planning_ids = sanitize_crud_input($_POST['planning_ids'] ?? '');
    $module = sanitize_crud_input($_POST['module'] ?? '');
    $submodule = sanitize_crud_input($_POST['submodule'] ?? '');

    // Basic validation
    if (empty($request_ids) || empty($planning_ids) || empty($module) || empty($submodule)) {
        $error = "All text fields are required.";
    } else {
        if ($action === 'add_record') {
            $image_path = '';

            // Handle file upload (only .drawio allowed)
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                // MODIFIED: Only 'drawio' allowed for upload
                $image_path = upload_crud_file($_FILES['file'], NAVIGATION_FLOW_UPLOAD_DIR, ['drawio']); 
                if ($image_path === false) {
                    $error = "File upload failed.";
                }
            } else if ($_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                 $error = "File upload error: " . $_FILES['file']['error'];
            }

            if (empty($error)) {
                // Table name 'navigation_flow_tracker'
                $stmt = $crud_conn->prepare("INSERT INTO navigation_flow_tracker (request_ids, planning_ids, module, submodule, image_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $request_ids, $planning_ids, $module, $submodule, $image_path);

                if ($stmt->execute()) {
                    $message = "Record added successfully!";
                    header("Location: index.php?action=list&message=" . urlencode($message)); // Redirect to self
                    exit();
                } else {
                    $error = "Error adding record: " . $stmt->error;
                }
                $stmt->close();
            }

        } elseif ($action === 'edit_record' && $id) {
            $current_record = null;
            // Table name 'navigation_flow_tracker'
            $stmt_select = $crud_conn->prepare("SELECT image_path FROM navigation_flow_tracker WHERE id = ?");
            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            if ($result_select->num_rows > 0) {
                $current_record = $result_select->fetch_assoc();
            }
            $stmt_select->close();

            $image_path = $current_record['image_path'] ?? null;

            // Handle new file upload (if provided)
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                // MODIFIED: Only 'drawio' allowed for upload
                $new_image_path = upload_crud_file($_FILES['file'], NAVIGATION_FLOW_UPLOAD_DIR, ['drawio']); 
                if ($new_image_path !== false) {
                    // Delete old file if it exists
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $new_image_path;
                } else {
                    $error = "New file upload failed.";
                }
            } else if ($_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
                 $error = "File upload error: " . $_FILES['file']['error'];
            }

            if (empty($error)) {
                // Table name 'navigation_flow_tracker'
                $stmt = $crud_conn->prepare("UPDATE navigation_flow_tracker SET request_ids = ?, planning_ids = ?, module = ?, submodule = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $request_ids, $planning_ids, $module, $submodule, $image_path, $id);

                if ($stmt->execute()) {
                    $message = "Record updated successfully!";
                    header("Location: index.php?action=list&message=" . urlencode($message)); // Redirect to self
                    // Do not exit here, allow the rest of the HTML to render.
                } else {
                    $error = "Error updating record: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Handle GET requests for actions
if (isset($_GET['message'])) {
    $message = sanitize_crud_input($_GET['message']);
}
if (isset($_GET['error'])) {
    $error = sanitize_crud_input($_GET['error']);
}

if ($action === 'delete' && $id) {
    $record_to_delete = null;
    // Table name 'navigation_flow_tracker'
    $stmt_select = $crud_conn->prepare("SELECT image_path FROM navigation_flow_tracker WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($result_select->num_rows > 0) {
        $record_to_delete = $result_select->fetch_assoc(); 
    }
    $stmt_select->close();

    if ($record_to_delete) {
        // Table name 'navigation_flow_tracker'
        $stmt = $crud_conn->prepare("DELETE FROM navigation_flow_tracker WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Delete associated file from server
            if ($record_to_delete['image_path'] && file_exists($record_to_delete['image_path'])) {
                unlink($record_to_delete['image_path']);
            }
            $message = "Record deleted successfully!";
        } else {
            $error = "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Record not found for deletion.";
    }
    header("Location: index.php?action=list&message=" . urlencode($message) . "&error=" . urlencode($error)); // Redirect to self
    exit();
}

// Fetch record for editing or viewing
$record_data = null;
if (($action === 'edit' || $action === 'view') && $id) {
    // Table name 'navigation_flow_tracker'
    $stmt = $crud_conn->prepare("SELECT * FROM navigation_flow_tracker WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $record_data = $result->fetch_assoc();
    } else {
        $error = "Record not found.";
        $action = 'list'; // Redirect to list if record not found
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Navigation Flow Manager</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="../assets/img/kaiadmin/simplex_icon.ico"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
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
          urls: ["../assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <!-- Tailwind CSS CDN for CRUD app styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">


    <!-- Custom CSS for dashboard content and integrated CRUD app -->
    <style>
      .page-inner .table th {
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

        /* --- Custom styles for the integrated CRUD app (from original CRUD app) --- */
        body {
            font-family: 'Inter', sans-serif; /* This will override dashboard font for CRUD section if Inter is loaded last */
            /* background-color: #f3f4f6; Removed as dashboard body background is handled */
        }
        /* .container { max-width: 1000px; } Removed to use dashboard's container */
        
        /* Enhanced form input styling for better visibility */
        .form-input {
            display: block;
            width: 100%;
            padding: 6px 10px;
            font-size: 13px;
            line-height: 1.4;
            color: #111827;
            background-color: #ffffff;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* File input specific styling */
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            color: #111827;
            background-color: #ffffff;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
        }
        
        input[type="file"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Button styling */
        .btn {
            @apply font-semibold py-2 px-4 rounded-md transition duration-200 ease-in-out text-sm;
        }
        .btn-primary {
            @apply bg-blue-600 hover:bg-blue-700 text-white shadow-sm;
        }
        .btn-success {
            @apply bg-green-600 hover:bg-green-700 text-white shadow-sm;
        }
        .btn-warning {
            @apply bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm;
        }
        .btn-danger {
            @apply bg-red-600 hover:bg-red-700 text-white shadow-sm;
        }
        .btn-info {
             @apply bg-gray-500 hover:bg-gray-600 text-white shadow-sm;
        }
        
        /* Custom button styles */
        .btn-test {
            background-color: #22c55e;
            color: white !important;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: inline-block;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .btn-test:hover {
            background-color: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: white !important;
            text-decoration: none;
        }
        
        .btn-add-record {
            background-color: #3b82f6;
            color: white !important;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: inline-block;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .btn-add-record:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: white !important;
            text-decoration: none;
        }
        
        /* Table styling */
        .table-header th {
            @apply px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
        }
        .table-row td {
            @apply px-4 py-3 whitespace-nowrap text-sm text-gray-900;
        }
        
        /* Label styling */
        .form-label {
            display: block;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        /* Read-only field styling */
        .form-input.readonly {
            background-color: #f9fafb;
            border: 2px solid #e5e7eb;
            color: #374151;
            cursor: not-allowed;
        }

        /* Modal specific styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            overflow-y: auto;
            padding: 40px 20px;
        }
        .modal-content {
            background-color: #fff;
            padding: 1.25rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
            margin: auto;
        }
        .modal-close-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #6b7280;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .modal-close-btn:hover {
            color: #374151;
            background-color: #f3f4f6;
        }
        
        /* Form grid layout for better organization */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        /* Compact spacing for form elements */
        .form-group {
            margin-bottom: 0.5rem;
        }
        
        /* File upload area styling */
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 6px;
            padding: 0.75rem;
            text-align: center;
            background-color: #f9fafb;
            transition: all 0.2s;
        }
        .file-upload-area:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        /* Current file display */
        .current-file {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
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
            <a href="index.php" class="logo"> 
              <img
                src="../assets/img/kaiadmin/simplex_icon_2.png"
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
              <li class="nav-item <?php echo ($action === 'list' || $action === 'add' || $action === 'edit' || $action === 'view') ? 'active' : ''; ?>">
                <a href="index.php?action=list">
                  <i class="fas fa-sitemap"></i> <!-- Changed icon for navigation flow -->
                  <p>Navigation Flow Records</p>
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
              <a href="index.php" class="logo"> 
                <img
                  src="../assets/img/kaiadmin/logo_light.svg" 
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
                <a href="index.php" 
                   style="display: flex; align-items: center; text-decoration: none; color: #333;">
                  <img src="../assets/img/kaiadmin/simplex_icon.ico" 
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
                        onerror="this.onerror=null; this.src='../assets/img/kaiadmin/default-avatar.png';" 
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
                              onerror="this.onerror=null; this.src='../assets/img/kaiadmin/default-avatar.png';"
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
                        <a class="dropdown-item" href="../../registration_project/my_profile.php">My Profile</a>
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
            <?php if ($action === 'dashboard'): // Only show welcome message for dashboard action (though not used in this standalone app) ?>
              <div class="welcome-message">
                <h1 class="page-header-title mt-4">Welcome to the Navigation Flow Manager, <?php echo $username_for_display; ?>!</h1> 
                <p>Use the navigation to manage navigation flow records.</p>
              </div>
            <?php else: // Display CRUD content ?>
                <div class="bg-white p-6 rounded-lg shadow-xl mt-8">
                    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Navigation Flow Management System</h1>
                    
                    <!-- Buttons Row -->
                    <div class="flex justify-between items-center mb-4">
                        <!-- MODIFIED: Changed button text and href for download -->
                        <a href="uploads/navigation_flow_files/standard_navigation_template.drawio" download class="btn btn-test">Download Standard Template</a>
                        <?php if ($action === 'list'): ?>
                            <a href="index.php?action=add" target="_self" class="btn btn-add-record" onclick="toggleSidebar(true)">Add New Navigation Flow Record</a>
                        <?php endif; ?>
                    </div>

                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Success!</strong>
                            <span class="block sm:inline"><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                        <?php
                        // Table name 'navigation_flow_tracker'
                        $sql = "SELECT * FROM navigation_flow_tracker ORDER BY created_at DESC";
                        $result = $crud_conn->query($sql);

                        if ($result->num_rows > 0):
                        ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-50">
                                        <tr class="table-header">
                                            <th>ID</th>
                                            <th>Request IDs</th>
                                            <th>Planning IDs</th>
                                            <th>Module</th>
                                            <th>Submodule</th>
                                            <th>File</th> <!-- Changed from Image to File -->
                                            <th>Created At</th>
                                            <th>Updated At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr class="table-row">
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['request_ids']); ?></td>
                                                <td><?php echo htmlspecialchars($row['planning_ids']); ?></td>
                                                <td><?php htmlspecialchars($row['module']); ?></td>
                                                <td><?php echo htmlspecialchars($row['submodule']); ?></td>
                                                <td>
                                                    <?php if ($row['image_path'] && file_exists($row['image_path'])): 
                                                        $file_extension = strtolower(pathinfo($row['image_path'], PATHINFO_EXTENSION));
                                                        $link_attributes = 'download'; // Always force download for .drawio files
                                                    ?>
                                                        <a href="<?php echo htmlspecialchars($row['image_path']); ?>" <?php echo $link_attributes; ?> class="text-blue-600 hover:underline">Download File</a>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row['created_at']; ?></td>
                                                <td><?php echo $row['updated_at']; ?></td>
                                                <td class="flex space-x-2">
                                                    <a href="index.php?action=view&id=<?php echo $row['id']; ?>" class="btn btn-info text-sm" onclick="toggleSidebar(true)">View</a>
                                                    <a href="index.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-warning text-sm" onclick="toggleSidebar(true)">Edit</a>
                                                    <a href="index.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger text-sm" onclick="return confirm('Are you sure you want to delete this record and its associated file?');">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-gray-600">No navigation flow records found. Start by adding a new one!</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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

    <!-- The Modal Structure -->
    <div id="crudModal" class="modal <?php echo ($action === 'add' || $action === 'edit' || $action === 'view') ? 'flex' : 'hidden'; ?>">
        <div class="modal-content">
            <button class="modal-close-btn" onclick="closeModal()">×</button>
            <h2 class="text-lg font-semibold text-gray-800 mb-3">
                <?php
                    if ($action === 'add') echo 'Add New Navigation Flow Record';
                    elseif ($action === 'edit') echo 'Edit Navigation Flow Record (ID: ' . htmlspecialchars($id) . ')';
                    elseif ($action === 'view') echo 'View Navigation Flow Record (ID: ' . htmlspecialchars($id) . ')';
                ?>
            </h2>

            <form method="POST" action="index.php?action=<?php echo ($action === 'add' ? 'add_record' : 'edit_record'); ?><?php echo ($id ? '&id=' . htmlspecialchars($id) : ''); ?>" enctype="multipart/form-data">
                <?php if ($action === 'view'): ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Request IDs:</label>
                            <div class="form-input readonly"><?php echo htmlspecialchars($record_data['request_ids'] ?? ''); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Planning IDs:</label>
                            <div class="form-input readonly"><?php echo htmlspecialchars($record_data['planning_ids'] ?? ''); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Module:</label>
                            <div class="form-input readonly"><?php echo htmlspecialchars($record_data['module'] ?? ''); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Submodule:</label>
                            <div class="form-input readonly"><?php echo htmlspecialchars($record_data['submodule'] ?? ''); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Navigation Flow File:</label> <!-- Changed label -->
                            <?php if ($record_data['image_path'] && file_exists($record_data['image_path'])): 
                                $file_extension = strtolower(pathinfo($record_data['image_path'], PATHINFO_EXTENSION));
                                $link_attributes = 'download'; // Always force download for .drawio files
                            ?>
                                <div class="current-file">
                                    <a href="<?php echo htmlspecialchars($record_data['image_path']); ?>" <?php echo $link_attributes; ?> class="text-blue-600 hover:underline">Download File</a>
                                </div>
                                <!-- No image preview for .drawio files -->
                            <?php else: ?>
                                <div class="current-file text-gray-500">No file uploaded.</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created At:</label>
                            <div class="form-input readonly"><?php echo htmlspecialchars($record_data['created_at'] ?? ''); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Updated At:</label>
                            <div class="form-input readonly"><?php echo htmlspecialchars($record_data['updated_at'] ?? ''); ?></div>
                        </div>
                    </div>
                <?php else: // Add or Edit form ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="request_ids" class="form-label">Request IDs (comma-separated):</label>
                            <input type="text" id="request_ids" name="request_ids" class="form-input" value="<?php echo htmlspecialchars($record_data['request_ids'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="planning_ids" class="form-label">Planning IDs (comma-separated):</label>
                            <input type="text" id="planning_ids" name="planning_ids" class="form-input" value="<?php echo htmlspecialchars($record_data['planning_ids'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="module" class="form-label">Module:</label>
                            <input type="text" id="module" name="module" class="form-input" value="<?php echo htmlspecialchars($record_data['module'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="submodule" class="form-label">Submodule:</label>
                            <input type="text" id="submodule" name="submodule" class="form-input" value="<?php echo htmlspecialchars($record_data['submodule'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="file" class="form-label">Upload Navigation Flow File (.drawio):</label> <!-- Changed label and input name -->
                            <div class="file-upload-area">
                                <!-- MODIFIED: Only .drawio allowed in accept attribute, input name changed to 'file' -->
                                <input type="file" id="file" name="file" accept=".drawio">
                            </div>
                            <?php if ($action === 'edit' && $record_data['image_path'] && file_exists($record_data['image_path'])): 
                                $file_extension = strtolower(pathinfo($record_data['image_path'], PATHINFO_EXTENSION));
                            ?>
                                <div class="current-file">
                                    Current File: <a href="<?php echo htmlspecialchars($record_data['image_path']); ?>" target="_blank" class="text-blue-600 hover:underline">View</a> (Upload new to replace)
                                </div>
                                <!-- No image preview for .drawio files -->
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-200">
                        <button type="submit" class="btn btn-success">
                            <?php echo ($action === 'add' ? 'Add Record' : 'Update Record'); ?>
                        </button>
                        <a href="index.php?action=list" class="btn btn-info" onclick="closeModal()">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../assets/js/kaiadmin.min.js"></script>
    <script>
        // Function to toggle sidebar state
        function toggleSidebar(collapse) {
            const wrapper = document.querySelector('.wrapper');
            if (collapse) {
                wrapper.classList.add('sidebar_minimize');
            } else {
                wrapper.classList.remove('sidebar_minimize');
            }
        }

        // Function to close the modal and expand sidebar
        function closeModal() {
            toggleSidebar(false); // Expand sidebar
            window.location.href = 'index.php?action=list'; // Redirect to list view
        }

        // Check initial action on page load to collapse sidebar if modal is open
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            if (action === 'add' || action === 'edit' || action === 'view') {
                toggleSidebar(true); // Collapse sidebar if modal is active
            }
        });
    </script>
  </body>
</html>
<?php
// Close CRUD database connection
$crud_conn->close();
?>
