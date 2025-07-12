<?php

session_start();

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
require_once 'auth.php';
require_once 'email.php';

class TasksAPI {
    private $db;
    private $auth;
    private $emailNotification;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->auth = new AuthAPI();
        $this->emailNotification = new EmailNotification();
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
        if (!$this->auth->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        $taskId = $_GET['id'] ?? null;
        
        if ($taskId) {
            // Get specific task
            $this->getTask($taskId, $userId, $role);
        } else {
            // Get tasks list
            $this->getTasksList($userId, $role);
        }
    }
    
    private function getTask($taskId, $userId, $role) {
        $sql = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   assigned_user.username as assigned_username,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   creator.username as creator_username,
                   creator.first_name as creator_first_name,
                   creator.last_name as creator_last_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE t.id = :task_id
        ";
        
        // Add user restriction for regular users
        if ($role !== 'admin') {
            $sql .= " AND (t.assigned_to = :user_id OR t.created_by = :user_id)";
        }
        
        $params = ['task_id' => $taskId];
        if ($role !== 'admin') {
            $params['user_id'] = $userId;
        }
        
        $stmt = $this->db->query($sql, $params);
        $task = $stmt->fetch();
        
        if (!$task) {
            $this->sendError('Task not found', 404);
            return;
        }
        
        $this->sendResponse(['task' => $task]);
    }
    
    private function getTasksList($userId, $role) {
        $filter = $_GET['filter'] ?? 'all';
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $project = $_GET['project'] ?? '';
        $assigned_to = $_GET['assigned_to'] ?? '';
        
        $sql = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   assigned_user.username as assigned_username,
                   assigned_user.first_name as assigned_first_name,
                   assigned_user.last_name as assigned_last_name,
                   creator.username as creator_username,
                   creator.first_name as creator_first_name,
                   creator.last_name as creator_last_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Role-based filtering
        if ($role !== 'admin') {
            if ($filter === 'assigned') {
                $sql .= " AND t.assigned_to = :user_id";
                $params['user_id'] = $userId;
            } elseif ($filter === 'created') {
                $sql .= " AND t.created_by = :user_id";
                $params['user_id'] = $userId;
            } else {
                // Default: show tasks assigned to user
                $sql .= " AND t.assigned_to = :user_id";
                $params['user_id'] = $userId;
            }
        }
        
        // Additional filters
        if ($status && in_array($status, ['pending', 'in_progress', 'completed'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }
        
        if ($priority && in_array($priority, ['low', 'medium', 'high'])) {
            $sql .= " AND t.priority = :priority";
            $params['priority'] = $priority;
        }
        
        if ($project) {
            $sql .= " AND t.project_id = :project_id";
            $params['project_id'] = $project;
        }
        
        if ($assigned_to && $role === 'admin') {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params['assigned_to'] = $assigned_to;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->query($sql, $params);
        $tasks = $stmt->fetchAll();
        
        $this->sendResponse(['tasks' => $tasks]);
    }
    
    private function createTask() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
            return;
        }
        
        // Only admins can create tasks and assign them to users
        if ($_SESSION['role'] !== 'admin') {
            $this->sendError('Admin access required to create tasks', 403);
            return;
        }
        
        $input = $this->getJsonInput();
        
        // Validate required fields
        if (empty($input['title'])) {
            $this->sendError('Task title is required', 400);
            return;
        }
        
        if (empty($input['assigned_to'])) {
            $this->sendError('Assigned user is required', 400);
            return;
        }
        
        // Validate assigned user exists
        $userCheck = $this->db->query("SELECT COUNT(*) FROM users WHERE id = :user_id AND status = 'active'", 
            ['user_id' => $input['assigned_to']])->fetchColumn();
        
        if ($userCheck === 0) {
            $this->sendError('Assigned user not found or inactive', 400);
            return;
        }
        
        // Validate project if provided
        if (!empty($input['project_id'])) {
            $projectCheck = $this->db->query("SELECT COUNT(*) FROM projects WHERE id = :project_id", 
                ['project_id' => $input['project_id']])->fetchColumn();
            
            if ($projectCheck === 0) {
                $this->sendError('Project not found', 400);
                return;
            }
        }
        
        // Prepare data
        $data = [
            'title' => trim($input['title']),
            'description' => isset($input['description']) ? trim($input['description']) : '',
            'priority' => isset($input['priority']) ? $input['priority'] : 'medium',
            'due_date' => !empty($input['due_date']) ? $input['due_date'] : null,
            'due_time' => !empty($input['due_time']) ? $input['due_time'] : null,
            'project_id' => !empty($input['project_id']) ? (int)$input['project_id'] : null,
            'assigned_to' => (int)$input['assigned_to'],
            'created_by' => $_SESSION['user_id'],
            'tags' => isset($input['tags']) ? trim($input['tags']) : null,
            'status' => 'pending'
        ];
        
        // Validate priority
        if (!in_array($data['priority'], ['low', 'medium', 'high'])) {
            $data['priority'] = 'medium';
        }
        
        $sql = "
            INSERT INTO tasks (title, description, priority, due_date, due_time, project_id, assigned_to, created_by, tags, status)
            VALUES (:title, :description, :priority, :due_date, :due_time, :project_id, :assigned_to, :created_by, :tags, :status)
        ";
        
        $this->db->query($sql, $data);
        $taskId = $this->db->lastInsertId();
        
        // Send email notification
        $this->emailNotification->sendTaskAssignmentEmail($taskId, $data['assigned_to'], $data['created_by']);
        
        $this->sendResponse([
            'message' => 'Task created and assigned successfully',
            'task_id' => $taskId
        ]);
    }
    
    private function updateTask() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
            return;
        }
        
