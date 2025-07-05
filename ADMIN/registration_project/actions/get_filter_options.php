<?php
// actions/get_filter_options.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../includes/db_config.php'; 

if (!isset($link) || !$link) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$primary_filter = $_GET['filter'] ?? '';
$view_mode = $_GET['view'] ?? 'default';
$context_filters = $_GET;
unset($context_filters['filter'], $context_filters['view']);

$column_map = [
    'unit' => ['table' => 'hrd', 'col' => 'unit'],
    'department' => ['table' => 'hrd', 'col' => 'department'],
    'designation' => ['table' => 'hrd', 'col' => 'designation'],
    'category' => ['table' => 'hrd', 'col' => 'category'],
    'grade' => ['table' => 'hrd', 'col' => 'grade'],
    'status' => ['table' => 'hrd', 'col' => 'status'],
    'leave_group' => ['table' => 'hrd', 'col' => 'leave_group'],
    'shift_schedule' => ['table' => 'hrd', 'col' => 'shift_schedule'],
    'reporting_incharge' => ['table' => 'hrd', 'col' => 'reporting_incharge'],
    'department_head' => ['table' => 'hrd', 'col' => 'department_head'],
    'attendance_policy' => ['table' => 'hrd', 'col' => 'attendance_policy'],
    'employee_role' => ['table' => 'hrd', 'col' => 'employee_role'],
    'employee_portal_status' => ['table' => 'hrd', 'col' => 'employee_portal_status'],
    'gender' => ['table' => 'u', 'col' => 'gender'],
    'perm_birth_state' => ['table' => 'u', 'col' => 'perm_birth_state'],
    'perm_birth_city_village' => ['table' => 'u', 'col' => 'perm_birth_city_village'],
    'blood_group' => ['table' => 'u', 'col' => 'blood_group'],
    'pan_available' => ['table' => 'u', 'col' => 'pan_available'],
    'aadhar_available' => ['table' => 'u', 'col' => 'aadhar_available'],
    'dl_available' => ['table' => 'u', 'col' => 'dl_available'],
    'present_birth_state' => ['table' => 'u', 'col' => 'present_birth_state'],
    'present_birth_city_village' => ['table' => 'u', 'col' => 'present_birth_city_village'],
    'date_of_joining' => ['table' => 'hrd', 'col' => 'date_of_joining'],
    'date_of_birth' => ['table' => 'u', 'col' => 'date_of_birth']
];

if (empty($primary_filter) || !array_key_exists($primary_filter, $column_map)) {
    echo json_encode([]);
    exit;
}

$primary_col_info = $column_map[$primary_filter];
$primary_column_sql = "{$primary_col_info['table']}.{$primary_col_info['col']}";

$sql = "SELECT DISTINCT {$primary_column_sql} AS value 
        FROM users u 
        LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id 
        WHERE {$primary_column_sql} IS NOT NULL AND TRIM({$primary_column_sql}) != ''";

$where_parts = [];
$params = [];
$types = '';

switch ($view_mode) {
    case 'new_users':
        $four_months_ago = date('Y-m-d', strtotime('-4 months'));
        $where_parts[] = "hrd.date_of_joining >= ?";
        $params[] = $four_months_ago;
        $types .= "s";
        break;
    case 'working':
        $where_parts[] = "hrd.employee_portal_status = ?";
        $params[] = '1';
        $types .= "s";
        break;
    case 'resigned':
        $where_parts[] = "hrd.employee_portal_status = ?";
        $params[] = '0';
        $types .= "s";
        break;
}

foreach ($context_filters as $key => $value) {
    if (array_key_exists($key, $column_map) && $value !== '') {
        $col_info = $column_map[$key];
        $col_sql = "{$col_info['table']}.{$col_info['col']}";
        $where_parts[] = "{$col_sql} = ?";
        $params[] = $value;
        $types .= 's';
    } 
    elseif (str_ends_with($key, '_from') && $value !== '') {
        $db_key = str_replace('_from', '', $key);
        if (array_key_exists($db_key, $column_map)) {
            $col_info = $column_map[$db_key];
            $col_sql = "{$col_info['table']}.{$col_info['col']}";
            $where_parts[] = "{$col_sql} >= ?";
            $params[] = $value;
            $types .= 's';
        }
    } elseif (str_ends_with($key, '_to') && $value !== '') {
        $db_key = str_replace('_to', '', $key);
        if (array_key_exists($db_key, $column_map)) {
            $col_info = $column_map[$db_key];
            $col_sql = "{$col_info['table']}.{$col_info['col']}";
            $where_parts[] = "{$col_sql} <= ?";
            $params[] = $value;
            $types .= 's';
        }
    }
}

if (!empty($where_parts)) {
    $sql .= " AND " . implode(" AND ", $where_parts);
}

$sql .= " ORDER BY value ASC";

$response = [];
if ($stmt = $link->prepare($sql)) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if($result) {
        while ($row = $result->fetch_assoc()) {
            $response[] = $row['value'];
        }
    }
    $stmt->close();
}

echo json_encode($response);
?>