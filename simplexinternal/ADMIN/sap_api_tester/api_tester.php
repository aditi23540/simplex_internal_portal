<?php
// /api_tester.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Your SAP API Interaction Logic (acts as a backend) ---
define('SAP_USERNAME_TEST', 'ZNOVELSH412439');
define('SAP_PASSWORD_TEST', 'PEWMsSS$Pv3TSonFJlFUYJiEmcCfVmXcpzaMfeHw');

function sapCurl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, SAP_USERNAME_TEST . ":" . SAP_PASSWORD_TEST);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/xml']);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function sapCurlJson($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, SAP_USERNAME_TEST . ":" . SAP_PASSWORD_TEST);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) return false;
    return json_decode($response, true);
}

if (isset($_GET['getEntities'])) {
    header('Content-Type: application/json');
    $baseUrl = rtrim($_GET['apiUrl'], '/');
    $url = $baseUrl . '/$metadata';
    $xml = sapCurl($url);
    if (!$xml) exit(json_encode(["entitySets" => []]));
    $entitySets = [];
    $xmlObj = simplexml_load_string($xml);
    $xmlObj->registerXPathNamespace('edmx', 'http://schemas.microsoft.com/ado/2007/06/edmx');
    $xmlObj->registerXPathNamespace('edm', 'http://schemas.microsoft.com/ado/2008/09/edm');
    foreach ($xmlObj->xpath('//edm:EntitySet') as $entitySet) { $entitySets[] = (string) $entitySet['Name']; }
    echo json_encode(["entitySets" => $entitySets]);
    exit;
}

if (isset($_GET['getAttributes'])) {
    header('Content-Type: application/json');
    $baseUrl = rtrim($_GET['apiUrl'], '/');
    $url = $baseUrl . '/$metadata';
    $entitySet = $_GET['entitySet'];
    $xml = sapCurl($url);
    if (!$xml) exit(json_encode(["attributes" => []]));
    $attributes = [];
    $xmlObj = simplexml_load_string($xml);
    $xmlObj->registerXPathNamespace('edmx', 'http://schemas.microsoft.com/ado/2007/06/edmx');
    $xmlObj->registerXPathNamespace('edm', 'http://schemas.microsoft.com/ado/2008/09/edm');
    $entityType = null;
    foreach ($xmlObj->xpath("//edm:EntitySet[@Name='$entitySet']") as $entitySetNode) { $entityType = (string)$entitySetNode['EntityType']; }
    if (!$entityType) exit(json_encode(["attributes" => []]));
    $entityType = explode('.', $entityType)[1];
    foreach ($xmlObj->xpath("//edm:EntityType[@Name='$entityType']/edm:Property") as $property) { $attributes[] = (string)$property['Name']; }
    echo json_encode(["attributes" => $attributes]);
    exit;
}

if (isset($_GET['getTotalCount'])) {
    header('Content-Type: application/json');
    $baseUrl = rtrim($_GET['apiUrl'], '/');
    $entitySet = $_GET['entitySet'];
    $url = "$baseUrl/$entitySet/\$count";
    $count = sapCurl($url);
    echo json_encode(["count" => intval($count)]);
    exit;
}

if (isset($_GET['fetchPage'])) {
    header('Content-Type: application/json');
    $baseUrl = rtrim($_GET['apiUrl'], '/');
    $entitySet = $_GET['entitySet'];
    $attributes = explode(",", $_GET['attributes']);
    $select = implode(",", $attributes);
    $page = intval($_GET['page']);
    $limit = intval($_GET['limit']);
    $skip = ($page - 1) * $limit;
    $url = "$baseUrl/$entitySet?\$format=json&\$top=$limit&\$skip=$skip&\$select=$select";
    $json = sapCurlJson($url);
    $data = isset($json['d']['results']) ? $json['d']['results'] : [];
    echo json_encode(["records" => $data]);
    exit;
}

// --- Main template user info logic for header/sidebar ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header("Location: login.php"); exit; }
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

