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

class UsersAPI {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->auth = new AuthAPI();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->getUsers();
                    break;
                case 'POST':
                    $this->createUser();
                    break;
                case 'PUT':
                    $this->updateUser();
                    break;
                case 'DELETE':
                    $this->deleteUser();
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    private function getUsers() {
        // Admin can see all users, regular users can only see themselves
        if (!$this->auth->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
            return;
        }
        
        $userId = $_GET['id'] ?? null;
        
        if ($userId) {
            // Get specific user
            if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $userId) {
                $this->sendError('Access denied', 403);
                return;
            }
            
            $sql = "
                SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role, u.status, u.created_at,
                       COUNT(DISTINCT t.id) as total_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.id END) as pending_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as in_progress_tasks
                FROM users u
                LEFT JOIN tasks t ON u.id = t.assigned_to
                WHERE u.id = :user_id
                GROUP BY u.id
            ";
            
            $stmt = $this->db->query($sql, ['user_id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->sendError('User not found', 404);
                return;
            }
            
            $this->sendResponse(['user' => $user]);
        } else {
            // Get all users (admin only)
            if ($_SESSION['role'] !== 'admin') {
                $this->sendError('Admin access required', 403);
                return;
            }
            
            $sql = "
                SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role, u.status, u.created_at,
                       COUNT(DISTINCT t.id) as total_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.id END) as pending_tasks,
                       COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as in_progress_tasks
                FROM users u
                LEFT JOIN tasks t ON u.id = t.assigned_to
                GROUP BY u.id
                ORDER BY u.created_at DESC
            ";
            
            $stmt = $this->db->query($sql);
            $users = $stmt->fetchAll();
            
            $this->sendResponse(['users' => $users]);
        }
    }
    
    private function createUser() {
        // Only admins can create users
        if (!$this->auth->isAuthenticated() || $_SESSION['role'] !== 'admin') {
            $this->sendError('Admin access required', 403);
            return;
        }
        
        $input = $this->getJsonInput();
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError(ucfirst($field) . ' is required', 400);
                return;
            }
        }
        
        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email format', 400);
            return;
        }
        
        // Validate role
        $role = $input['role'] ?? 'user';
        if (!in_array($role, ['admin', 'user'])) {
            $this->sendError('Invalid role', 400);
            return;
        }
        
        // Check if username or email already exists
        $checkSql = "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email";
        $count = $this->db->query($checkSql, [
            'username' => $input['username'],
            'email' => $input['email']
        ])->fetchColumn();
        
        if ($count > 0) {
            $this->sendError('Username or email already exists', 400);
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "
            INSERT INTO users (username, email, password, first_name, last_name, role, status) 
            VALUES (:username, :email, :password, :first_name, :last_name, :role, :status)
        ";
        
        $this->db->query($sql, [
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $hashedPassword,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'role' => $role,
            'status' => $input['status'] ?? 'active'
        ]);
        
        $userId = $this->db->lastInsertId();
        
        $this->sendResponse([
            'message' => 'User created successfully',
            'user_id' => $userId
        ]);
    }
    
    private function updateUser() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
            return;
        }
        
        $input = $this->getJsonInput();
        
        if (empty($input['user_id'])) {
            $this->sendError('User ID is required', 400);
            return;
        }
        
        $userId = (int)$input['user_id'];
        
        // Users can only update themselves, admins can update anyone
        if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $userId) {
            $this->sendError('Access denied', 403);
            return;
        }
        
        // Check if user exists
        $checkSql = "SELECT COUNT(*) FROM users WHERE id = :user_id";
        $count = $this->db->query($checkSql, ['user_id' => $userId])->fetchColumn();
        
        if ($count === 0) {
            $this->sendError('User not found', 404);
            return;
        }
        
        // Build update query dynamically
        $updateFields = [];
        $params = ['user_id' => $userId];
        
        if (!empty($input['username'])) {
            // Check if username already exists (excluding current user)
            $checkSql = "SELECT COUNT(*) FROM users WHERE username = :username AND id != :user_id";
            $count = $this->db->query($checkSql, [
                'username' => $input['username'],
                'user_id' => $userId
            ])->fetchColumn();
            
            if ($count > 0) {
                $this->sendError('Username already exists', 400);
                return;
            }
            
            $updateFields[] = 'username = :username';
            $params['username'] = $input['username'];
        }
        
        if (!empty($input['email'])) {
            // Validate email format
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Invalid email format', 400);
                return;
            }
            
            // Check if email already exists (excluding current user)
            $checkSql = "SELECT COUNT(*) FROM users WHERE email = :email AND id != :user_id";
            $count = $this->db->query($checkSql, [
                'email' => $input['email'],
                'user_id' => $userId
            ])->fetchColumn();
            
            if ($count > 0) {
                $this->sendError('Email already exists', 400);
                return;
            }
            
            $updateFields[] = 'email = :email';
            $params['email'] = $input['email'];
        }
        
        if (!empty($input['first_name'])) {
            $updateFields[] = 'first_name = :first_name';
            $params['first_name'] = $input['first_name'];
        }
        
        if (!empty($input['last_name'])) {
            $updateFields[] = 'last_name = :last_name';
            $params['last_name'] = $input['last_name'];
        }
        
        // Only admins can change role and status
        if ($_SESSION['role'] === 'admin') {
            if (!empty($input['role']) && in_array($input['role'], ['admin', 'user'])) {
                $updateFields[] = 'role = :role';
                $params['role'] = $input['role'];
            }
            
            if (!empty($input['status']) && in_array($input['status'], ['active', 'inactive'])) {
                $updateFields[] = 'status = :status';
                $params['status'] = $input['status'];
            }
        }
        
        // Handle password change
        if (!empty($input['password'])) {
            $updateFields[] = 'password = :password';
            $params['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            $this->sendError('No fields to update', 400);
            return;
        }
        
        $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
        $this->db->query($sql, $params);
        
        $this->sendResponse(['message' => 'User updated successfully']);
    }
    
    private function deleteUser() {
        // Only admins can delete users
        if (!$this->auth->isAuthenticated() || $_SESSION['role'] !== 'admin') {
            $this->sendError('Admin access required', 403);
            return;
        }
        
        $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($userId <= 0) {
            $this->sendError('Valid user ID is required', 400);
            return;
        }
        
        // Prevent admin from deleting themselves
        if ($userId == $_SESSION['user_id']) {
            $this->sendError('Cannot delete your own account', 400);
            return;
        }
        
        // Check if user exists
        $checkSql = "SELECT COUNT(*) FROM users WHERE id = :user_id";
        $count = $this->db->query($checkSql, ['user_id' => $userId])->fetchColumn();
        
        if ($count === 0) {
            $this->sendError('User not found', 404);
            return;
        }
        
        // Check if user has tasks
        $taskCountSql = "SELECT COUNT(*) FROM tasks WHERE assigned_to = :user_id OR created_by = :user_id";
        $taskCount = $this->db->query($taskCountSql, ['user_id' => $userId])->fetchColumn();
        
        if ($taskCount > 0) {
            $this->sendError('Cannot delete user with assigned tasks. Please reassign tasks first.', 400);
            return;
        }
        
        // Delete user
        $sql = "DELETE FROM users WHERE id = :user_id";
        $this->db->query($sql, ['user_id' => $userId]);
        
        $this->sendResponse(['message' => 'User deleted successfully']);
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
$api = new UsersAPI();
$api->handleRequest();

?>