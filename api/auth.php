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

class AuthAPI {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'POST':
                    if ($action === 'login') {
                        $this->login();
                    } elseif ($action === 'register') {
                        $this->register();
                    } elseif ($action === 'logout') {
                        $this->logout();
                    } else {
                        $this->sendError('Invalid action', 400);
                    }
                    break;
                case 'GET':
                    if ($action === 'profile') {
                        $this->getProfile();
                    } elseif ($action === 'check') {
                        $this->checkAuth();
                    } else {
                        $this->sendError('Invalid action', 400);
                    }
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    private function login() {
        $input = $this->getJsonInput();
        
        if (empty($input['username']) || empty($input['password'])) {
            $this->sendError('Username and password are required', 400);
            return;
        }
        
        // Find user by username or email
        $sql = "SELECT * FROM users WHERE (username = :username OR email = :username) AND status = 'active'";
        $stmt = $this->db->query($sql, ['username' => $input['username']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($input['password'], $user['password'])) {
            $this->sendError('Invalid username or password', 401);
            return;
        }
        
        // Create session
        $sessionId = $this->generateSessionId();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $sessionSql = "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sessionSql, [
            $sessionId,
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $expiresAt
        ]);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['session_id'] = $sessionId;
        
        // Remove sensitive data
        unset($user['password']);
        
        $this->sendResponse([
            'message' => 'Login successful',
            'user' => $user,
            'session_id' => $sessionId
        ]);
    }
    
    private function register() {
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
            VALUES (:username, :email, :password, :first_name, :last_name, :role, 'active')
        ";
        
        $this->db->query($sql, [
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $hashedPassword,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'role' => $input['role'] ?? 'user'
        ]);
        
        $userId = $this->db->lastInsertId();
        
        $this->sendResponse([
            'message' => 'User registered successfully',
            'user_id' => $userId
        ]);
    }
    
    private function logout() {
        if (isset($_SESSION['session_id'])) {
            // Delete session from database
            $sql = "DELETE FROM user_sessions WHERE id = :session_id";
            $this->db->query($sql, ['session_id' => $_SESSION['session_id']]);
        }
        
        // Destroy session
        session_destroy();
        
        $this->sendResponse(['message' => 'Logout successful']);
    }
    
    private function getProfile() {
        if (!$this->isAuthenticated()) {
            $this->sendError('Not authenticated', 401);
            return;
        }
        
        $sql = "SELECT id, username, email, first_name, last_name, role, status, created_at FROM users WHERE id = :user_id";
        $stmt = $this->db->query($sql, ['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $this->sendError('User not found', 404);
            return;
        }
        
        $this->sendResponse(['user' => $user]);
    }
    
    private function checkAuth() {
        if ($this->isAuthenticated()) {
            $this->sendResponse([
                'authenticated' => true,
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role']
            ]);
        } else {
            $this->sendResponse(['authenticated' => false]);
        }
    }
    
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
            return false;
        }
        
        // Check if session exists and is valid
        $sql = "SELECT COUNT(*) FROM user_sessions WHERE id = :session_id AND user_id = :user_id AND expires_at > datetime('now')";
        $count = $this->db->query($sql, [
            'session_id' => $_SESSION['session_id'],
            'user_id' => $_SESSION['user_id']
        ])->fetchColumn();
        
        return $count > 0;
    }
    
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->sendError('Authentication required', 401);
        }
    }
    
    public function requireAdmin() {
        $this->requireAuth();
        if ($_SESSION['role'] !== 'admin') {
            $this->sendError('Admin access required', 403);
        }
    }
    
    private function generateSessionId() {
        return bin2hex(random_bytes(32));
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
$api = new AuthAPI();
$api->handleRequest();

?>