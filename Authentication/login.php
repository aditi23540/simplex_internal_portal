<?php session_start(); // Required for session-based error messages ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Login - SIMPLEX INTERNAL PORTAL</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../Central_Asset_Library/img/Simplex_Logo/simplex_icon.ico" type="image/x-icon" />

    <script src="../Central_Asset_Library/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
          urls: ["../Central_Asset_Library/css/fonts.min.css"],
        },
        active: function () { sessionStorage.fonts = true; },
      });
    </script>

    <link rel="stylesheet" href="../Central_Asset_Library/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../Central_Asset_Library/css/kaiadmin.min.css" /> <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: "Public Sans", sans-serif; /* Ensure consistent font */
        }
        body.login-page-body {
            background-color: #f4f5f8; /* A common light background for login pages */
            display: flex;
            flex-direction: column;
            color: #575962; /* Default text color */
        }

        /* Minimal Header for Login Page */
        .login-header-minimal {
            background-color: #ffffff;
            padding: 1rem 0;
            border-bottom: 1px solid #ebedf2; /* Subtle border */
            text-align: center;
            flex-shrink: 0; /* Prevent header from shrinking */
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); /* Subtle shadow */
        }
        .login-header-minimal .navbar-brand-wrapper {
            display: inline-flex; /* Keeps logo and text together when centered */
            align-items: center;
            text-decoration: none;
            color: #333740; /* Darker text for branding */
        }
        .login-header-minimal .brand-logo {
            height: 70px;
            margin-right: 10px;
        }
        .login-header-minimal .brand-text {
            font-size: 2.2rem;
            font-weight: 500;
            white-space: nowrap;
        }

        /* Main Content Area - Centers the Login Form */
        .login-main-content {
            flex-grow: 1; /* Takes up available vertical space */
            display: flex;
            align-items: center; /* Vertically centers the card */
            justify-content: center; /* Horizontally centers the card */
            padding: 20px; /* Padding around the card container */
        }
        .login-card-minimal {
            width: 100%;
            max-width: 420px; /* Slightly wider card */
            border: none;
            border-radius: 0.5rem; /* Softer corners */
            box-shadow: 0 0 45px 0 rgba(0,0,0,0.1); /* Adjusted shadow */
        }
        .login-card-minimal .card-header {
            background-color: transparent;
            border-bottom: none;
            padding-top: 2rem;
            padding-bottom: 0.5rem; /* Reduced space below header text */
        }
        .login-card-minimal .card-header img {
            max-height: 70px;
        }
        .login-card-minimal .card-body {
            padding: 1.5rem 2rem 2rem 2rem; /* Adjusted padding */
        }
        .login-card-minimal h4 {
            font-weight: 500; /* Less bold heading in card */
            margin-top: 0.5rem;
        }

        /* Footer for Login Page */
        .login-page-footer {
            background-color: #ffffff;
            border-top: 1px solid #ebedf2;
            padding: 1rem 15px; /* Consistent padding */
            text-align: center;
            flex-shrink: 0; /* Prevent footer from shrinking */
            font-size: 0.875rem;
            color: #575962;
        }
        .login-page-footer .container-fluid {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            justify-content: space-between;
            align-items: center;
        }
        .login-page-footer .copyright-text,
        .login-page-footer .company-link-text {
             margin: 0.25rem 0.5rem; /* Spacing for footer items */
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) { /* Medium devices and down */
            .login-header-minimal .brand-text {
                font-size: 1rem; /* Adjust brand text size */
            }
             .login-header-minimal .brand-logo {
                height: 28px;
            }
            .login-main-content {
                align-items: flex-start; /* Align card to top on mobile for better scroll */
                padding-top: 2rem;
            }
        }
        @media (max-width: 575.98px) { /* Small devices */
            .login-header-minimal .brand-text {
                font-size: 0.9rem;
            }
            .login-header-minimal .brand-logo {
                height: 24px;
                margin-right: 8px;
            }
            .login-card-minimal .card-body, .login-card-minimal .card-header {
                padding: 1.25rem;
            }
            .login-card-minimal h4 {
                font-size: 1.15rem;
            }
            .login-page-footer .container-fluid {
                justify-content: center; /* Center footer items if they stack */
                flex-direction: column;
            }
            .login-page-footer .copyright-text {
                order: 2; /* Put copyright below company link on mobile stack */
            }
            .login-page-footer .company-link-text {
                order: 1;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="login-page-body">

    <header class="login-header-minimal">
        <div class="navbar-brand-wrapper">
            <img src="../Central_Asset_Library/img/Simplex_Logo/simplex_icon.ico" alt="Simplex Logo" class="brand-logo" />
            <span class="brand-text">Simplex Engineering and Foundary Work</span>
        </div>
    </header>

    <main class="login-main-content">
        <div class="card login-card-minimal">
            <div class="card-header text-center">
                <img src="../Central_Asset_Library/img/Simplex_Logo/simplex_icon.ico" alt="Portal Login Logo" class="mb-2" >
                <h4>Internal Portal Login</h4>
            </div>
            <div class="card-body">
                <?php
                // Display login errors if any
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                         htmlspecialchars($_SESSION['login_error']) .
                         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    unset($_SESSION['login_error']); // Clear the error after displaying
                }
                ?>
                <form id="loginPageForm" action="login_handler.php" method="POST">
                    <div class="form-group mb-3">
                        <label for="loginUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="loginUsername" name="username" required placeholder="Enter your username">
                    </div>
                    <div class="form-group mb-4">
                        <label for="loginPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="loginPassword" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </main>

    <footer class="login-page-footer">
        <div class="container-fluid">
            <div class="pull-left">
                 </div>
                 <?php $current_year = date("Y"); ?>
        <div class="copyright-text">
                
        <?php echo $current_year;?>, made with <i class="fa fa-heart heart text-danger"></i> by
                <a href="my portfolio website">Abhimanyu Banerjee (IT Department)</a>
            </div>
            <div class="company-link-text">
                For
                <a target="_blank" href="https://www.simplexengg.in/home/">
                    Simplex Enginnering & Foundary work PVT. LTD.
                </a>
            </div>
        </div>
    </footer>

    <script src="../Central_Asset_Library/js/core/jquery-3.7.1.min.js"></script>
    <script src="../Central_Asset_Library/js/core/popper.min.js"></script>
    <script src="../Central_Asset_Library/js/core/bootstrap.min.js"></script>
    <script src="../Central_Asset_Library/js/kaiadmin.min.js"></script>
    </body>
</html>