        $input = $this->getJsonInput();
        
        if (empty($input['task_id'])) {
            $this->sendError('Task ID is required', 400);
            return;
        }
        
        $taskId = (int)$input['task_id'];
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        // Check if task exists and user has access
        $taskCheck = "SELECT * FROM tasks WHERE id = :task_id";
        $taskParams = ['task_id' => $taskId];
        
        if ($role !== 'admin') {
            $taskCheck .= " AND (assigned_to = :user_id OR created_by = :user_id)";
            $taskParams['user_id'] = $userId;
        }
        
        $stmt = $this->db->query($taskCheck, $taskParams);
        $existingTask = $stmt->fetch();
        
        if (!$existingTask) {
            $this->sendError('Task not found or access denied', 404);
            return;
        }
        
        // Check if it's a status-only update (users can update status of assigned tasks)
        if (isset($input['status']) && count($input) === 2) { // task_id + status
            $this->updateTaskStatus($taskId, $input['status'], $existingTask);
            return;
        }
        
        // Full task update (admin only)
        if ($role !== 'admin') {
            $this->sendError('Admin access required for full task updates', 403);
            return;
        }
        
        // Validate and prepare update data
        $updateFields = [];
        $params = ['task_id' => $taskId];
        
        if (!empty($input['title'])) {
            $updateFields[] = 'title = :title';
            $params['title'] = trim($input['title']);
        }
        
        if (isset($input['description'])) {
            $updateFields[] = 'description = :description';
            $params['description'] = trim($input['description']);
        }
        
        if (!empty($input['priority']) && in_array($input['priority'], ['low', 'medium', 'high'])) {
            $updateFields[] = 'priority = :priority';
            $params['priority'] = $input['priority'];
        }
        
        if (isset($input['due_date'])) {
            $updateFields[] = 'due_date = :due_date';
            $params['due_date'] = !empty($input['due_date']) ? $input['due_date'] : null;
        }
        
        if (isset($input['due_time'])) {
            $updateFields[] = 'due_time = :due_time';
            $params['due_time'] = !empty($input['due_time']) ? $input['due_time'] : null;
        }
        
        if (isset($input['project_id'])) {
            if (!empty($input['project_id'])) {
                $projectCheck = $this->db->query("SELECT COUNT(*) FROM projects WHERE id = :project_id", 
                    ['project_id' => $input['project_id']])->fetchColumn();
                
                if ($projectCheck === 0) {
                    $this->sendError('Project not found', 400);
                    return;
                }
            }
            $updateFields[] = 'project_id = :project_id';
            $params['project_id'] = !empty($input['project_id']) ? (int)$input['project_id'] : null;
        }
        
        if (isset($input['tags'])) {
            $updateFields[] = 'tags = :tags';
            $params['tags'] = trim($input['tags']);
        }
        
        // Handle reassignment
        if (!empty($input['assigned_to']) && $input['assigned_to'] != $existingTask['assigned_to']) {
            $userCheck = $this->db->query("SELECT COUNT(*) FROM users WHERE id = :user_id AND status = 'active'", 
                ['user_id' => $input['assigned_to']])->fetchColumn();
            
            if ($userCheck === 0) {
                $this->sendError('Assigned user not found or inactive', 400);
                return;
            }
            
            $updateFields[] = 'assigned_to = :assigned_to';
            $params['assigned_to'] = (int)$input['assigned_to'];
            
            // Send email notification for reassignment
            $this->emailNotification->sendTaskAssignmentEmail($taskId, $input['assigned_to'], $userId);
        }
        
        if (empty($updateFields)) {
            $this->sendError('No fields to update', 400);
            return;
        }
        
        $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
        
        $sql = "UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE id = :task_id";
        $this->db->query($sql, $params);
        
        $this->sendResponse(['message' => 'Task updated successfully']);
    }
    
    private function updateTaskStatus($taskId, $status, $existingTask) {
        // Validate status
        if (!in_array($status, ['pending', 'in_progress', 'completed'])) {
            $this->sendError('Invalid status', 400);
            return;
        }
        
        $sql = "UPDATE tasks SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :task_id";
        $this->db->query($sql, ['task_id' => $taskId, 'status' => $status]);
        
        // Send notification about status change
        $statusMessage = "Task status updated to: " . ucfirst(str_replace('_', ' ', $status));
        $this->emailNotification->sendTaskUpdateEmail($taskId, $existingTask['assigned_to'], 'task_updated', $statusMessage);
        
        $this->sendResponse(['message' => 'Task status updated successfully']);
    }
    
    private function deleteTask() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
            return;
        }
        
        // Only admins can delete tasks
        if ($_SESSION['role'] !== 'admin') {
            $this->sendError('Admin access required', 403);
            return;
        }
        
        $taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($taskId <= 0) {
            $this->sendError('Valid task ID is required', 400);
            return;
        }
        
        $sql = "DELETE FROM tasks WHERE id = :task_id";
        $stmt = $this->db->query($sql, ['task_id' => $taskId]);
        
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