<?php
/**
 * Order Management Class
 * Handles order operations and status management
 */

class Order {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all orders with pagination
     */
    public function getAllOrders($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id";
        $params = [];

        if ($status) {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get order by ID with items
     */
    public function getOrderById($id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->getOrderItems($id);
        }

        return $order;
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("SELECT oi.*, p.name as current_product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Create new order
     */
    public function createOrder($data, $items) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Generate order number
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

            // Create order
            $stmt = $this->db->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $orderNumber,
                $data['customer_name'],
                $data['customer_email'],
                $data['customer_phone'] ?? null,
                $data['shipping_address'] ?? null,
                $data['total_amount'],
                $data['status'] ?? 'pending',
                $data['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Add order items
            foreach ($items as $item) {
                $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_details, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $orderId,
                    $item['product_id'] ?? null,
                    $item['variant_id'] ?? null,
                    $item['product_name'],
                    $item['variant_details'] ?? null,
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price']
                ]);

                // Update variant stock if applicable
                if (!empty($item['variant_id'])) {
                    $this->updateVariantStock($item['variant_id'], -$item['quantity']);
                }
            }

            $this->db->getConnection()->commit();
            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($id, $status, $notes = null) {
        try {
            $sql = "UPDATE orders SET status = ?, updated_at = NOW()";
            $params = [$status];

            if ($notes !== null) {
                $sql .= ", notes = ?";
                $params[] = $notes;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $this->db->prepare($sql);
            return ['success' => $stmt->execute($params)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete order
     */
    public function deleteOrder($id) {
        try {
            // Get order items to restore stock
            $items = $this->getOrderItems($id);

            $this->db->getConnection()->beginTransaction();

            // Restore variant stock
            foreach ($items as $item) {
                if (!empty($item['variant_id'])) {
                    $this->updateVariantStock($item['variant_id'], $item['quantity']);
                }
            }

            // Delete order (items will be deleted by CASCADE)
            $stmt = $this->db->prepare("DELETE FROM orders WHERE id = ?");
            $result = $stmt->execute([$id]);

            $this->db->getConnection()->commit();
            return ['success' => $result];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get order statistics
     */
    public function getOrderStats() {
        $stats = [];

        // Total orders
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders");
        $stmt->execute();
        $stats['total_orders'] = $stmt->fetchColumn();

        // Pending orders
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $stmt->execute();
        $stats['pending_orders'] = $stmt->fetchColumn();

        // Total revenue
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('shipped', 'delivered')");
        $stmt->execute();
        $stats['total_revenue'] = $stmt->fetchColumn();

        // Today's orders
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $stats['today_orders'] = $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Update variant stock
     */
    private function updateVariantStock($variantId, $change) {
        $stmt = $this->db->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?");
        return $stmt->execute([$change, $variantId]);
    }
}