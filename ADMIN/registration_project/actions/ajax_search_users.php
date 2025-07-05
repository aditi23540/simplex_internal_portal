<?php
// /actions/ajax_search_users.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_config.php'; 

// --- Logic is the same, only HTML generation is changed ---
$possible_limits = [10, 25, 50, 100, 200];
$records_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $possible_limits) ? (int)$_GET['limit'] : 25;
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) { $current_page = 1; }
$search_query = isset($_GET['search']) ? trim($link->real_escape_string($_GET['search'])) : '';

$sql_select_from = "SELECT u.user_id, u.salutation, u.first_name, u.middle_name, u.surname, u.profile_picture_path, hrd.hr_detail_id IS NOT NULL AS hr_details_exist, hrd.employee_id_ascent, hrd.unit, hrd.department, hrd.date_of_joining, hrd.employee_role, itd.it_detail_id IS NOT NULL AS it_details_exist, pd_father.name AS father_name FROM users u LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id LEFT JOIN user_it_details itd ON u.user_id = itd.user_id LEFT JOIN parent_details pd_father ON u.user_id = pd_father.user_id AND pd_father.parent_type = 'Father'";
$sql_where = "";
$sql_params = [];
$sql_types = "";
if (!empty($search_query)) {
    $search_term_like = "%" . $search_query . "%";
    $sql_where = " WHERE (CONCAT_WS(' ', u.salutation, u.first_name, u.middle_name, u.surname) LIKE ? OR u.user_id LIKE ? OR hrd.employee_id_ascent LIKE ? OR pd_father.name LIKE ? OR hrd.unit LIKE ? OR hrd.department LIKE ? OR hrd.employee_role LIKE ?)";
    for ($i = 0; $i < 7; $i++) { $sql_params[] = &$search_term_like; $sql_types .= "s"; }
}
$total_records = 0;
$total_records_sql = "SELECT COUNT(DISTINCT u.user_id) AS total FROM users u LEFT JOIN user_hr_details hrd ON u.user_id = hrd.user_id LEFT JOIN parent_details pd_father ON u.user_id = pd_father.user_id AND pd_father.parent_type = 'Father' " . $sql_where;
if ($stmt_total = $link->prepare($total_records_sql)) {
    if (!empty($search_query)) { $stmt_total->bind_param($sql_types, ...$sql_params); }
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_records = $total_row['total'];
    $stmt_total->close();
}
$total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 1;
if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
$offset = ($current_page - 1) * $records_per_page;
$users = [];
$sql_final = $sql_select_from . $sql_where . " ORDER BY u.user_id DESC LIMIT ? OFFSET ?";
$sql_params_final = $sql_params;
$sql_params_final[] = &$records_per_page;
$sql_params_final[] = &$offset;
$sql_types_final = $sql_types . "ii";
if ($stmt = $link->prepare($sql_final)) {
    if(!empty($sql_types_final)){ $stmt->bind_param($sql_types_final, ...$sql_params_final); }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) { $users = $result->fetch_all(MYSQLI_ASSOC); $result->free(); }
    $stmt->close();
}


