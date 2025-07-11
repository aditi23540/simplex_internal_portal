<?php
// File: php/projects_api.php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Assumes db_connection.php is in the same directory

// Sanitize input functions (ensure they are defined or included if used more broadly)
if (!function_exists('sanitize_input_project_api')) { 
    function sanitize_input_project_api($conn, $data) {
        if (is_null($data)) return null;
        return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
    }
}
if (!function_exists('sanitize_textarea_project_api')) {
    function sanitize_textarea_project_api($conn, $data) {
        if (is_null($data)) return null;
        return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
    }
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'createProject':
        $projectName = sanitize_input_project_api($conn, $_POST['project_name']);
        $projectDescription = sanitize_textarea_project_api($conn, $_POST['project_description']);
        $startDate = !empty($_POST['start_date']) ? sanitize_input_project_api($conn, $_POST['start_date']) : null;
        $endDate = !empty($_POST['end_date']) ? sanitize_input_project_api($conn, $_POST['end_date']) : null;
        $projectOwnerId = !empty($_POST['project_owner_id']) ? (int)$_POST['project_owner_id'] : 1; // Default to user 1 if not provided

        if (empty($projectName)) {
            echo json_encode(['success' => false, 'message' => 'Project name is required.']);
            exit;
        }
        if ($startDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDate)) $startDate = null;
        if ($endDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $endDate)) $endDate = null;

        $sql = "INSERT INTO projects (project_name, project_description, start_date, end_date, project_owner_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssi", $projectName, $projectDescription, $startDate, $endDate, $projectOwnerId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'project_id' => $stmt->insert_id, 'message' => 'Project created successfully.']);
            } else {
                error_log("Create project execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            error_log("Create project prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
        break;

    case 'updateProject':
        $projectId = isset($_POST['projectId']) ? (int)$_POST['projectId'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
        $projectName = sanitize_input_project_api($conn, $_POST['project_name']);
        $projectDescription = sanitize_textarea_project_api($conn, $_POST['project_description']);
        $startDate = !empty($_POST['start_date']) ? sanitize_input_project_api($conn, $_POST['start_date']) : null;
        $endDate = !empty($_POST['end_date']) ? sanitize_input_project_api($conn, $_POST['end_date']) : null;
        $projectOwnerId = !empty($_POST['project_owner_id']) ? (int)$_POST['project_owner_id'] : 1;

        if ($projectId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid Project ID.']); exit; }
        if (empty($projectName)) { echo json_encode(['success' => false, 'message' => 'Project name required.']); exit; }
        if ($startDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDate)) $startDate = null; 
        if ($endDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $endDate)) $endDate = null;

        $sql = "UPDATE projects SET project_name = ?, project_description = ?, start_date = ?, end_date = ?, project_owner_id = ? WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssii", $projectName, $projectDescription, $startDate, $endDate, $projectOwnerId, $projectId);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Project updated successfully.']);
                } else {
                    echo json_encode(['success' => true, 'message' => 'No changes made or project not found.']);
                }
            } else { 
                error_log("Update project execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]); 
            }
            $stmt->close();
        } else { 
            error_log("Update project prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]); 
        }
        break;

    case 'deleteProject':
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        if ($projectId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid Project ID.']); exit; }

        // Tasks associated with this project will be deleted by ON DELETE CASCADE in DB schema
        $sql = "DELETE FROM projects WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Project and its tasks deleted.']);
                } else { 
                    echo json_encode(['success' => false, 'message' => 'Project not found.']); 
                }
            } else { 
                error_log("Delete project execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]); 
            }
            $stmt->close();
        } else { 
            error_log("Delete project prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]); 
        }
        break;

    case 'getProjects':
        $projects = [];
        $sql = "SELECT
                    p.project_id, p.project_name, p.project_description, p.start_date, p.end_date, p.project_owner_id,
                    u.user_name AS owner_name,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id) AS total_tasks,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status = 'Done') AS done_tasks,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status = 'In Progress') AS inprogress_tasks,
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status = 'To Do') AS todo_tasks
                FROM
                    projects p
                LEFT JOIN
                    users u ON p.project_owner_id = u.user_id
                ORDER BY
                    p.project_name ASC";
        
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['start_date'] = $row['start_date'] ?: null;
                $row['end_date'] = $row['end_date'] ?: null;

                $total_tasks = (int)$row['total_tasks'];
                $done_tasks = (int)$row['done_tasks'];
                $inprogress_tasks = (int)$row['inprogress_tasks'];
                $todo_tasks = (int)$row['todo_tasks'];

                $status_text = 'Active'; 
                $status_class_suffix = 'active';

                if ($total_tasks > 0) {
                    if ($inprogress_tasks > 0) {
                        $status_text = 'In Progress';
                        $status_class_suffix = 'in-progress';
                    } elseif ($done_tasks == $total_tasks) {
                        $status_text = 'Complete';
                        $status_class_suffix = 'complete';
                    } elseif ($todo_tasks == $total_tasks && $inprogress_tasks == 0 && $done_tasks == 0) { // More specific for not started
                        $status_text = 'Not Started Yet';
                        $status_class_suffix = 'not-started';
                    }
                    // Default 'Active' if some tasks are done and some are to-do, but none in progress
                } else {
                    $status_text = 'No Tasks Yet';
                    $status_class_suffix = 'no-tasks';
                }
                $row['project_status_text'] = $status_text;
                $row['project_status_class'] = 'tile-' . $status_class_suffix;

                $projects[] = $row;
            }
            echo json_encode(['success' => true, 'projects' => $projects]);
        } else {
            error_log("Get projects query failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error fetching projects: ' . $conn->error]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified for projects API.']);
        break;
}

$conn->close();
?>