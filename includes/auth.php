<?php

require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_firm_id'] = $user['firm_id'];
            $_SESSION['user_credit'] = $user['credit'];
            
            $this->logAction($user['id'], 'login', 'User logged in');
            return true;
        }
        
        return false;
    }
    
    public function register($name, $email, $password, $role = 'user') {

        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($existingUser) {
            return false; 
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'credit' => 100.00 
        ]);
        
        if ($userId) {
            $this->logAction($userId, 'register', 'New user registered');
            return $userId;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'firm_id' => $_SESSION['user_firm_id'] ?? null,
            'credit' => $_SESSION['user_credit']
        ];
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['user_role'] === $role;
    }
    
    public function hasAnyRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return in_array($_SESSION['user_role'], $roles);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!in_array($_SESSION['user_role'], (array)$role)) {
            header('Location: /unauthorized.php');
            exit;
        }
    }
    
    public function updateCredit($userId, $amount) {
        $result = $this->db->update(
            'users',
            ['credit' => $amount],
            'id = ?',
            [$userId]
        );
        
        if ($result) {
            $_SESSION['user_credit'] = $amount;
        }
        
        return $result;
    }
    
    public function addCredit($userId, $amount) {
        $user = $this->db->fetchOne("SELECT credit FROM users WHERE id = ?", [$userId]);
        if ($user) {
            $newCredit = $user['credit'] + $amount;
            return $this->updateCredit($userId, $newCredit);
        }
        return false;
    }
    
    private function logAction($userId, $action, $details = '') {
        $this->db->insert('logs', [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public function validatePassword($password) {
        return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
    }
    
    public function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>