// --- Generate HTML for table rows ---
$table_rows_html = '';
if (!empty($users)) {
    $serial_no = $offset + 1;
    foreach ($users as $user) {
        $table_rows_html .= '<tr>';
        $table_rows_html .= '<td>' . $serial_no++ . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['user_id']) . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['employee_id_ascent'] ?? 'N/A') . '</td>';
        
        $display_avatar_path = !empty($user['profile_picture_path']) ? $user['profile_picture_path'] : '';
        $server_path_to_check = !empty($user['profile_picture_path']) ? dirname(__DIR__) . '/' . $user['profile_picture_path'] : '';
        
        $table_rows_html .= '<td><div class="profile-thumbnail-container">';
        if (!empty($display_avatar_path) && file_exists($server_path_to_check)) {
            $table_rows_html .= '<a href="' . htmlspecialchars($display_avatar_path) . '" target="_blank" title="View full image"><img src="' . htmlspecialchars($display_avatar_path) . '" alt="Photo" class="profile-thumbnail"></a>';
        } else {
            $table_rows_html .= '<div class="no-pic-placeholder">No Pic</div>';
        }
        $table_rows_html .= '</div></td>';
        
        $table_rows_html .= '<td>' . htmlspecialchars(trim($user['salutation'] . ' ' . $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['surname'])) . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['father_name'] ?? 'N/A') . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['unit'] ?? 'N/A') . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['department'] ?? 'N/A') . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['date_of_joining'] ? date("d M Y", strtotime($user['date_of_joining'])) : 'N/A') . '</td>';
        $table_rows_html .= '<td>' . htmlspecialchars($user['employee_role'] ?? 'N/A') . '</td>';
        $table_rows_html .= '<td><span class="badge ' . ($user['hr_details_exist'] ? 'bg-success' : 'bg-secondary') . '">' . ($user['hr_details_exist'] ? 'Yes' : 'No') . '</span></td>';
        $table_rows_html .= '<td><span class="badge ' . ($user['it_details_exist'] ? 'bg-success' : 'bg-secondary') . '">' . ($user['it_details_exist'] ? 'Yes' : 'No') . '</span></td>';
        
        // --- ACTION BUTTONS WITH LABELS FIX ---
        $table_rows_html .= '<td class="text-nowrap"><div class="d-flex flex-wrap">';
        $table_rows_html .= '<a href="view_user_details.php?user_id=' . $user['user_id'] . '" class="btn btn-info btn-sm table-action-btn" title="View Details"><i class="fa fa-eye"></i> View</a>';
        $table_rows_html .= '<a href="edit_user_form.php?user_id=' . $user['user_id'] . '" class="btn btn-warning btn-sm table-action-btn" title="Edit Basic Info"><i class="fa fa-edit"></i> Edit</a>';
        $hr_class = $user['hr_details_exist'] ? 'btn-success' : 'btn-primary';
        $hr_text = $user['hr_details_exist'] ? 'HR Edit' : 'HR Add';
        $table_rows_html .= '<a href="hr_onboarding_form.php?user_id=' . $user['user_id'] . '" class="btn btn-sm table-action-btn ' . $hr_class . '" title="' . ($user['hr_details_exist'] ? 'Edit HR Details' : 'Process HR Onboarding') . '"><i class="fa fa-users"></i> ' . $hr_text . '</a>';
        $it_class = $user['it_details_exist'] ? 'btn-success' : 'btn-secondary';
        $it_text = $user['it_details_exist'] ? 'IT Edit' : 'IT Add';
        $table_rows_html .= '<a href="it_setup_form.php?user_id=' . $user['user_id'] . '" class="btn btn-sm table-action-btn ' . $it_class . '" title="' . ($user['it_details_exist'] ? 'Edit IT Details' : 'Process IT Setup') . '"><i class="fa fa-desktop"></i> ' . $it_text . '</a>';
        $delete_confirm_msg = "Are you sure you want to delete this user?";
        $table_rows_html .= '<a href="actions/delete_user.php?user_id=' . $user['user_id'] . '" class="btn btn-danger btn-sm table-action-btn" title="Delete User" onclick="return confirm(\'' . $delete_confirm_msg . '\');"><i class="fa fa-trash"></i> Delete</a>';
        $table_rows_html .= '</div></td></tr>';
    }
} else {
    $table_rows_html = '<tr><td colspan="13" class="text-center py-4">No users found matching your criteria.</td></tr>';
}

// --- Generate HTML for pagination using Bootstrap classes ---
$pagination_html = '<div class="text-muted">Showing ' . min($offset + 1, $total_records) . ' to ' . min($offset + $records_per_page, $total_records) . ' of ' . $total_records . ' entries</div>';
$pagination_html .= '<ul class="pagination pg-primary">';
if ($current_page > 1) {
    $pagination_html .= '<li class="page-item"><a class="page-link" href="?page=' . ($current_page - 1) . '&limit=' . $records_per_page . '&search=' . urlencode($search_query) . '">Previous</a></li>';
} else {
    $pagination_html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
}
// Page number links logic
$num_links_to_show = 3; 
$start_page = max(1, $current_page - floor($num_links_to_show / 2));
$end_page = min($total_pages, $start_page + $num_links_to_show - 1);
if ($total_pages > 0 && $end_page - $start_page + 1 < $num_links_to_show) {
    if ($start_page == 1) $end_page = min($total_pages, $start_page + $num_links_to_show - 1);
    elseif ($end_page == $total_pages) $start_page = max(1, $end_page - $num_links_to_show + 1);
}
if ($start_page > 1) { $pagination_html .= '<li class="page-item"><a class="page-link" href="?page=1&limit='.$records_per_page.'&search='.urlencode($search_query).'">1</a></li>'; if ($start_page > 2) $pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
for ($i = $start_page; $i <= $end_page; $i++) {
    $pagination_html .= '<li class="page-item ' . (($i == $current_page) ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&limit=' . $records_per_page . '&search=' . urlencode($search_query) . '">' . $i . '</a></li>';
}
if ($end_page < $total_pages) { if ($end_page < $total_pages - 1) $pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; $pagination_html .= '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&limit='.$records_per_page.'&search='.urlencode($search_query).'">'.$total_pages.'</a></li>'; }

if ($current_page < $total_pages) {
    $pagination_html .= '<li class="page-item"><a class="page-link" href="?page=' . ($current_page + 1) . '&limit=' . $records_per_page . '&search=' . urlencode($search_query) . '">Next</a></li>';
} else {
    $pagination_html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
}
$pagination_html .= '</ul>';


// --- Return JSON response ---
header('Content-Type: application/json');
echo json_encode([
    'table_rows_html' => $table_rows_html,
    'pagination_html' => $pagination_html
]);

if (isset($link) && $link instanceof mysqli) {
    $link->close(); 
}
?>