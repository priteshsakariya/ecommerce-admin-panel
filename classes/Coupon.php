<?php
/**
 * Coupon Management Class
 * Handles coupon CRUD and activation
 */

class Coupon {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all coupons
     */
    public function getAllCoupons() {
        $stmt = $this->db->prepare("SELECT * FROM coupons ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCouponById($id) {
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createCoupon($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, start_date, end_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                strtoupper(trim($data['code'])),
                $data['description'] ?? null,
                $data['discount_type'], // percent|fixed
                $data['discount_value'],
                $data['min_order_amount'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['usage_limit'] ?? null,
                $data['is_active'] ?? 1
            ]);
            return ['success' => $result, 'coupon_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateCoupon($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE coupons SET code = ?, description = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, start_date = ?, end_date = ?, usage_limit = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                strtoupper(trim($data['code'])),
                $data['description'] ?? null,
                $data['discount_type'],
                $data['discount_value'],
                $data['min_order_amount'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['usage_limit'] ?? null,
                $data['is_active'] ?? 1,
                $id
            ]);
            return ['success' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteCoupon($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = ?");
            return ['success' => $stmt->execute([$id])];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function toggleStatus($id) {
        $stmt = $this->db->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }
}


