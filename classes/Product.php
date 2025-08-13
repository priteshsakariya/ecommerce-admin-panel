<?php
/**
 * Product Management Class
 * Handles CRUD operations for products and variants
 */

class Product {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all products with category info
     */
    public function getAllProducts($limit = null, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, 
                       COUNT(DISTINCT pi.id) as image_count,
                       COUNT(DISTINCT pv.id) as variant_count,
                       COALESCE(
                           MAX(CASE WHEN pi.is_primary = 1 THEN pi.image_url END),
                           MIN(pi.image_url)
                       ) AS primary_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id
                LEFT JOIN product_variants pv ON p.id = pv.product_id
                GROUP BY p.id 
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get product by ID with full details
     */
    public function getProductById($id) {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $product['images'] = $this->getProductImages($id);
            $product['variants'] = $this->getProductVariants($id);
        }

        return $product;
    }

    /**
     * Create new product
     */
    public function createProduct($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO products (name, slug, description, base_price, category_id, source_link) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['name'],
                $this->createSlug($data['name']),
                $data['description'],
                $data['base_price'],
                $data['category_id'] ?: null,
                $data['source_link']
            ]);

            $productId = $this->db->lastInsertId();

            // Add images if provided
            if (!empty($data['images'])) {
                $this->addProductImages($productId, $data['images']);
            }

            $this->db->getConnection()->commit();
            return ['success' => true, 'product_id' => $productId];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update product
     */
    public function updateProduct($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, base_price = ?, category_id = ?, source_link = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $data['name'],
                $data['description'],
                $data['base_price'],
                $data['category_id'] ?: null,
                $data['source_link'],
                $id
            ]);

            return ['success' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get product images
     */
    public function getProductImages($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Add product images
     */
    public function addProductImages($productId, $images) {
        // Check if a primary image already exists
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
        $stmtCheck->execute([$productId]);
        $hasPrimary = (int)$stmtCheck->fetchColumn() > 0;

        foreach ($images as $index => $image) {
            $isPrimary = 0;
            if (!$hasPrimary && $index === 0) {
                $isPrimary = 1;
                $hasPrimary = true;
            }

            $stmt = $this->db->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $productId,
                $image['url'],
                $image['alt_text'] ?? '',
                $isPrimary,
                $index
            ]);
        }
    }

    /**
     * Get product variants
     */
    public function getProductVariants($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_name, variant_value");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete product images by their IDs and remove files from storage if local
     */
    public function deleteProductImages($imageIds) {
        if (empty($imageIds)) {
            return ['success' => true];
        }

        // Fetch image URLs before deletion
        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));
        $stmt = $this->db->prepare("SELECT id, image_url FROM product_images WHERE id IN ($placeholders)");
        $stmt->execute($imageIds);
        $images = $stmt->fetchAll();

        // Delete DB rows
        $stmtDel = $this->db->prepare("DELETE FROM product_images WHERE id IN ($placeholders)");
        $ok = $stmtDel->execute($imageIds);

        // Attempt to unlink files stored locally under UPLOAD_PATH
        if ($ok) {
            foreach ($images as $img) {
                $path = $img['image_url'] ?? '';
                if ($path && strpos($path, UPLOAD_PATH) === 0 && file_exists($path)) {
                    @unlink($path);
                }
            }
        }

        return ['success' => $ok];
    }

    /**
     * Set a specific image as the primary for a given product
     */
    public function setPrimaryImage($productId, $imageId) {
        try {
            $this->db->getConnection()->beginTransaction();

            $stmtClear = $this->db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
            $stmtClear->execute([$productId]);

            $stmtSet = $this->db->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
            $ok = $stmtSet->execute([$imageId, $productId]);

            $this->db->getConnection()->commit();
            return ['success' => $ok];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create product variant
     */
    public function createVariant($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO product_variants (product_id, variant_name, variant_value, sku, price_adjustment, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['product_id'],
                $data['variant_name'],
                $data['variant_value'],
                $data['sku'],
                $data['price_adjustment'] ?? 0,
                $data['stock_quantity'] ?? 0
            ]);

            return ['success' => $result, 'variant_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update variant stock
     */
    public function updateVariantStock($variantId, $quantity) {
        $stmt = $this->db->prepare("UPDATE product_variants SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$quantity, $variantId]);
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems($threshold = 10) {
        $stmt = $this->db->prepare("SELECT pv.*, p.name as product_name FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE pv.stock_quantity <= ? AND pv.is_active = 1 ORDER BY pv.stock_quantity");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
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
        return $text . '-' . time();
    }

    /**
     * Get total products count
     */
    public function getTotalCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}