$page_specific_title = "SAP API Tester";
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
        #tableContainer { max-height: 60vh; overflow: auto; border: 1px solid #dee2e6; }
        #resultTable { width: max-content; min-width: 100%; }
        #resultTable th, #resultTable td { white-space: nowrap; padding: 0.5rem; font-size: 0.8rem; }
        .attr-box { border:1px solid #ccc; padding:5px 10px; margin:5px; display:inline-block; border-radius: 5px; cursor: pointer; user-select: none;}
        .attr-box:hover { background-color: #f0f0f0; }
        .attr-box input { margin-right: 5px; }
    </style>
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
                          <a href="../registration_project/hr_update_requests.php" target="_self">
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
                                <ul class="dropdown-menu dropdown-user animated fadeIn"><div class="dropdown-user-scroll scrollbar-outer"><li><div class="user-box"><div class="avatar-lg"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="image profile" class="avatar-img rounded"/></div><div class="u-text"><h4><?php echo $username_for_display; ?></h4><p class="text-muted"><?php echo $user_email_placeholder; ?></p><p class="text-muted">Emp Code: <?php echo $empcode; ?></p></div></div></li><li><div class="dropdown-divider"></div><a class="dropdown-item" href="#">My Profile</a><div class="dropdown-divider"></div><a class="dropdown-item" href="logout.php">Logout</a></li></div></ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <div class="container">
                <div class="page-inner">
                     <div class="card">
                        <div class="card-header">
                            <h2 class="card-title fw-bold">SAP OData API Tester</h2>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="apiUrl" class="form-label">SAP API URL</label>
                                <input type="text" id="apiUrl" class="form-control" placeholder="Enter SAP API URL" >
                            </div>
                            <button class="btn btn-primary" id="fetchEntities">Fetch Entity Sets</button>
                            
                            <div id="entitySection" class="mt-4" style="display:none;">
                                <label for="entitySet" class="form-label">Select Entity Set</label>
                                <div class="input-group">
                                    <select id="entitySet" class="form-select"></select>
                                    <button class="btn btn-secondary" id="loadAttributes">Load Attributes</button>
                                </div>
                            </div>

                            <div id="attributeSection" class="mt-4" style="display:none;">
                                <label class="form-label">Select Attributes to Load</label>
                                <div id="attributeList" class="mb-2 p-3 border rounded bg-light"></div>
                                <button class="btn btn-success" id="loadData">Load Data</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card" id="resultsCard" style="display:none;">
                        <div class="card-header"><h4 class="card-title">Results</h4></div>
                        <div class="card-body">
                             <div class="progress my-3" style="display:none;">
                                <div class="progress-bar" role="progressbar"></div>
                            </div>
                            <div id="tableContainer" class="table-responsive">
                                <table class="table table-bordered table-striped" id="resultTable"></table>
                            </div>
                            <div id="paginationSection" class="mt-3 d-flex justify-content-between" style="display:none;">
                                <button class="btn btn-secondary" id="prevPage">Previous</button>
                                <span id="pageInfo" class="align-self-center fw-bold"></span>
                                <button class="btn btn-secondary" id="nextPage">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between"><div class="copyright"><?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Abhimanyu</a></div><div>For <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering</a>.</div></div>
            </footer>
        </div>
    </div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script> 
    
    <script>
        // Paste your full JavaScript logic from your original api_tester.php file here
        let currentPage = 1, totalCount = 0, pageSize = 100, selectedAttributes = [];
        const apiUrl = document.getElementById("apiUrl");
        const entitySet = document.getElementById("entitySet");
        const attributeList = document.getElementById("attributeList");
        const entitySection = document.getElementById("entitySection");
        const attributeSection = document.getElementById("attributeSection");
        const tableContainer = document.getElementById("tableContainer");
        const resultTable = document.getElementById("resultTable");
        const paginationSection = document.getElementById("paginationSection");
        const pageInfo = document.getElementById("pageInfo");
        const resultsCard = document.getElementById("resultsCard");

        document.getElementById("fetchEntities").onclick = () => {
            fetch(`?getEntities=1&apiUrl=${encodeURIComponent(apiUrl.value)}`)
            .then(res => res.json())
            .then(data => {
                entitySet.innerHTML = "";
                data.entitySets.forEach(e => entitySet.innerHTML += `<option value="${e}">${e}</option>`);
                entitySection.style.display = 'block';
            });
        };

        document.getElementById("loadAttributes").onclick = () => {
            fetch(`?getAttributes=1&apiUrl=${encodeURIComponent(apiUrl.value)}&entitySet=${entitySet.value}`)
            .then(res => res.json())
            .then(data => {
                attributeList.innerHTML = "";
                data.attributes.forEach(attr => {
                    attributeList.innerHTML += `<span class="attr-box"><input type="checkbox" class="form-check-input attrChk" value="${attr}" > ${attr}</span>`;
                });
                attributeSection.style.display = 'block';
            });
        };

        document.getElementById("loadData").onclick = () => {
            selectedAttributes = [...document.querySelectorAll(".attrChk:checked")].map(e=>e.value);
            if (selectedAttributes.length==0) { alert("Select at least one attribute."); return; }
            currentPage=1;
            resultsCard.style.display = 'block';
            fetchTotalCount().then(() => { loadPage(currentPage); paginationSection.style.display='flex'; });
        };

        document.getElementById("prevPage").onclick = () => { if (currentPage>1) { currentPage--; loadPage(currentPage); }};
        document.getElementById("nextPage").onclick = () => { if (currentPage*pageSize<totalCount) { currentPage++; loadPage(currentPage); }};

        function fetchTotalCount() {
            return fetch(`?getTotalCount=1&apiUrl=${encodeURIComponent(apiUrl.value)}&entitySet=${entitySet.value}`)
            .then(res => res.json()).then(data => totalCount=data.count);
        }

        function loadPage(page) {
            fetch(`?fetchPage=1&apiUrl=${encodeURIComponent(apiUrl.value)}&entitySet=${entitySet.value}&attributes=${selectedAttributes.join(",")}&page=${page}&limit=${pageSize}`)
            .then(res => res.json())
            .then(data => {
                renderTable(data.records);
                pageInfo.innerText = `Page: ${page} / ${Math.ceil(totalCount/pageSize)} (Total: ${totalCount} records)`;
            });
        }

        function renderTable(records) {
            resultTable.innerHTML = "";
            if (!records.length) { resultTable.innerHTML = "<tbody><tr><td>No records found.</td></tr></tbody>"; return; }
            let thead = "<thead><tr>" + selectedAttributes.map(h=>`<th>${h}</th>`).join('') + "</tr></thead>";
            let tbody = "<tbody>" + records.map(row => "<tr>"+selectedAttributes.map(h=>`<td>${row[h]??""}</td>`).join('')+"</tr>").join('') + "</tbody>";
            resultTable.innerHTML = thead + tbody;
            tableContainer.style.display = 'block';
        }
    </script>
</body>
</html>