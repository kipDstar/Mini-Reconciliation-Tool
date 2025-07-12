<?php

class EmailNotification {
    private $from_email;
    private $from_name;
    private $smtp_enabled;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $db;
    
    public function __construct() {
        // Email configuration - these should be in environment variables in production
        $this->from_email = 'admin@taskflow.com';
        $this->from_name = 'TaskFlow System';
        $this->smtp_enabled = false; // Set to true if you want to use SMTP
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_port = 587;
        $this->smtp_username = '';
        $this->smtp_password = '';
        
        $this->db = getDatabase();
    }
    
    public function sendTaskAssignmentEmail($taskId, $assignedUserId, $assignedByUserId) {
        try {
            // Get task details
            $taskSql = "
                SELECT t.*, p.name as project_name, 
                       assigned_user.email as assigned_email,
                       assigned_user.first_name as assigned_first_name,
                       assigned_user.last_name as assigned_last_name,
                       creator.first_name as creator_first_name,
                       creator.last_name as creator_last_name
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE t.id = :task_id
            ";
            
            $stmt = $this->db->query($taskSql, ['task_id' => $taskId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            // Prepare email content
            $subject = "New Task Assigned: " . $task['title'];
            $assignedName = $task['assigned_first_name'] . ' ' . $task['assigned_last_name'];
            $creatorName = $task['creator_first_name'] . ' ' . $task['creator_last_name'];
            
            $message = $this->generateTaskAssignmentEmailTemplate($task, $assignedName, $creatorName);
            
            // Send email
            $emailSent = $this->sendEmail($task['assigned_email'], $subject, $message);
            
            // Log notification
            $this->logNotification($taskId, $assignedUserId, 'task_assigned', 
                "You have been assigned a new task: " . $task['title'], $emailSent);
            
            return $emailSent;
            
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendTaskUpdateEmail($taskId, $userId, $updateType, $message) {
        try {
            // Get task and user details
            $taskSql = "
                SELECT t.*, p.name as project_name,
                       u.email, u.first_name, u.last_name
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.id = :task_id
            ";
            
            $stmt = $this->db->query($taskSql, ['task_id' => $taskId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            $subject = "Task Updated: " . $task['title'];
            $userName = $task['first_name'] . ' ' . $task['last_name'];
            
            $emailMessage = $this->generateTaskUpdateEmailTemplate($task, $userName, $message);
            
            // Send email
            $emailSent = $this->sendEmail($task['email'], $subject, $emailMessage);
            
            // Log notification
            $this->logNotification($taskId, $userId, $updateType, $message, $emailSent);
            
            return $emailSent;
            
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendEmail($to, $subject, $message) {
        if ($this->smtp_enabled) {
            return $this->sendSMTPEmail($to, $subject, $message);
        } else {
            return $this->sendSimpleEmail($to, $subject, $message);
        }
    }
    
    private function sendSimpleEmail($to, $subject, $message) {
        $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Reply-To: {$this->from_email}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    private function sendSMTPEmail($to, $subject, $message) {
        // This is a placeholder for SMTP implementation
        // In production, you would use a library like PHPMailer or SwiftMailer
        
        // For now, just log the email attempt
        error_log("SMTP Email would be sent to: $to, Subject: $subject");
        return true; // Return true for demo purposes
    }
    
    private function generateTaskAssignmentEmailTemplate($task, $assignedName, $creatorName) {
        $dueDate = $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No deadline';
        $dueTime = $task['due_time'] ? date('g:i A', strtotime($task['due_time'])) : '';
        $priority = ucfirst($task['priority']);
        $project = $task['project_name'] ? $task['project_name'] : 'No project';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .task-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .priority-{$task['priority']} { color: " . $this->getPriorityColor($task['priority']) . "; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 New Task Assigned</h1>
                </div>
                <div class='content'>
                    <p>Hello {$assignedName},</p>
                    <p>You have been assigned a new task by {$creatorName}.</p>
                    
                    <div class='task-details'>
                        <h3>{$task['title']}</h3>
                        <p><strong>Description:</strong> {$task['description']}</p>
                        <p><strong>Priority:</strong> <span class='priority-{$task['priority']}'>{$priority}</span></p>
                        <p><strong>Project:</strong> {$project}</p>
                        <p><strong>Due Date:</strong> {$dueDate} {$dueTime}</p>
                        <p><strong>Status:</strong> " . ucfirst(str_replace('_', ' ', $task['status'])) . "</p>
                    </div>
                    
                    <p>Please log in to TaskFlow to view and manage your tasks.</p>
                    
                    <div class='footer'>
                        <p>Best regards,<br>TaskFlow Team</p>
                        <p><small>This is an automated notification. Please do not reply to this email.</small></p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function generateTaskUpdateEmailTemplate($task, $userName, $updateMessage) {
        $dueDate = $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No deadline';
        $priority = ucfirst($task['priority']);
        $project = $task['project_name'] ? $task['project_name'] : 'No project';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .task-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .update-message { background: #e8f4f8; padding: 10px; border-left: 4px solid #667eea; margin: 10px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📝 Task Updated</h1>
                </div>
                <div class='content'>
                    <p>Hello {$userName},</p>
                    
                    <div class='update-message'>
                        <p><strong>Update:</strong> {$updateMessage}</p>
                    </div>
                    
                    <div class='task-details'>
                        <h3>{$task['title']}</h3>
                        <p><strong>Description:</strong> {$task['description']}</p>
                        <p><strong>Priority:</strong> {$priority}</p>
                        <p><strong>Project:</strong> {$project}</p>
                        <p><strong>Due Date:</strong> {$dueDate}</p>
                        <p><strong>Status:</strong> " . ucfirst(str_replace('_', ' ', $task['status'])) . "</p>
                    </div>
                    
                    <p>Please log in to TaskFlow to view the updated task details.</p>
                    
                    <div class='footer'>
                        <p>Best regards,<br>TaskFlow Team</p>
                        <p><small>This is an automated notification. Please do not reply to this email.</small></p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPriorityColor($priority) {
        switch ($priority) {
            case 'high':
                return '#f56565';
            case 'medium':
                return '#ed8936';
            case 'low':
                return '#48bb78';
            default:
                return '#666';
        }
    }
    
    private function logNotification($taskId, $userId, $type, $message, $emailSent = false) {
        $sql = "
            INSERT INTO task_notifications (task_id, user_id, type, message, email_sent) 
            VALUES (:task_id, :user_id, :type, :message, :email_sent)
        ";
        
        $this->db->query($sql, [
            'task_id' => $taskId,
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'email_sent' => $emailSent ? 1 : 0
        ]);
    }
    
    public function getNotifications($userId, $limit = 10) {
        $sql = "
            SELECT n.*, t.title as task_title
            FROM task_notifications n
            LEFT JOIN tasks t ON n.task_id = t.id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->query($sql, ['user_id' => $userId, 'limit' => $limit]);
        return $stmt->fetchAll();
    }
    
    public function markNotificationAsRead($notificationId, $userId) {
        $sql = "
            UPDATE task_notifications 
            SET is_read = 1 
            WHERE id = :notification_id AND user_id = :user_id
        ";
        
        $this->db->query($sql, [
            'notification_id' => $notificationId,
            'user_id' => $userId
        ]);
    }
    
    public function getUnreadNotificationCount($userId) {
        $sql = "SELECT COUNT(*) FROM task_notifications WHERE user_id = :user_id AND is_read = 0";
        return $this->db->query($sql, ['user_id' => $userId])->fetchColumn();
    }
}

?>