<?php
require_once 'config.php';
// page name - unsolved_issues.php
$can_manage_status = true; 
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>All PO Issues - SIMPLEX INTERNAL PORTAL</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/simplex_icon.ico" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: { families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ["assets/css/fonts.min.css"], },
      });
    </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
      .page-header-title { text-align: center; margin-bottom: 30px; font-weight: bold; color: #003366; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
      .loading-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 1056; display: flex; align-items: center; justify-content: center;}
      .table th, .table td { white-space: nowrap; vertical-align: middle; }
      .form-label { font-weight: 500; margin-bottom: 0.5rem; }
      .card-stats .card-title { font-size: 1.1rem; margin-bottom: 0.5rem; font-weight: 600; }
      .card-stats .card-category { font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0; }
      .card-stats .card-body { padding: 1rem; }
      .table-responsive { max-height: 70vh; }
      .timeline { list-style: none; padding: 0; position: relative; }
      .timeline:before { top: 0; bottom: 0; position: absolute; content: " "; width: 3px; background-color: #eee; left: 20px; margin-left: -1.5px; }
      .timeline-item { margin-bottom: 20px; position: relative; }
      .timeline-badge { color: #fff; width: 40px; height: 40px; line-height: 40px; font-size: 1.2em; text-align: center; position: absolute; top: 16px; left: 20px; margin-left: -20px; border-radius: 50%; }
      .timeline-badge.primary { background-color: #007bff !important; } .timeline-badge.success { background-color: #28a745 !important; }
      .timeline-badge.warning { background-color: #ffc107 !important; } .timeline-badge.danger { background-color: #dc3545 !important; }
      .timeline-badge.dark { background-color: #212529 !important; } .timeline-badge.info { background-color: #17a2b8 !important; }
      .timeline-panel { margin-left: 60px; padding: 15px; border-radius: 2px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); position: relative; background: #fff; border: 1px solid #ddd; }
      .alert-blinking { animation: blinker 1.5s linear infinite; } @keyframes blinker { 50% { opacity: 0.5; } }
    </style>
  </head>
  <body>
    <div class="wrapper">
      <div class="sidebar" data-background-color="dark"><!-- Sidebar content --></div>
      <div class="main-panel">
        <div class="main-header"><!-- Header content --></div>
        <div class="container">
          <div class="page-inner">
            <h1 class="page-header-title mt-4">All Purchase Order Issues</h1>
            <div class="d-flex justify-content-end mb-4">
             <a href="po_issues_dashboard.php" class="btn btn-secondary">
                  <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
             </a>
            </div>
            <div id="problem-type-cards" class="row mb-4"></div>
            <div class="card">
                <div class="card-header"><div class="d-flex justify-content-between"><h4 class="card-title">Filters</h4><button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true">Hide/Show Filters</button></div></div>
                <div class="collapse show" id="filterCollapse"><div class="card-body"><div class="row g-3" id="dynamic-filters">
                    <div class="col-lg-3 col-md-6"><label class="form-label">Status (Open, In progress, Resolved, Closed)</label><select class="form-select filter-control" data-filter-key="status"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Site</label><select class="form-select filter-control" data-filter-key="site"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Problem Type</label><select class="form-select filter-control" data-filter-key="problem_type"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Gate Entry No</label><select class="form-select filter-control" data-filter-key="gate_entry_no"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Supplier</label><select class="form-select filter-control" data-filter-key="supplier_id"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Invoice No</label><select class="form-select filter-control" data-filter-key="invoice_no"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Invoice Date (From - To)</label><div class="input-group"><input type="date" class="form-control filter-control" data-filter-key="invoice_date_from"><input type="date" class="form-control filter-control" data-filter-key="invoice_date_to"></div></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Challan No</label><select class="form-select filter-control" data-filter-key="challan_no"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Challan Date (From - To)</label><div class="input-group"><input type="date" class="form-control filter-control" data-filter-key="challan_date_from"><input type="date" class="form-control filter-control" data-filter-key="challan_date_to"></div></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Purchase Order Number</label><select class="form-select filter-control" data-filter-key="po_number"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Project ID</label><select class="form-select filter-control" data-filter-key="project_id"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Buyer ID</label><select class="form-select filter-control" data-filter-key="buyer_id"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Buyer Name</label><select class="form-select filter-control" data-filter-key="buyer_name"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Issue Reporter</label><select class="form-select filter-control" data-filter-key="reported_by_name"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Issue Assignee</label><select class="form-select filter-control" data-filter-key="assignee_name"></select></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Issue Created At</label><div class="input-group"><input type="date" class="form-control filter-control" data-filter-key="created_at_from"><input type="date" class="form-control filter-control" data-filter-key="created_at_to"></div></div>
                    <div class="col-lg-3 col-md-6"><label class="form-label">Issue Status Upadted At</label><div class="input-group"><input type="date" class="form-control filter-control" data-filter-key="updated_at_from"><input type="date" class="form-control filter-control" data-filter-key="updated_at_to"></div></div>
                    
                    
                </div></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Issues (<span id="total-records">0</span>)</h4></div>
                <div class="card-body position-relative">
                    <div id="loading-overlay" class="loading-overlay" style="display: none;"><div class="spinner-border text-primary"></div></div>
                    <div class="table-responsive"><table class="table table-striped table-hover">
                        <thead id="issues-table-head"></thead>
                        <tbody id="issues-table-body"></tbody>
                    </table></div>
                    <nav><ul class="pagination justify-content-end mt-3" id="pagination-controls"></ul></nav>
                </div>
            </div>
          </div>
        </div>
        <footer class="footer"><!-- Footer --></footer>
      </div>
    </div>
    <div class="modal fade" id="issueLogModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">History for Issue #<span id="modal-issue-id"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><div id="modal-loading-overlay" class="loading-overlay" style="display: none;"><div class="spinner-border text-primary"></div></div><div id="issueLogBody"></div></div>
      <div class="modal-footer"><textarea id="log-remark-input" class="form-control" placeholder="Type a remark..."></textarea><button type="button" class="btn btn-primary" id="add-log-remark-btn">Add Remark</button></div>
    </div></div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    $(document).ready(function() {
        let currentPage = 1;
        let filters = {};
        const canManageStatus = <?php echo json_encode($can_manage_status); ?>;

        const tableHeaders = ['ID (Track History of Issue)', 'Alert Days', 'Status', 'Set Status', 'Reporter','Assignee', 'Site', 'Problem', 'Supplier ID', 'Supplier Name','Buyer ID','Buyer Name', 'Attachment', 'PO No', 'Invoice Number', 'Invoice Date', 'Challan Number', 'Challan Date', 'Project ID'];
        const dbKeys = ['id', 'days_open', 'status', 'setStatus','reported_by_name', 'assignee_name','site', 'problem_type','supplier_id', 'supplier_name','buyer_id', 'buyer_name', 'attachment_path', 'po_number', 'invoice_no','invoice_date','challan_no', 'challan_date', 'project_id'];

        function fetchAndRender() {
            $('#loading-overlay').show();
            filters = {};
            $('.filter-control').each(function() {
                const key = $(this).data('filter-key');
                const value = $(this).val();
                if (value && value !== 'all' && value !== '') filters[key] = value;
            });

            $.ajax({
                url: 'fetch_all_issues.php', type: 'GET', dataType: 'json', data: { ...filters, page: currentPage },
                success: function(response) {
                    if (response.status === 'success') {
                        updateDisplayCards(response.problemTypeCounts);
                        updateFilterOptions(response.filterOptions);
                        updateTable(response.data);
                        updatePagination(response.pagination);
                        $('#total-records').text(response.pagination.totalRecords);
                    } else { handleError(response.message); }
                },
                error: function() { handleError('A server error occurred.'); },
                complete: function() { $('#loading-overlay').hide(); }
            });
        }
        
        function handleError(message) {
            $('#issues-table-body').html(`<tr><td colspan="${tableHeaders.length}" class="text-center text-danger">${message}</td></tr>`);
        }

        function updateDisplayCards(counts) {
            const cardsContainer = $('#problem-type-cards').empty();
            const problemTypes = ["PO Not Created", "PO Pending for Authorization", "Quantity Variation", "Document Required", "Price Variation", "PO Item Variation", "Invoice Required", "Freight Issue"];
            problemTypes.forEach(type => {
                const countData = counts[type] || { total: 0, Open: 0, 'In Progress': 0, Resolved: 0, Closed: 0 };
                const cardHtml = `<div class="col-lg-3 col-md-6 mb-4"><div class="card card-stats card-round"><div class="card-body"><h5 class="card-title">${type} (${countData.total})</h5><div class="d-flex justify-content-around"><div class="text-center"><p class="card-category mb-0">Open</p><h6 class="fw-bold text-danger">${countData.Open}</h6></div><div class="text-center"><p class="card-category mb-0">Progress</p><h6 class="fw-bold text-warning">${countData['In Progress']}</h6></div><div class="text-center"><p class="card-category mb-0">Resolved</p><h6 class="fw-bold text-success">${countData.Resolved}</h6></div><div class="text-center"><p class="card-category mb-0">Closed</p><h6 class="fw-bold text-secondary">${countData.Closed}</h6></div></div></div></div></div>`;
                cardsContainer.append(cardHtml);
            });
        }

        function updateTable(issues) {
            const tableBody = $('#issues-table-body').empty();
            $('#issues-table-head').html(`<tr>${tableHeaders.map(h => `<th>${h}</th>`).join('')}</tr>`);
            if (issues.length === 0) { tableBody.html(`<tr><td colspan="${tableHeaders.length}" class="text-center">No issues found.</td></tr>`); return; }
            issues.forEach(issue => {
                let row = '<tr>';
                dbKeys.forEach(key => {
                    let cellData = issue[key] ?? 'N/A';
                    if (key === 'id' || key === 'actions') {
                        cellData = `<a href="#" class="btn btn-sm btn-info issue-log-link" data-issue-id="${issue.id}">${key === 'id' ? issue.id : 'View Log'}</a>`;
                    } else if (key === 'status') {
                        const badgeClasses = { 'Open': 'bg-danger', 'In Progress': 'bg-warning', 'Resolved': 'bg-success', 'Closed': 'bg-secondary' };
                        cellData = `<span class="badge ${badgeClasses[issue.status] || 'bg-info'}">${issue.status}</span>`;
                    } else if (key === 'days_open') {
                        const createDate = new Date(issue.created_at); const now = new Date();
                        const diffDays = Math.ceil(Math.abs(now - createDate) / (1000 * 60 * 60 * 24));
                        const blinkClass = (diffDays >= 3 && issue.status === 'Open') ? 'alert-blinking' : '';
                        cellData = `<span class="badge bg-light text-dark ${blinkClass}">${diffDays}</span>`;
                    }else if (key === 'attachment_path') { // <-- ADD THIS BLOCK
    // If the path exists, create a link. Otherwise, show 'N/A'.
    cellData = issue.attachment_path 
        ? `<a href="${issue.attachment_path}" target="_blank" class="btn btn-sm btn-outline-primary p-1">View File</a>` 
        : 'N/A';
} 
                    else if (key === 'setStatus') {
                        if (canManageStatus && (issue.status === 'Open' || issue.status === 'In Progress')) {
                            let options = `<option value="" selected hidden>Change</option>`;
                            if (issue.status === 'Open') options += `<option value="In Progress">In Progress</option>`;
                            if (issue.status === 'In Progress') options += `<option value="Resolved">Resolved</option>`;
                            cellData = `<select class="form-select form-select-sm status-update-dropdown" data-issue-id="${issue.id}">${options}</select>`;
                        } else { cellData = 'N/A'; }
                    }
                    row += `<td>${cellData}</td>`;
                });
                row += '</tr>';
                tableBody.append(row);
            });
        }
        
        function updatePagination(pagination) {
            const paginationControls = $('#pagination-controls').empty();
            if (pagination.totalPages <= 1) return;
            const createPageItem = (page, text, isActive = false, isDisabled = false) => `<li class="page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page}">${text}</a></li>`;
            paginationControls.append(createPageItem(pagination.currentPage - 1, '«', false, pagination.currentPage === 1));
            for (let i = 1; i <= pagination.totalPages; i++) {
                if (i === 1 || i === pagination.totalPages || (i >= pagination.currentPage - 2 && i <= pagination.currentPage + 2)) {
                    paginationControls.append(createPageItem(i, i, pagination.currentPage === i));
                } else if (paginationControls.children().last().text() !== '...') {
                    paginationControls.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                }
            }
            paginationControls.append(createPageItem(pagination.currentPage + 1, '»', false, pagination.currentPage === pagination.totalPages));
        }
        
        function updateFilterOptions(options) {
             $('select.filter-control').each(function() {
                const select = $(this);
                const filterKey = select.data('filter-key');
                if (filterKey === 'user_confirmation') return;
                const currentVal = filters[filterKey] || 'all';
                let defaultText = 'All ' + filterKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                select.empty().append(`<option value="all">${defaultText.replace(/Ids/g, 'IDs').replace(/ss/g, 's')}</option>`);
                if (options[filterKey]) {
                    options[filterKey].forEach(optionData => {
                        let value, text;
                        if(filterKey === 'supplier_id') { value = optionData.supplier_id; text = `${optionData.supplier_id} - ${optionData.supplier_name}`; }
                        else if(filterKey === 'buyer_id') { value = optionData.buyer_id; text = `${optionData.buyer_id} - ${optionData.buyer_name}`; }
                        else if(filterKey === 'reported_by_empcode') { value = optionData.reported_by_empcode; text = optionData.reported_by_name; }
                        else if(filterKey === 'assignee_empcode') { value = optionData.assignee_empcode; text = optionData.assignee_name; }
                        else { value = text = optionData[filterKey]; }
                        select.append($('<option>', { value: value, text: text }));
                    });
                }
                 select.val(currentVal);
            });
        }

        function loadIssueLog(issueId) {
            $('#modal-issue-id').text(issueId);
            $('#add-log-remark-btn').data('issue-id', issueId);
            $('#issueLogModal').modal('show');
            $('#modal-loading-overlay').show();
            $.ajax({
                url: 'fetch_issue_log.php', type: 'GET', data: { id: issueId }, dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.issue_details) {
                        let logHtml = '<ul class="timeline">';
                        const issueDetails = response.issue_details;
                        logHtml += `<li class="timeline-item"><div class="timeline-badge success"><i class="fa fa-plus"></i></div><div class="timeline-panel"><div class="timeline-heading"><h5 class="timeline-title">PO Issue Raised - Remark Created</h5><p><small class="text-muted"><i class="fa fa-user"></i> by ${issueDetails.reported_by_name || 'System'} on <i class="fa fa-clock"></i> ${new Date(issueDetails.created_at).toLocaleString()}</small></p></div><div class="timeline-body">${issueDetails.remarks ? `<p style="white-space: pre-wrap;">${issueDetails.remarks}</p>` : '<p>No initial remarks provided.</p>'}</div></div></li>`;
                        let reopenCount = response.logs.filter(log => log.action_type.includes('Re-Opened')).length;
                        response.logs.forEach(log => {
                            let badgeClass = 'primary'; let icon = 'fa-info-circle'; let statusChangeText = '';
                            if (log.action_type.includes('Re-Opened')) { badgeClass = 'danger'; icon = 'fa-undo'; }
                            if (log.action_type.includes('Closed')) { badgeClass = 'dark'; icon = 'fa-check'; }
                            if (log.action_type === 'Status Changed') { badgeClass = 'warning'; icon = 'fa-random'; statusChangeText = `<p class="mb-0">Status changed from <strong>${log.old_value}</strong> to <strong>${log.new_value}</strong>.</p>`; }
                            if (log.action_type === 'Remark Added') { badgeClass = 'info'; icon = 'fa-comment-dots'; }
                            logHtml += `<li class="timeline-item"><div class="timeline-badge ${badgeClass}"><i class="fa ${icon}"></i></div><div class="timeline-panel"><div class="timeline-heading"><h5 class="timeline-title">${log.action_type}</h5><p><small class="text-muted"><i class="fa fa-user"></i> by ${log.action_by_name || 'System'} on <i class="fa fa-clock"></i> ${new Date(log.log_timestamp).toLocaleString()}</small></p></div><div class="timeline-body">${statusChangeText}${log.remarks ? `<p class="mt-2" style="white-space: pre-wrap;"><strong>Remarks:</strong> ${log.remarks}</p>` : ''}</div></div></li>`;
                        });
                        logHtml += '</ul>';
                        if(reopenCount > 0) $('#issueLogBody').html(`<div class="alert alert-danger" role="alert">This issue has been re-opened ${reopenCount} time(s).</div>` + logHtml);
                        else $('#issueLogBody').html(logHtml);
                    } else { $('#issueLogBody').html(`<p class="text-danger">${response.message}</p>`); }
                },
                error: function() { $('#issueLogBody').html('<p class="text-danger">Failed to load issue history.</p>'); },
                complete: function() { $('#modal-loading-overlay').hide(); }
            });
        }

        $(document).on('change', '.filter-control', function() { currentPage = 1; fetchAndRender(); });
        $(document).on('click', '#pagination-controls a', function(e) { e.preventDefault(); const page = $(this).data('page'); if(page && !$(this).parent().hasClass('disabled')) { currentPage = page; fetchAndRender(); } });
        $(document).on('click', '.issue-log-link', function(e) { e.preventDefault(); loadIssueLog($(this).data('issue-id')); });
        
        $(document).on('click', '#add-log-remark-btn', function() {
            const issueId = $(this).data('issue-id');
            const remark = $('#log-remark-input').val();
            if (!remark.trim()) { Swal.fire('Error', 'Remark cannot be empty.', 'error'); return; }
            $.ajax({
                url: 'add_log_remark.php', type: 'POST', dataType: 'json', data: { issue_id: issueId, remark: remark },
                success: (response) => {
                    if (response.status === 'success') {
                        $('#log-remark-input').val('');
                        loadIssueLog(issueId);
                        fetchAndRender(); 
                    } else { Swal.fire('Error!', response.message, 'error'); }
                },
                error: () => Swal.fire('Error!', 'An unexpected server error.', 'error')
            });
        });
        
        $(document).on('change', '.status-update-dropdown', function() {
            const dropdown = $(this); const issueId = dropdown.data('issue-id'); const newStatus = dropdown.val();
            if (!newStatus) return;
            Swal.fire({
                title: 'Confirm Status Change', text: `Please provide remarks for this action.`, input: 'textarea',
                inputPlaceholder: 'Enter remarks here...', showCancelButton: true, confirmButtonText: 'Yes, update it!',
                preConfirm: (remarks) => { if (!remarks) { Swal.showValidationMessage(`Remarks are required.`); } return remarks; }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'update_issue_status.php', type: 'POST', dataType: 'json',
                        data: { issue_id: issueId, new_status: newStatus, remark: result.value },
                        success: (response) => {
                            if (response.status === 'success') { Swal.fire('Updated!', response.message, 'success'); fetchAndRender(); }
                            else { Swal.fire('Error!', response.message, 'error'); }
                        },
                        error: () => Swal.fire('Error!', 'A server error occurred.', 'error')
                    });
                } else { dropdown.val(''); }
            });
        });

        fetchAndRender();
    });
    </script>
  </body>
</html> 