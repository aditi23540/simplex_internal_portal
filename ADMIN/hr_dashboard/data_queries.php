<?php
// Set headers to prevent caching and ensure JSON output
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

// The user did not provide 'db_config.php', but it is required for the script to run.
// Assuming 'db_config.php' establishes a PDO connection object named $pdo.
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    // Fallback for analysis purposes if db_config.php is missing.
    // This will cause an error if the script is run without the actual file.
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Database configuration file is missing.']);
    exit;
}


try {
    function clean_and_standardize_string($value, $default = 'Unassigned') {
        if ($value === null || trim($value) === '') { return $default; }
        $cleaned_value = trim($value);
        $cleaned_value = preg_replace('/\s+/', ' ', $cleaned_value);
        $cleaned_value = mb_convert_case($cleaned_value, MB_CASE_TITLE, "UTF-8");
        return $cleaned_value;
    }

    // --- Data Fetching and Classification ---
    // MODIFIED: Added WHERE clause to filter by employee_portal_status
    $masterUserQuery = "
        SELECT u.user_id, u.first_name, u.surname, u.gender, 
               hrd.unit, hrd.department, hrd.designation, hrd.date_of_joining, 
               hrd.grade, hrd.employee_id_ascent, hrd.attendance_policy,
               hrd.department_head
        FROM users u 
        LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id
        WHERE hrd.employee_portal_status = 1
    ";
    
    $stmt = $pdo->query($masterUserQuery);
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a map of employee_id_ascent to full names for the head lookup
    $ascentIdToNameMap = [];
    foreach ($allUsers as $user) {
        if (!empty($user['employee_id_ascent'])) {
            $ascentIdToNameMap[$user['employee_id_ascent']] = trim($user['first_name'] . ' ' . $user['surname']);
        }
    }

    // Add 'status' and the correct 'department_head_name'
    foreach ($allUsers as &$user) {
        $user['status'] = (!empty($user['employee_id_ascent'])) ? 'Active' : 'Inactive';
        $headAscentId = $user['department_head'];
        $user['department_head_name'] = isset($ascentIdToNameMap[$headAscentId]) ? $ascentIdToNameMap[$headAscentId] : 'Not Assigned';
    }
    unset($user);

    $activeUsers = array_filter($allUsers, function($user) {
        return isset($user['status']) && $user['status'] === 'Active';
    });
    
    $allDashboardData = [];

    // --- Basic Data Preparation ---
    $allDashboardData['active_headcount'] = count($activeUsers);
    $allDashboardData['inactive_headcount'] = count(array_filter($allUsers, fn($user) => $user['status'] === 'Inactive'));

    // --- Department Breakdown ---
    $departmentColumn = array_column($activeUsers, 'department');
    $cleanedDeptColumn = array_map(fn($value) => clean_and_standardize_string($value, 'Unassigned'), $departmentColumn);
    $deptCounts = array_count_values($cleanedDeptColumn);
    arsort($deptCounts);
    $allDashboardData['department_breakdown'] = [];
    foreach ($deptCounts as $dept => $count) { $allDashboardData['department_breakdown'][] = ['name' => $dept, 'value' => $count]; }
    $allDashboardData['department_names'] = array_keys($deptCounts);

    // --- Unit Breakdown ---
    $unitColumn = array_column($activeUsers, 'unit');
    $cleanedUnitColumn = array_map(fn($value) => clean_and_standardize_string($value, 'No Unit'), $unitColumn);
    $unitCounts = array_count_values($cleanedUnitColumn);
    arsort($unitCounts);
    $allDashboardData['unit_breakdown'] = [];
    foreach ($unitCounts as $unit => $count) { $allDashboardData['unit_breakdown'][] = ['name' => $unit, 'value' => $count]; }

    // --- Designation Breakdown ---
    $designationColumn = array_column($activeUsers, 'designation');
    $cleanedDesignationColumn = array_map(fn($value) => clean_and_standardize_string($value, 'Not Designated'), $designationColumn);
    $designationCounts = array_count_values($cleanedDesignationColumn);
    arsort($designationCounts);
    $allDashboardData['designation_breakdown'] = [];
    foreach ($designationCounts as $designation => $count) { $allDashboardData['designation_breakdown'][] = ['name' => $designation, 'value' => $count]; }
    $allDashboardData['designation_names'] = array_keys($designationCounts);
    
    // --- Department Head Breakdown ---
    $departmentHeadColumn = array_column($activeUsers, 'department_head_name');
    $deptHeadCounts = array_count_values($departmentHeadColumn);
    arsort($deptHeadCounts);
    $allDashboardData['department_head_breakdown'] = [];
    foreach ($deptHeadCounts as $deptHead => $count) { $allDashboardData['department_head_breakdown'][] = ['name' => $deptHead, 'value' => $count]; }
    $allDashboardData['department_head_names'] = array_keys($deptHeadCounts);

    // --- Department Head Details (Accordion Data) ---
    $departmentHeadDetails = [];
    $uniqueDeptHeads = [];
    foreach ($activeUsers as $user) {
        $deptHead = $user['department_head_name'];
        if (!isset($uniqueDeptHeads[$deptHead])) {
            $uniqueDeptHeads[$deptHead] = ['name' => $deptHead, 'employee_count' => 0, 'departments' => [], 'employees' => []];
        }
        $uniqueDeptHeads[$deptHead]['employee_count']++;
        $dept = clean_and_standardize_string($user['department'], 'Unassigned');
        if (!in_array($dept, $uniqueDeptHeads[$deptHead]['departments'])) {
            $uniqueDeptHeads[$deptHead]['departments'][] = $dept;
        }
        $uniqueDeptHeads[$deptHead]['employees'][] = [
            'name' => trim($user['first_name'] . ' ' . $user['surname']),
            'designation' => clean_and_standardize_string($user['designation'], 'Not Designated')
        ];
    }
    $departmentHeadDetails = array_values($uniqueDeptHeads);
    usort($departmentHeadDetails, fn($a, $b) => $b['employee_count'] - $a['employee_count']);
    $allDashboardData['department_head_details'] = $departmentHeadDetails;

    // --- Hiring Trends ---
    $joinersByYear = [];
    foreach ($activeUsers as $user) {
        $year = $user['date_of_joining'] ? date('Y', strtotime($user['date_of_joining'])) : 'Unknown Year';
        $joinersByYear[$year] = ($joinersByYear[$year] ?? 0) + 1;
    }
    ksort($joinersByYear);
    $allDashboardData['headcount_trend'] = [];
    foreach($joinersByYear as $year => $count){ $allDashboardData['headcount_trend'][] = ['name' => $year, 'value' => $count]; }

    $joinersByDecade = [];
    foreach ($activeUsers as $user) {
        if ($user['date_of_joining']) {
            $year = (int)date('Y', strtotime($user['date_of_joining']));
            $decade = match (true) {
                $year >= 2020 => '2020-2029',
                $year >= 2010 => '2010-2019',
                $year >= 2000 => '2000-2009',
                $year >= 1990 => '1990-1999',
                default => 'Before 1990',
            };
            $joinersByDecade[$decade] = ($joinersByDecade[$decade] ?? 0) + 1;
        } else {
            $joinersByDecade['Unknown Year'] = ($joinersByDecade['Unknown Year'] ?? 0) + 1;
        }
    }
    arsort($joinersByDecade);
    $allDashboardData['hiring_by_decade'] = [];
    foreach($joinersByDecade as $decade => $count){ $allDashboardData['hiring_by_decade'][] = ['name' => $decade, 'value' => $count]; }

    // --- Diversity & Policy ---
    $genderColumn = array_column($activeUsers, 'gender');
    $processedGenderColumn = array_map(fn($v) => $v ?? 'Not Specified', $genderColumn);
    $genderCounts = array_count_values($processedGenderColumn);
    $allDashboardData['gender_distribution'] = [];
    foreach ($genderCounts as $gender => $count) { $allDashboardData['gender_distribution'][] = ['name' => $gender, 'value' => $count]; }

    $policyColumn = array_column($activeUsers, 'attendance_policy');
    $cleanedPolicyColumn = array_map(fn($value) => clean_and_standardize_string($value, 'N/A'), $policyColumn);
    $policyCounts = array_count_values($cleanedPolicyColumn);
    arsort($policyCounts);
    $allDashboardData['attendance_policy_distribution'] = [];
    foreach ($policyCounts as $policy => $count) {
        if ($count > 0) { $allDashboardData['attendance_policy_distribution'][] = ['name' => $policy, 'value' => $count]; }
    }
    
    echo json_encode($allDashboardData);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage(), 'php_error_line' => $e->getLine()]);
}
?>