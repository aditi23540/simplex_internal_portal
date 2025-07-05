<?php
// page name - export_all_issues_excel.php

// 1. INCLUDE CORE FILES
// ======================
require_once 'config.php';
// Use the Composer autoloader to load PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;

// 2. REPLICATE THE FILTERING LOGIC
// =================================
// This is the *exact same logic* from fetch_all_issues.php to ensure
// that the exported data matches what the user sees in the table.
$filterColumns = [
    'status', 'problem_type', 'site', 'supplier_id', 'gate_entry_no', 
    'invoice_no', 'challan_no', 'po_number', 'project_id', 'buyer_id', 
    'reported_by_empcode', 'assignee_empcode', 'user_confirmation'
];
$dateFilters = ['invoice_date', 'challan_date', 'created_at', 'updated_at', 'status_set_by_assignee_at'];
$activeFilters = [];

foreach ($filterColumns as $col) {
    if (isset($_GET[$col]) && $_GET[$col] !== 'all' && !empty($_GET[$col])) {
        $activeFilters[$col] = $_GET[$col];
    }
}
foreach ($dateFilters as $col) {
    if (isset($_GET[$col . '_from']) && !empty($_GET[$col . '_from'])) {
        $activeFilters[$col . '_from'] = $_GET[$col . '_from'];
    }
    if (isset($_GET[$col . '_to']) && !empty($_GET[$col . '_to'])) {
        $activeFilters[$col . '_to'] = $_GET[$col . '_to'];
    }
}

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


// 3. FETCH ALL MATCHING DATA FROM DATABASE
// =========================================
// Note: We do NOT use LIMIT or OFFSET here because we want all matching data for the export.
$dataSql = "SELECT * FROM po_issues" . $mainWhereSql . " ORDER BY id DESC";

$stmtData = $conn_po->prepare($dataSql);
$issues = [];
if($stmtData) {
    if(!empty($types)) {
        $stmtData->bind_param($types, ...$params);
    }
    $stmtData->execute();
    $result = $stmtData->get_result();
    while ($row = $result->fetch_assoc()) {
        $issues[] = $row;
    }
    $stmtData->close();
}

$conn_po->close();
$conn_user->close();


// 4. GENERATE THE EXCEL FILE USING PHPSPREADSHEET
// ==============================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('PO Issues Report');

// Define Headers (matching the table on the webpage)
$headers = ['ID', 'Status', 'Site', 'Reporter', 'Assignee', 'Problem', 'Supplier ID', 'Supplier Name','Buyer ID','Buyer Name', 'Attachment', 'PO No', 'Invoice No', 'Invoice Date', 'Challan No', 'Challan Date', 'Project ID', 'Created At', 'Last Updated'];
// The database keys corresponding to the headers
$headerKeys = ['id','status', 'site', 'reported_by_name', 'assignee_name', 'problem_type','supplier_id', 'supplier_name','buyer_id', 'buyer_name', 'attachment_path', 'po_number', 'invoice_no','invoice_date','challan_no', 'challan_date', 'project_id', 'created_at', 'updated_at'];

// Write headers to the Excel sheet
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    // Make headers bold
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    // Auto-size columns for better readability
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Write data rows
$rowNum = 2;
foreach($issues as $issue) {
    $col = 'A';
    foreach($headerKeys as $key) {
        $sheet->setCellValue($col . $rowNum, $issue[$key] ?? ''); // Use null coalescing for safety
        $col++;
    }
    $rowNum++;
}


// 5. SEND THE FILE TO THE BROWSER
// ===============================
$writer = new Xlsx($spreadsheet);

// Set the HTTP headers to trigger a download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$filename = "PO_Issues_Report_" . date('Y-m-d') . ".xlsx";
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Write the file to the browser
$writer->save('php://output');
exit;

?>