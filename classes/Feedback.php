<?php
/**
 * Feedback Management Class
 * Handles customer feedback tickets
 */

class Feedback {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all feedback
     */
    public function getAllFeedback($status = null) {
        $sql = "SELECT f.*, c.name as customer_name, c.email as customer_email
                FROM feedback f 
                LEFT JOIN customers c ON f.customer_id = c.id";
        $params = [];
        if ($status) {
            $sql .= " WHERE f.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY f.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFeedbackById($id) {
        $stmt = $this->db->prepare("SELECT * FROM feedback WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createFeedback($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO feedback (customer_id, subject, message, status) VALUES (?, ?, ?, 'new')");
            $result = $stmt->execute([
                $data['customer_id'] ?? null,
                $data['subject'],
                $data['message']
            ]);
            return ['success' => $result, 'feedback_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE feedback SET status = ?, updated_at = NOW() WHERE id = ?");
        return ['success' => $stmt->execute([$status, $id])];
    }

    public function deleteFeedback($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM feedback WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}


