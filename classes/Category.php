<?php
/**
 * Category Management Class
 * Handles CRUD operations for product categories
 */

class Category {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all categories
     */
    public function getAllCategories() {
        $stmt = $this->db->prepare("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get category by ID
     */
    public function getCategoryById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create new category
     */
    public function createCategory($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['name'],
                $this->createSlug($data['name']),
                $data['description'],
                $data['image'] ?? null
            ]);

            return ['success' => $result, 'category_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update category
     */
    public function updateCategory($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE categories SET name = ?, description = ?, image = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $data['name'],
                $data['description'],
                $data['image'] ?? null,
                $id
            ]);

            return ['success' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory($id) {
        try {
            // Check if category has products
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$id]);
            $productCount = $stmt->fetchColumn();

            if ($productCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete category with products'];
            }

            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            return ['success' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Toggle category status
     */
    public function toggleStatus($id) {
        $stmt = $this->db->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Create URL-friendly slug
     */
    private function createSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return $text;
    }
}