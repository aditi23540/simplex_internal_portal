<?php
session_start(); // Start session to store login state and user info

// Configuration for calling the Python script
$python_executable = 'C:\Users\Administrator\AppData\Local\Programs\Python\Python313\python.exe'; // Full path from previous successful setup
$python_script_path = __DIR__ . '/ldap_auth.py'; // Assumes ldap_auth.py is in the same directory

// --- Database Configuration (for fetching department and role) ---
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "user_master_db"; 

// --- Main script execution: Handle POST request from the login form ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_from_form = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password_from_form = isset($_POST['password']) ? $_POST['password'] : ''; // Do NOT trim password

    if (empty($username_from_form) || empty($password_from_form)) {
        $_SESSION['login_error'] = 'Username and password are required.';
        header("Location: login.php");
        exit;
    }

    $escaped_username = escapeshellarg($username_from_form);
    $escaped_password = escapeshellarg($password_from_form);
    $command = $python_executable . ' ' . escapeshellcmd($python_script_path) . ' ' . $escaped_username . ' ' . $escaped_password;

    $output = @shell_exec($command);
    $auth_message = '';

    if ($output === null) {
        error_log("PHP Login Handler: shell_exec failed. Command: " . $command . ". Output was null. Check PHP error logs, shell_exec configuration, and script permissions/paths.");
        $_SESSION['login_error'] = 'Login service is currently unavailable. Please contact administrator. (Error: SE_NULL)';
        header("Location: login.php");
        exit;
    }

    $trimmed_output = trim($output);

    if (strpos($trimmed_output, 'SUCCESS:') === 0) {
        $parts = explode(':', $trimmed_output, 2);
        $empcode = isset($parts[1]) ? $parts[1] : null;

        if ($empcode !== null && $empcode !== '') {
            // LDAP Authentication Successful, now fetch department and role from DB
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username_from_form; 
            $_SESSION['empcode'] = $empcode;  

            // Initialize department and role with default values
            $_SESSION['department'] = 'N/A';
            $_SESSION['employee_role'] = 'N/A';

            $conn_db = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if ($conn_db->connect_error) {
                error_log("Database connection failed for role/dept fetch: " . $conn_db->connect_error);
                // Proceed with login but without role/dept if DB connection fails
            } else {
                $sql_hr_details = "SELECT department, employee_role FROM user_hr_details WHERE employee_id_ascent = ?";
                if ($stmt_hr = $conn_db->prepare($sql_hr_details)) {
                    $stmt_hr->bind_param("s", $empcode);
                    $stmt_hr->execute();
                    $result_hr = $stmt_hr->get_result();

                    if ($result_hr->num_rows > 0) {
                        $row_hr = $result_hr->fetch_assoc();
                        // Store them exactly as they are from DB for comparison, apply htmlspecialchars on display
                        $_SESSION['department'] = !empty($row_hr['department']) ? $row_hr['department'] : 'N/A';
                        $_SESSION['employee_role'] = !empty($row_hr['employee_role']) ? $row_hr['employee_role'] : 'N/A';
                    } else {
                        error_log("No HR details found for empcode: " . $empcode);
                    }
                    $stmt_hr->close();
                } else {
                    error_log("Failed to prepare SQL statement for HR details: " . $conn_db->error);
                }
                $conn_db->close();
            }

            // Conditional Redirection Logic
            $redirect_url = 'dashboard.php'; // Default redirect URL

            $role = $_SESSION['employee_role'];
            $department = $_SESSION['department'];

            // It's good practice to compare in a case-insensitive manner for roles/departments
            // if they might be entered with varying capitalization in the DB.
            // For this example, we'll assume exact case or handle it as needed.
            // Example: strtolower($role) == 'user'

            if ($role === 'USER') {
                if ($department === 'HRD') {
                    $redirect_url = '../USER/HRD_USER/index.php';
                } elseif ($department === 'purchase') {
                    $redirect_url = '../USER/PURCHASE_USER/index.php';
                } elseif ($department === 'IT') {
                    $redirect_url = '../USER/IT_USER/index.php';
                } else {
                    // User role with any other department (or N/A department)
                    $redirect_url = '../USER/NORMAL_USER/index.php';
                }
            } elseif ($role === 'ADMIN') {
                // Admin role with any department
                $redirect_url = '../ADMIN/dashboard/index.php';
            }
            // If role is N/A or not 'user' or 'admin', it will use the default 'dashboard.php'

            header("Location: " . $redirect_url);
            exit;
            
        } else {
            error_log("PHP Login Handler: Python script indicated SUCCESS but returned no empcode. Output: " . $trimmed_output);
            $auth_message = 'Authentication successful, but employee code was not retrieved.';
        }
    } else {
        // Handle different error messages from Python
        switch ($trimmed_output) {
            case 'AUTH_FAILED_BIND_ERROR':
                $auth_message = 'Authentication failed. Invalid username or password.';
                break;
            case 'AUTH_FAILED_NO_EMPCODE':
                $auth_message = 'Authentication successful, but employee code not found in LDAP.';
                break;
            case 'LDAP_ERROR':
                $auth_message = 'An LDAP error occurred. Please try again later or contact support.';
                break;
            case 'USAGE_ERROR_INVALID_ARGS':
                error_log("PHP Login Handler: Python script reported USAGE_ERROR_INVALID_ARGS. Command: " . $command);
                $auth_message = 'Login service configuration error. Please contact administrator. (Error: PY_ARGS)';
                break;
            case 'PYTHON_SCRIPT_ERROR':
                 error_log("PHP Login Handler: Python script reported PYTHON_SCRIPT_ERROR. Output: " . $trimmed_output . ". Command: " . $command);
                $auth_message = 'An unexpected error occurred in the login service. Please contact administrator. (Error: PY_GEN)';
                break;
            default:
                error_log("PHP Login Handler: Unexpected output from Python script: '" . $trimmed_output . "'. Command: " . $command);
                $auth_message = 'An unexpected error occurred during login. Please try again. (Error: PY_UNX)';
                break;
        }
    }

    // If we reached here, authentication failed or an error occurred
    $_SESSION['login_error'] = $auth_message;
    header("Location: login.php");
    exit;

} else {
    // If not a POST request, redirect to login page
    $_SESSION['login_error'] = 'Invalid request method.';
    header("Location: login.php");
    exit;
}
?>
