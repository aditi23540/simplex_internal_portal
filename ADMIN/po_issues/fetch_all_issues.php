<?php
// page name - fetch_all_issues.php
require_once 'config.php';

header('Content-Type: application/json');

// This page fetches issues for ALL users.

// --- Get counts for the top display cards (for all users) ---
$problemTypeCounts = [];
$sql_counts = "SELECT problem_type, status, COUNT(id) as count FROM po_issues GROUP BY problem_type, status";
$stmt_counts = $conn_po->prepare($sql_counts);
if($stmt_counts) {
    $stmt_counts->execute();
    $result_counts = $stmt_counts->get_result();
    while ($row = $result_counts->fetch_assoc()) {
        if (!isset($problemTypeCounts[$row['problem_type']])) {
            $problemTypeCounts[$row['problem_type']] = ['total' => 0, 'Open' => 0, 'In Progress' => 0, 'Resolved' => 0, 'Closed' => 0];
        }
        if(isset($row['status'])) {
            $problemTypeCounts[$row['problem_type']][$row['status']] = $row['count'];
            $problemTypeCounts[$row['problem_type']]['total'] += $row['count'];
        }
    }
    $stmt_counts->close();
}


// --- Collect all active filters ---
$filterColumns = [
    'status', 'problem_type', 'site', 'supplier_id', 'gate_entry_no', 
    'invoice_no', 'challan_no', 'po_number', 'project_id', 'buyer_id', 
    'reported_by_empcode', 'assignee_empcode', 'user_confirmation'
];
$dateFilters = ['invoice_date', 'challan_date', 'created_at', 'updated_at', 'status_set_by_assignee_at'];
$activeFilters = [];

foreach ($filterColumns as $col) {
    if (isset($_GET[$col]) && $_GET[$col] !== 'all' && !empty($_GET[$col])) $activeFilters[$col] = $_GET[$col];
}
foreach ($dateFilters as $col) {
    if (isset($_GET[$col . '_from']) && !empty($_GET[$col . '_from'])) $activeFilters[$col . '_from'] = $_GET[$col . '_from'];
    if (isset($_GET[$col . '_to']) && !empty($_GET[$col . '_to'])) $activeFilters[$col . '_to'] = $_GET[$col . '_to'];
}

// --- Build WHERE clause ---
$whereClauses = [];
$params = [];
$types = "";

if (count($activeFilters) > 0) {
    foreach ($activeFilters as $key => $val) {
        if (str_ends_with($key, '_from')) $whereClauses[] = "`" . substr($key, 0, -5) . "` >= ?";
        elseif (str_ends_with($key, '_to')) $whereClauses[] = "`" . substr($key, 0, -3) . "` <= ?";
        else $whereClauses[] = "`$key` = ?";
        $params[] = $val;
        $types .= 's';
    }
}
$mainWhereSql = count($whereClauses) > 0 ? " WHERE " . implode(" AND ", $whereClauses) : "";


// --- Fetch Cascading Filter Options ---
$filterOptions = [];
foreach ($filterColumns as $columnToQuery) {
    $cascadingWhereClauses = [];
    $cascadingParams = [];
    $cascadingTypes = "";
    
    foreach ($activeFilters as $col => $val) {
        if ($col !== $columnToQuery) {
            if (str_ends_with($col, '_from')) $cascadingWhereClauses[] = "`" . substr($col, 0, -5) . "` >= ?";
            elseif (str_ends_with($col, '_to')) $cascadingWhereClauses[] = "`" . substr($col, 0, -3) . "` <= ?";
            else $cascadingWhereClauses[] = "`$col` = ?";
            $cascadingParams[] = $val;
            $cascadingTypes .= 's';
        }
    }
    $cascadingWhereSql = count($cascadingWhereClauses) > 0 ? " WHERE " . implode(" AND ", $cascadingWhereClauses) : "";
    
    $columnSelect = "DISTINCT `$columnToQuery`";
    if ($columnToQuery === 'supplier_id') $columnSelect = "DISTINCT `supplier_id`, `supplier_name`";
    if ($columnToQuery === 'buyer_id') $columnSelect = "DISTINCT `buyer_id`, `buyer_name`";
    if ($columnToQuery === 'reported_by_empcode') $columnSelect = "DISTINCT `reported_by_empcode`, `reported_by_name`";
    if ($columnToQuery === 'assignee_empcode') $columnSelect = "DISTINCT `assignee_empcode`, `assignee_name`";
    
    $optionsSql = "SELECT $columnSelect FROM po_issues" . $cascadingWhereSql . " ORDER BY `$columnToQuery` ASC";
    
    $stmtOptions = $conn_po->prepare($optionsSql);
    if($stmtOptions) {
        if(!empty($cascadingTypes)) $stmtOptions->bind_param($cascadingTypes, ...$cascadingParams);
        $stmtOptions->execute();
        $result = $stmtOptions->get_result();
        $options = [];
        while($row = $result->fetch_assoc()){
            if (isset($row[$columnToQuery]) && !is_null($row[$columnToQuery]) && $row[$columnToQuery] !== '') {
                $options[] = $row;
            }
        }
        $filterOptions[$columnToQuery] = $options;
        $stmtOptions->close();
    }
}


// --- Pagination & Data Fetching ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$totalRecordsSql = "SELECT COUNT(id) as total FROM po_issues" . $mainWhereSql;
$stmtTotal = $conn_po->prepare($totalRecordsSql);
if(!empty($types)) $stmtTotal->bind_param($types, ...$params);
$stmtTotal->execute();
$totalRecords = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRecords / $limit);
$stmtTotal->close();
$dataSql = "SELECT * FROM po_issues" . $mainWhereSql . " ORDER BY id DESC LIMIT ? OFFSET ?";
array_push($params, $limit, $offset);
$types .= "ii";
$stmtData = $conn_po->prepare($dataSql);
$issues = [];
if($stmtData) {
    $stmtData->bind_param($types, ...$params);
    $stmtData->execute();
    $result = $stmtData->get_result();
    while ($row = $result->fetch_assoc()) $issues[] = $row;
    $stmtData->close();
}

echo json_encode([
    "status" => "success",
    "data" => $issues,
    "pagination" => [ "currentPage" => $page, "totalPages" => $totalPages, "totalRecords" => $totalRecords ],
    "problemTypeCounts" => $problemTypeCounts,
    "filterOptions" => $filterOptions
]);
$conn_po->close();
$conn_user->close();
?>