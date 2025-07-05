<?php
// File: php/users_api.php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Assumes db_connection.php is in the same directory

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getUsers':
        $users = [];
        $sql = "SELECT user_id, user_name FROM users ORDER BY user_name ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode(['success' => true, 'users' => $users]);
        } else {
            error_log("Get users query failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $conn->error]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action for users API.']);
        break;
}
$conn->close();
?>