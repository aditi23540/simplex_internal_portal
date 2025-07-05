<?php 
session_set_cookie_params(['path' => '/']);
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['employee_role']) || strtolower($_SESSION['employee_role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$original_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$username_for_display = str_replace('.', ' ', $original_username);
$username_for_display = htmlspecialchars(ucwords(strtolower($username_for_display)));
$empcode = isset($_SESSION['empcode']) ? htmlspecialchars($_SESSION['empcode']) : 'N/A'; 
$user_email_placeholder = htmlspecialchars($original_username) . '@simplexengg.in'; 
$department_display = isset($_SESSION['department']) ? htmlspecialchars($_SESSION['department']) : 'N/A';
$employee_role_display = isset($_SESSION['employee_role']) ? htmlspecialchars($_SESSION['employee_role']) : 'N/A';

$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "user_master_db"; 
$avatar_path = "assets/img/kaiadmin/default-avatar.png"; 

if ($empcode !== 'N/A') {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$conn->connect_error) {
        $sql = "SELECT users.profile_picture_path FROM users JOIN user_hr_details ON users.user_id = user_hr_details.user_id WHERE user_hr_details.employee_id_ascent = ?";
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
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SIMPLEX INTERNAL PORTAL - Dashboard</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/css/plugins.min.css" />
  <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
  <style>
    .logo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 15px;
    }
    .logo-tile {
      background: #fff;
      border: 2px solid #ddd;
      padding: 20px;
      border-radius: 12px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      text-align: center;
      height: 200px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .logo-tile:hover {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      transform: scale(1.05);
      border-color: #007BFF;
    }
    .logo-tile img {
      max-width: 100%;
      height: 80px;
      object-fit: contain;
      margin-bottom: 10px;
    }
    .logo-tile span {
      display: block;
      font-weight: bold;
      color: #333;
      font-size: 16px;
    }
    .logo-tile a {
      text-decoration: none;
      color: inherit;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <!-- Begin Full Static Header -->
    <div class="main-header">
      <nav class="navbar navbar-header navbar-expand-lg" data-background-color="dark">
        <div class="container-fluid">
          <div class="navbar-brand">
            <a href="dashboard.php">
              <img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" height="40">
              <span class="text-white ms-2">Simplex Engineering & Foundry Works</span>
            </a>
          </div>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo htmlspecialchars($avatar_path); ?>" class="rounded-circle" width="40" height="40">
                <span class="text-white ms-2">Hi, <?php echo $username_for_display; ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="#">My Profile</a></li>
                <li><a class="dropdown-item" href="#">Account Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../../LOGIN/logout.php">Logout</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>
    </div>
    <!-- End Full Static Header -->

    <div class="main-panel">
      <div class="container">
        <div class="page-inner">
          <div class="text-center mt-4">
            <h1 class="page-header-title">Welcome to the Dashboard, <?php echo $username_for_display; ?>!</h1>
            <p>Select a logo to access the corresponding website.</p>
          </div>

          <div class="logo-grid">
            <?php
            $companies = [
              ['name' => 'Apple', 'url' => 'https://www.apple.com', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg'],
              ['name' => 'Microsoft', 'url' => 'https://www.microsoft.com', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg'],
              ['name' => 'Google', 'url' => 'https://www.google.com', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg'],
              ['name' => 'Amazon', 'url' => 'https://www.amazon.com', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg']
            ];

            foreach ($companies as $company) {
              echo '<div class="logo-tile">
                      <a href="' . htmlspecialchars($company['url']) . '" target="_blank">
                        <img src="' . htmlspecialchars($company['logo']) . '" alt="' . htmlspecialchars($company['name']) . '">
                        <span>' . htmlspecialchars($company['name']) . '</span>
                      </a>
                    </div>';
            }
            ?>
          </div>
        </div>
      </div>

      <footer class="footer bg-dark text-white py-3 mt-5">
        <div class="container d-flex justify-content-between">
          <span>&copy; <?php echo date('Y'); ?> SIMPLEX - All Rights Reserved.</span>
          <span>Developed by <a href="#" class="text-info">Abhimanyu</a></span>
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
