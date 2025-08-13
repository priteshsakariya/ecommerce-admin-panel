<?php
/**
 * Review Management Class
 * Handles product reviews moderation
 */

class Review {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all reviews
     */
    public function getAllReviews($status = null) {
        $sql = "SELECT r.*, p.name as product_name, c.name as customer_name
                FROM reviews r 
                LEFT JOIN products p ON r.product_id = p.id
                LEFT JOIN customers c ON r.customer_id = c.id";
        $params = [];
        if ($status) {
            $sql .= " WHERE r.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getReviewById($id) {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createReview($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO reviews (product_id, customer_id, rating, title, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $result = $stmt->execute([
                $data['product_id'],
                $data['customer_id'] ?? null,
                $data['rating'],
                $data['title'] ?? null,
                $data['comment']
            ]);
            return ['success' => $result, 'review_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE reviews SET status = ?, updated_at = NOW() WHERE id = ?");
        return ['success' => $stmt->execute([$status, $id])];
    }

    public function deleteReview($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM reviews WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}


