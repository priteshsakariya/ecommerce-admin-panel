<?php
/**
 * Authentication Class
 * Handles user login, logout, and session management
 */

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Authenticate user login
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password, role, first_name, last_name, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_active']) {
                    return ['success' => false, 'message' => 'Account is deactivated'];
                }

                // Update last login
                $updateStmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['login_time'] = time();

                return ['success' => true, 'user' => $user];
            }

            return ['success' => false, 'message' => 'Invalid username or password'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }

    /**
     * Check if session is valid
     */
    public function isValidSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isValidSession()) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    /**
     * Create new user (admin only)
     */
    public function createUser($data) {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['role'],
                $data['first_name'],
                $data['last_name']
            ]);

            return ['success' => $result, 'user_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all users
     */
    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT id, username, email, role, first_name, last_name, is_active, last_login, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}