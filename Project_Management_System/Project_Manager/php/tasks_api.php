<?php
// File: php/tasks_api.php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Assumes db_connection.php is in the same directory

// Sanitize input functions
if (!function_exists('sanitize_input_task_api')) {
    function sanitize_input_task_api($conn, $data) {
        if (is_null($data)) return null;
        return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
    }
}
if (!function_exists('sanitize_textarea_task_api')) {
    function sanitize_textarea_task_api($conn, $data) {
        if (is_null($data)) return null;
        return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
    }
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

$validStatuses = ['To Do', 'In Progress', 'Done', 'Blocked'];
$validPriorities = ['Low', 'Medium', 'High'];

switch ($action) {
    case 'createTask':
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $taskName = sanitize_input_task_api($conn, $_POST['task_name']);
        $taskDescription = sanitize_textarea_task_api($conn, $_POST['task_description']);
        
        $startDate = !empty($_POST['start_date']) ? sanitize_input_task_api($conn, $_POST['start_date']) : null;
        if ($startDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDate)) {
            $startDate = null; 
        }

        $status = isset($_POST['status']) ? sanitize_input_task_api($conn, $_POST['status']) : 'To Do';
        $priority = isset($_POST['priority']) ? sanitize_input_task_api($conn, $_POST['priority']) : 'Medium';
        $assigneeId = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null;
        $dueDate = !empty($_POST['due_date']) ? sanitize_input_task_api($conn, $_POST['due_date']) : null;
        $dependentOnTaskId = !empty($_POST['dependent_on_task_id']) ? (int)$_POST['dependent_on_task_id'] : null;

        if ($projectId <= 0) { echo json_encode(['success' => false, 'message' => 'Valid Project ID required.']); exit; }
        if (empty($taskName)) { echo json_encode(['success' => false, 'message' => 'Task name required.']); exit; }
        if (!in_array($status, $validStatuses)) { echo json_encode(['success' => false, 'message' => 'Invalid status.']); exit; }
        if (!in_array($priority, $validPriorities)) { echo json_encode(['success' => false, 'message' => 'Invalid priority.']); exit; }
        if ($dueDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dueDate)) $dueDate = null;

        $sql = "INSERT INTO tasks (project_id, task_name, task_description, start_date, status, priority, assignee_id, due_date, dependent_on_task_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isssssisi", $projectId, $taskName, $taskDescription, $startDate, $status, $priority, $assigneeId, $dueDate, $dependentOnTaskId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'task_id' => $stmt->insert_id, 'message' => 'Task created successfully.']);
            } else {
                error_log("Create task execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            error_log("Create task prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
        break;

    case 'getTasksByProject':
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        if ($projectId <= 0) { echo json_encode(['success' => false, 'message' => 'Valid Project ID required.']); exit; }

        $tasks = [];
        $sql = "SELECT t.*, u.user_name AS assignee_name, dep_task.task_name AS dependent_task_name 
                FROM tasks t
                LEFT JOIN users u ON t.assignee_id = u.user_id
                LEFT JOIN tasks dep_task ON t.dependent_on_task_id = dep_task.task_id
                WHERE t.project_id = ? 
                ORDER BY FIELD(t.priority, 'High', 'Medium', 'Low'), t.due_date ASC, t.created_at DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $row['start_date'] = $row['start_date'] ?: null;
                    $row['due_date'] = $row['due_date'] ?: null;
                    $row['dependent_task_name'] = $row['dependent_on_task_id'] ? $row['dependent_task_name'] : null;
                    $tasks[] = $row;
                }
                echo json_encode(['success' => true, 'tasks' => $tasks]);
            } else { 
                 error_log("Get tasks execute failed: " . $stmt->error);
                 echo json_encode(['success' => false, 'message' => 'Error fetching tasks: ' . $stmt->error]);
            }
            $stmt->close();
        } else { 
            error_log("Get tasks prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error preparing to fetch tasks: ' . $conn->error]);
        }
        break;

    case 'updateTaskStatus':
        $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        $newStatus = isset($_POST['status']) ? sanitize_input_task_api($conn, $_POST['status']) : '';

        if ($taskId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid Task ID.']); exit; }
        if (empty($newStatus) || !in_array($newStatus, $validStatuses)) { echo json_encode(['success' => false, 'message' => 'Invalid status.']); exit; }

        $sql = "UPDATE tasks SET status = ? WHERE task_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $newStatus, $taskId);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Task status updated.']);
                } else { 
                    echo json_encode(['success' => false, 'message' => 'Task not found or status unchanged.']);
                }
            } else { 
                error_log("Update task status execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            }
            $stmt->close();
        } else { 
            error_log("Update task status prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
        break;

    case 'updateTask': // This is where line 157 is located
        $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        $taskName = sanitize_input_task_api($conn, $_POST['task_name'] ?? '');
        $taskDescription = sanitize_textarea_task_api($conn, $_POST['task_description'] ?? '');
        
        $startDate = !empty($_POST['start_date']) ? sanitize_input_task_api($conn, $_POST['start_date']) : null;
        if ($startDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $startDate)) {
            $startDate = null; 
        }

        $status = sanitize_input_task_api($conn, $_POST['status'] ?? 'To Do');
        $priority = sanitize_input_task_api($conn, $_POST['priority'] ?? 'Medium');
        $assigneeId = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null;
        $dueDate = !empty($_POST['due_date']) ? sanitize_input_task_api($conn, $_POST['due_date']) : null;
        $dependentOnTaskId = !empty($_POST['dependent_on_task_id']) ? (int)$_POST['dependent_on_task_id'] : null;

        if ($taskId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid Task ID.']); exit; }
        if (empty($taskName)) { echo json_encode(['success' => false, 'message' => 'Task name required.']); exit; }
        if (!in_array($status, $validStatuses)) { echo json_encode(['success' => false, 'message' => 'Invalid status.']); exit; }
        if (!in_array($priority, $validPriorities)) { echo json_encode(['success' => false, 'message' => 'Invalid priority.']); exit; }
        if ($dueDate && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dueDate)) $dueDate = null; 
        if ($dependentOnTaskId !== null && $dependentOnTaskId == $taskId) {
            echo json_encode(['success' => false, 'message' => 'A task cannot depend on itself.']);
            exit;
        }

        $sql = "UPDATE tasks SET task_name = ?, task_description = ?, start_date = ?, status = ?, priority = ?, assignee_id = ?, due_date = ?, dependent_on_task_id = ? WHERE task_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // MODIFIED: Corrected types string from "sssssisi" to "sssssissi"
            // taskName (s), taskDescription (s), startDate (s), status (s), priority (s), 
            // assigneeId (i), dueDate (s), dependentOnTaskId (i), taskId (i)
            $stmt->bind_param("sssssissi", $taskName, $taskDescription, $startDate, $status, $priority, $assigneeId, $dueDate, $dependentOnTaskId, $taskId); // This is line 157
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Task updated.']);
                } else { 
                    echo json_encode(['success' => true, 'message' => 'No changes made or task not found.']);
                }
            } else { 
                error_log("Update task execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            }
            $stmt->close();
        } else { 
            error_log("Update task prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
        break;

    case 'deleteTask':
        $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        if ($taskId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid Task ID.']); exit; }

        $sql = "DELETE FROM tasks WHERE task_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $taskId);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Task deleted.']);
                } else { 
                    echo json_encode(['success' => false, 'message' => 'Task not found.']);
                }
            } else { 
                error_log("Delete task execute failed: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            }
            $stmt->close();
        } else { 
            error_log("Delete task prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action for tasks API.']);
        break;
}
$conn->close();
?>