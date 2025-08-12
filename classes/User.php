<?php
/**
 * User Management Class
 * Handles user CRUD operations for admin panel
 */

class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all users
     */
    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT id, username, email, role, first_name, last_name, is_active, last_login, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT id, username, email, role, first_name, last_name, is_active, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create new user
     */
    public function createUser($data) {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['role'] ?? 'editor',
                $data['first_name'],
                $data['last_name'],
                $data['is_active'] ?? true
            ]);

            return ['success' => $result, 'user_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update user
     */
    public function updateUser($id, $data) {
        try {
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, first_name = ?, last_name = ?, is_active = ?";
            $params = [
                $data['username'],
                $data['email'],
                $data['role'],
                $data['first_name'],
                $data['last_name'],
                $data['is_active'] ?? true
            ];

            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $sql .= ", updated_at = NOW() WHERE id = ?";
            $params[] = $id;

            $stmt = $this->db->prepare($sql);
            return ['success' => $stmt->execute($params)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        try {
            // Prevent deleting the last admin
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $userRole = $stmt->fetchColumn();

            if ($userRole === 'admin' && $adminCount <= 1) {
                return ['success' => false, 'message' => 'Cannot delete the last admin user'];
            }

            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Toggle user status
     */
    public function toggleUserStatus($id) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}