<?php
// page name - fetch_dashboard_counts.php 

require_once 'config.php';

header('Content-Type: application/json');

$empcode = $_SESSION['empcode'] ?? '';
$counts = [
    'created_by_you' => 0,
    'closed_by_you' => 0,
    'unsolved' => 0,
    'all_issues' => 0,
    'closed_only' => 0, // Specifically for the "Closed" tile
    'open_by_type' => []
];

// --- Counts for issues created by the logged-in user ---
$sql_track = "SELECT 
                COUNT(id) AS all_count,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_count
              FROM po_issues WHERE reported_by_empcode = ?";
if($stmt_track = $conn_po->prepare($sql_track)) {
    $stmt_track->bind_param("s", $empcode);
    $stmt_track->execute();
    $result = $stmt_track->get_result();
    if($row = $result->fetch_assoc()) {
        $counts['created_by_you'] = (int) ($row['all_count'] ?? 0);
        $counts['closed_by_you'] = (int) ($row['closed_count'] ?? 0);
    }
    $stmt_track->close();
}


// --- Overview Counts for All Users ---
$sql_overview = "SELECT status, COUNT(id) AS count FROM po_issues GROUP BY status";
if($result_overview = $conn_po->query($sql_overview)) {
    while($row = $result_overview->fetch_assoc()) {
        $status = $row['status'];
        $count = (int) $row['count'];
        
        $counts['all_issues'] += $count;
        
        if ($status === 'Open' || $status === 'In Progress') {
            $counts['unsolved'] += $count;
        } 
        
        if ($status === 'Closed') {
            $counts['closed_only'] = $count; // Strictly 'Closed' issues
        }
    }
}

// --- Counts of Open issues grouped by problem type ---
$sql_open_by_type = "SELECT problem_type, COUNT(id) as count 
                     FROM po_issues 
                     WHERE status = 'Open' 
                     GROUP BY problem_type";
if($result_open = $conn_po->query($sql_open_by_type)) {
    while($row = $result_open->fetch_assoc()) {
        $counts['open_by_type'][$row['problem_type']] = (int) $row['count'];
    }
}


// Echo the final counts as a JSON object
echo json_encode($counts);

$conn_po->close();
$conn_user->close();
?>