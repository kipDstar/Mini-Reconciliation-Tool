<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';

class TasksAPI {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->getTasks();
                    break;
                case 'POST':
                    $this->createTask();
                    break;
                case 'PUT':
                    $this->updateTask();
                    break;
                case 'DELETE':
                    $this->deleteTask();
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    private function getTasks() {
        $sql = "
            SELECT 
                t.*,
                p.name as project_name,
                p.color as project_color
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            ORDER BY t.created_at DESC
        ";
        
        $stmt = $this->db->query($sql);
        $tasks = $stmt->fetchAll();
        
        $this->sendResponse(['tasks' => $tasks]);
    }
    
    private function createTask() {
        $input = $this->getJsonInput();
        error_log('POST input: ' . json_encode($input)); // Add this line for debugging
        
        // Validate required fields
        if (empty($input['taskTitle'])) {
            $this->sendError('Task title is required', 400);
            return;
        }
        
        // Prepare data
        $data = [
            'title' => trim($input['taskTitle']),
            'description' => isset($input['taskDescription']) ? trim($input['taskDescription']) : null,
            'priority' => isset($input['taskPriority']) ? $input['taskPriority'] : 'medium',
            'due_date' => !empty($input['taskDueDate']) ? $input['taskDueDate'] : null,
            'due_time' => !empty($input['taskDueTime']) ? $input['taskDueTime'] : null,
            'project_id' => !empty($input['taskProject']) ? (int)$input['taskProject'] : null,
            'tags' => isset($input['taskTags']) ? trim($input['taskTags']) : null,
            'status' => 'pending'
        ];
        
        // Validate priority
        if (!in_array($data['priority'], ['low', 'medium', 'high'])) {
            $data['priority'] = 'medium';
        }
        
        $sql = "
            INSERT INTO tasks (title, description, priority, due_date, due_time, project_id, tags, status)
            VALUES (:title, :description, :priority, :due_date, :due_time, :project_id, :tags, :status)
        ";
        
        $this->db->query($sql, $data);
        $taskId = $this->db->lastInsertId();
        
        $this->sendResponse([
            'message' => 'Task created successfully',
            'task_id' => $taskId
        ]);
    }
    
    private function updateTask() {
        $input = $this->getJsonInput();
        
        if (empty($input['taskId'])) {
            $this->sendError('Task ID is required', 400);
            return;
        }
        
        $taskId = (int)$input['taskId'];
        
        // Check if it's a status-only update
        if (isset($input['status']) && count($input) === 2) { // taskId + status
            $this->updateTaskStatus($taskId, $input['status']);
            return;
        }
        
        // Full task update
        if (empty($input['taskTitle'])) {
            $this->sendError('Task title is required', 400);
            return;
        }
        
        $data = [
            'id' => $taskId,
            'title' => trim($input['taskTitle']),
            'description' => isset($input['taskDescription']) ? trim($input['taskDescription']) : null,
            'priority' => isset($input['taskPriority']) ? $input['taskPriority'] : 'medium',
            'due_date' => !empty($input['taskDueDate']) ? $input['taskDueDate'] : null,
            'due_time' => !empty($input['taskDueTime']) ? $input['taskDueTime'] : null,
            'project_id' => !empty($input['taskProject']) ? (int)$input['taskProject'] : null,
            'tags' => isset($input['taskTags']) ? trim($input['taskTags']) : null
        ];
        
        // Validate priority
        if (!in_array($data['priority'], ['low', 'medium', 'high'])) {
            $data['priority'] = 'medium';
        }
        
        $sql = "
            UPDATE tasks 
            SET title = :title, 
                description = :description, 
                priority = :priority, 
                due_date = :due_date, 
                due_time = :due_time, 
                project_id = :project_id, 
                tags = :tags,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        
        $stmt = $this->db->query($sql, $data);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Task not found', 404);
            return;
        }
        
        $this->sendResponse(['message' => 'Task updated successfully']);
    }
    
    private function updateTaskStatus($taskId, $status) {
        // Validate status
        if (!in_array($status, ['pending', 'completed'])) {
            $this->sendError('Invalid status', 400);
            return;
        }
        
        $sql = "UPDATE tasks SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->query($sql, ['id' => $taskId, 'status' => $status]);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Task not found', 404);
            return;
        }
        
        $this->sendResponse(['message' => 'Task status updated successfully']);
    }
    
    private function deleteTask() {
        $taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($taskId <= 0) {
            $this->sendError('Valid task ID is required', 400);
            return;
        }
        
        $sql = "DELETE FROM tasks WHERE id = :id";
        $stmt = $this->db->query($sql, ['id' => $taskId]);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Task not found', 404);
            return;
        }
        
        $this->sendResponse(['message' => 'Task deleted successfully']);
    }
    
    private function getJsonInput() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON input', 400);
        }
        return $input ?: [];
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_PRETTY_PRINT);
        exit();
    }
    
    private function sendError($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit();
    }
}

// Handle the request
$api = new TasksAPI();
$api->handleRequest();

?>