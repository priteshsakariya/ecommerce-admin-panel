<?php
/**
 * Customer Management Class
 * Handles CRUD operations for customers
 */

class Customer {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all customers with basic stats
     */
    public function getAllCustomers() {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM orders o WHERE CONVERT(o.customer_email USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(c.email USING utf8mb4) COLLATE utf8mb4_unicode_ci) AS total_orders,
                       (SELECT COALESCE(SUM(total_amount), 0) FROM orders o2 WHERE CONVERT(o2.customer_email USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(c.email USING utf8mb4) COLLATE utf8mb4_unicode_ci AND o2.status IN ('shipped','delivered')) AS total_spent
                FROM customers c 
                ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get customer by ID
     */
    public function getCustomerById($id) {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a customer
     */
    public function createCustomer($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO customers (name, email, phone, address, is_active) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['is_active'] ?? 1
            ]);
            return ['success' => $result, 'customer_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update a customer
     */
    public function updateCustomer($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['is_active'] ?? 1,
                $id
            ]);
            return ['success' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete customer
     */
    public function deleteCustomer($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM customers WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Toggle customer active status
     */
    public function toggleStatus($id) {
        $stmt = $this->db->prepare("UPDATE customers SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }
}


