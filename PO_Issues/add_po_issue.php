<?php
// Page name - add_po_issue.php
require_once 'config.php';

// --- Fetch reporter_user_id from user_master_db ---
$reporter_user_id = null;
if (isset($_SESSION['empcode'])) {
    // We use the $conn_user connection established in config.php
    $sql_user = "SELECT user_id FROM user_hr_details WHERE employee_id_ascent = ?";
    if($stmt_user = $conn_user->prepare($sql_user)) {
        $stmt_user->bind_param("s", $_SESSION['empcode']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if($row_user = $result_user->fetch_assoc()) {
            $reporter_user_id = $row_user['user_id'];
        }
        $stmt_user->close();
    }
}

// This is prepared in config.php but we ensure it's here for clarity
$username_for_display = htmlspecialchars(ucwords(strtolower(str_replace('.', ' ', $_SESSION['username'] ?? 'User'))));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Add PO Issue - SIMPLEX INTERNAL PORTAL</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () { sessionStorage.fonts = true; },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- SweetAlert2 for notifications -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


    <style>
      .page-inner .page-header-title { text-align: center; margin-bottom: 30px; font-weight: bold; color: #003366; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
      .form-control:disabled, .form-control[readonly] { background-color: #e9ecef; opacity: 1; }
      .select2-container--bootstrap-5 .select2-selection { min-height: calc(1.5em + 0.75rem + 2px); }
    </style>
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <div class="logo-header" data-background-color="dark">
            <a href="dashboard.php" class="logo"><img src="assets/img/kaiadmin/simplex_icon_2.png" alt="navbar brand" class="navbar-brand" height="50"/></a>
            <div class="nav-toggle"><button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button><button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button></div>
            <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
          </div>
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item"><a href="index.php"><i class="fas fa-home"></i><p>Dashboard</p></a></li>
              <li class="nav-section"><span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span><h4 class="text-section">Modules</h4></li>
              <li class="nav-item active submenu">
                <a data-bs-toggle="collapse" href="#poIssuesCollapse"><i class="fas fa-file-invoice-dollar"></i><p>PO Issues</p><span class="caret"></span></a>
                <div class="collapse show" id="poIssuesCollapse">
                  <ul class="nav nav-collapse">
                    <li class="active"><a href="add_po_issue.php"><span class="sub-item">Raise PO Issue</span></a></li>
                    <li><a href="view_po_issues.php"><span class="sub-item">View All Issues</span></a></li>
                  </ul>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
              <a href="dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: #333;"><img src="assets/img/kaiadmin/simplex_icon.ico" alt="Simplex Logo" style="height: 60px; margin-right: 10px;" /><span style="font-size: 1.8rem; font-weight: 500;">Simplex Engineering</span></a>
              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item topbar-user dropdown hidden-caret">
                  <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <div class="avatar-sm"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User Avatar" class="avatar-img rounded-circle" onerror="this.onerror=null; this.src='assets/img/kaiadmin/default-avatar.png';"/></div>
                    <span class="profile-username"><span class="op-7">Hi,</span> <span class="fw-bold"><?php echo $username_for_display; ?></span></span>
                  </a>
                  <ul class="dropdown-menu dropdown-user animated fadeIn">
                     <div class="dropdown-user-scroll scrollbar-outer">
                        <li>
                           <div class="user-box">
                              <div class="avatar-lg"><img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="image profile" class="avatar-img rounded"/></div>
                              <div class="u-text"><h4><?php echo $username_for_display; ?></h4><p class="text-muted">Emp Code: <?php echo htmlspecialchars($_SESSION['empcode']); ?></p></div>
                           </div>
                        </li>
                        <li><div class="dropdown-divider"></div><a class="dropdown-item" href="#">My Profile</a><div class="dropdown-divider"></div><a class="dropdown-item" href="logout.php">Logout</a></li>
                     </div>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
        </div>

        <!-- Main Content -->
        <div class="container">
          <div class="page-inner">
            <h1 class="page-header-title mt-4">Report a New Purchase Order Issue</h1>
            <!-- Back to Dashboard Button -->
<div class="d-flex justify-content-end mb-3">
    <a href="po_issues_dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>
<!-- End Back to Dashboard Button -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><div class="card-title">New Issue Details</div></div>
                        <div class="card-body">
                            <form id="poIssueForm" action="process_po_issue.php" method="POST" enctype="multipart/form-data">
                                <!-- Hidden fields for reporter identity -->
                                <input type="hidden" name="reporter_user_id" value="<?= htmlspecialchars((string)$reporter_user_id) ?>">
                                <input type="hidden" name="reported_by_empcode" value="<?= htmlspecialchars($_SESSION['empcode']) ?>">
                                <input type="hidden" name="reported_by_name" value="<?= htmlspecialchars($username_for_display) ?>">

                                <div class="row">
                                    <div class="col-md-12"><div class="form-group"><label for="problem_type">Select Problem</label><select class="form-select" id="problem_type" name="problem_type" required><option value="" disabled selected>Select a Problem to Start</option><option value="PO Not Created">PO Not Created</option><option value="PO Pending for Authorization">PO Pending for Authorization</option><option value="Quantity Variation">Quantity Variation</option><option value="Document Required">Document Required</option><option value="Price Variation">Price Variation</option><option value="PO Item Variation">PO Item Variation</option><option value="Invoice Required">Invoice Required</option><option value="Freight Issue">Freight Issue</option></select></div></div>
                                    <div class="col-md-12"><div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group conditional-field" id="site_group" style="display: none;"><label for="site">Site</label><select class="form-select" id="site" name="site" required><option value="" disabled selected >Select a Site</option><option value="Unit I">Unit I</option><option value="Unit II">Unit II</option><option value="Unit III">Unit III</option></select></div>
                                            <div class="form-group conditional-field" id="supplier_id_group" style="display: none;"><label for="supplier_id">Supplier ID</label><select class="form-select" id="supplier_id" name="supplier_id"><option value="">Loading suppliers...</option></select></div>
                                            <div class="form-group conditional-field" id="gate_entry_no_group" style="display: none;"><label for="gate_entry_no">Select GATE Entry No.</label><select class="form-select" id="gate_entry_no" name="gate_entry_no" disabled><option value="">Connect to SAP API to load...</option></select></div>
                                            <div class="form-group conditional-field" id="invoice_no_group" style="display: none;"><label for="invoice_no">Invoice No.</label><input type="text" class="form-control" id="invoice_no" name="invoice_no" placeholder="Enter Invoice No."></div>
                                            <div class="form-group conditional-field" id="challan_no_group" style="display: none;"><label for="challan_no">Challan No.</label><input type="text" class="form-control" id="challan_no" name="challan_no" placeholder="Enter Challan No."></div>
                                            <div class="form-group conditional-field" id="po_number_group" style="display: none;"><label for="po_number">Select PO Order</label><select class="form-select" id="po_number" name="po_number" disabled><option value="">Populated from SAP...</option></select></div>
                                            <div class="form-group conditional-field" id="buyer_id_group" style="display: none;"><label for="buyer_id">Buyer ID</label><input type="text" class="form-control" id="buyer_id" name="buyer_id" placeholder="Auto-filled from SAP" readonly></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group conditional-field" id="project_id_group" style="display: none;"><label for="project_id">Project ID</label><input type="text" class="form-control" id="project_id" name="project_id" placeholder="Enter Project ID"></div>
                                            <div class="form-group conditional-field" id="supplier_name_group" style="display: none;"><label for="supplier_name">Supplier Name</label><input type="text" class="form-control" id="supplier_name" name="supplier_name" placeholder="Auto-filled from Supplier ID" readonly></div>
                                            <div class="form-group conditional-field" id="invoice_date_group" style="display: none;"><label for="invoice_date">Invoice Date</label><input type="date" class="form-control" id="invoice_date" name="invoice_date"></div>
                                            <div class="form-group conditional-field" id="challan_date_group" style="display: none;"><label for="challan_date">Challan Date</label><input type="date" class="form-control" id="challan_date" name="challan_date"></div>
                                            <div class="form-group conditional-field" id="buyer_name_group" style="display: none;"><label for="buyer_name">Buyer Name</label><input type="text" class="form-control" id="buyer_name" name="buyer_name" placeholder="Auto-filled from SAP" readonly></div>
                                        </div>
                                        <div class="col-md-12"><div class="form-group conditional-field" id="remarks_group" style="display: none;"><label for="remarks">Enter Remarks</label><textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Describe the issue in detail..."></textarea></div></div>
                                        <div class="col-md-12"><div class="form-group conditional-field" id="attachment_group" style="display: none;"><label for="attachment">Select File (Optional)</label><input type="file" class="form-control" id="attachment" name="attachment"><small class="form-text text-muted">You can upload any relevant document (e.g., PDF, JPG, PNG).</small></div></div>
                                    </div></div>
                                </div>
                                <div class="card-action"><button type="submit" class="btn btn-success">Submit Issue</button><button type="reset" class="btn btn-danger">Cancel</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
        <!-- End Main Content -->
        <footer class="footer">
          <div class="container-fluid d-flex justify-content-between">
            <div class="copyright"><?php echo date('Y')?>, made with <i class="fa fa-heart heart text-danger"></i> by <a href="#">Abhimanyu</a></div>
            <div>For <a target="_blank" href="https://www.simplexengg.in/home/">Simplex Engineering & Foundry Works PVT. LTD.</a>.</div>
          </div>
        </footer>
      </div>
    </div>
    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- SweetAlert2 for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // --- Form Reset and Submission Logic ---
            function resetFullForm() {
                $('#poIssueForm')[0].reset(); // Resets native inputs
                $('#supplier_id').val(null).trigger('change'); // Resets Select2, which also clears supplier_name
                $('#problem_type').val('').trigger('change'); // Resets main dropdown and hides fields
            }

            $('#poIssueForm').on('submit', function(e) {
                e.preventDefault(); 
                var formData = new FormData(this);
                
                Swal.fire({
                    title: 'Submitting...',
                    text: 'Please wait while we save your issue.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: 'process_po_issue.php',
                    type: 'POST',
                    data: formData,
                    processData: false, 
                    contentType: false, 
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Success!', text: response.message, })
                            .then(() => {
                                resetFullForm(); // Use the consolidated reset function
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Oops...', text: 'An error occurred: ' + response.message, });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Submission Failed', text: 'A server error occurred. Please try again later.', });
                    }
                });
            });

            // --- UI and Conditional Field Logic ---
            const fieldMapping = {
                'PO Not Created': ['site_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'PO Pending for Authorization': ['site_group', 'po_number_group', 'buyer_id_group', 'buyer_name_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'Quantity Variation': ['site_group', 'gate_entry_no_group', 'po_number_group', 'buyer_id_group', 'buyer_name_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'Document Required': ['site_group', 'buyer_id_group', 'buyer_name_group', 'gate_entry_no_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'po_number_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'Price Variation': ['site_group', 'buyer_id_group', 'buyer_name_group', 'gate_entry_no_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'po_number_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'PO Item Variation': ['site_group', 'buyer_id_group', 'buyer_name_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'po_number_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'Invoice Required': ['site_group', 'buyer_id_group', 'buyer_name_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'challan_no_group', 'challan_date_group', 'po_number_group', 'remarks_group', 'project_id_group', 'attachment_group'],
                'Freight Issue': ['site_group', 'buyer_id_group', 'buyer_name_group', 'supplier_id_group', 'supplier_name_group', 'invoice_no_group', 'invoice_date_group', 'challan_no_group', 'challan_date_group', 'po_number_group', 'remarks_group', 'project_id_group', 'attachment_group']
            };

            function updateFormVisibility() {
                $('.conditional-field').hide();
                const selectedProblem = $('#problem_type').val();
                if (selectedProblem && fieldMapping[selectedProblem]) {
                    fieldMapping[selectedProblem].forEach(fieldId => $('#' + fieldId).fadeIn('fast'));
                }
            }
            
            $('#problem_type').on('change', updateFormVisibility);
            $('button[type="reset"]').on('click', function(e) {
                e.preventDefault();
                resetFullForm();
            });

            // Initialize form on page load
            updateFormVisibility();

            // --- SAP OData Supplier Fetching via Proxy ---
            let supplierCache = [];
            const excludedGroups = ['SUPL', 'ZEMP', 'ZONE', 'ZTRP'];
            const initialProxyUrl = "sap_proxy.php";

            async function fetchAllSuppliers(proxyUrl) {
                try {
                    const response = await fetch(proxyUrl);
                    if (!response.ok) throw new Error(`Proxy error! status: ${response.status}`);
                    const data = await response.json();
                    if (!data.d || !data.d.results) throw new Error("Invalid data structure from proxy.");
                    
                    const filteredSuppliers = data.d.results.filter(supplier => {
                        const group = supplier.SupplierAccountGroup ? supplier.SupplierAccountGroup.trim().toUpperCase() : '';
                        return !excludedGroups.includes(group);
                    });
                    
                    supplierCache.push(...filteredSuppliers);

                    if (data.d.__next) {
                        const nextProxyUrl = `sap_proxy.php?next_url=${encodeURIComponent(data.d.__next)}`;
                        await fetchAllSuppliers(nextProxyUrl);
                    } else {
                        initializeSupplierDropdown();
                    }
                } catch (error) {
                    console.error("Error fetching supplier data via proxy:", error);
                    $('#supplier_id').html('<option value="">Error loading. Check console.</option>');
                }
            }

            function initializeSupplierDropdown() {
                const idData = supplierCache.map(s => ({ id: s.Supplier, text: `${s.Supplier} - ${s.SupplierName}` }));

                $('#supplier_id').empty().append(new Option('', '', true, true)).select2({
                    theme: 'bootstrap-5',
                    data: idData,
                    placeholder: 'Type or select a Supplier'
                });

                $('#supplier_id').on('change', function() {
                    const selectedId = $(this).val();
                    const selectedSupplier = supplierCache.find(s => s.Supplier === selectedId);
                    if (selectedSupplier) {
                        $('#supplier_name').val(selectedSupplier.SupplierName);
                    } else {
                        $('#supplier_name').val('');
                    }
                });
            }

            fetchAllSuppliers(initialProxyUrl);
        });
    </script>
  </body>
</html>
