<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Products';
$productClass = new Product($db);
$categoryClass = new Category($db);

$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                // Handle uploads
                $uploadedImages = handleProductImageUploads($_FILES['images'] ?? null);
                if (!empty($uploadedImages['error'])) {
                    $message = '<div class="alert alert-danger">' . htmlspecialchars($uploadedImages['error']) . '</div>';
                    break;
                }

                $payload = $_POST;
                $payload['images'] = $uploadedImages['images'] ?? [];
                $result = $productClass->createProduct($payload);
                $message = $result['success'] 
                    ? '<div class="alert alert-success">Product created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: products.php');
                    exit;
                }
                break;

            case 'edit':
                // Optional: delete selected existing images
                if (!empty($_POST['delete_image_ids']) && is_array($_POST['delete_image_ids'])) {
                    $ids = array_map('intval', $_POST['delete_image_ids']);
                    $productClass->deleteProductImages($ids);
                }

                // Optional: new uploads
                $uploadedImages = handleProductImageUploads($_FILES['images'] ?? null);
                $payload = $_POST;
                if (empty($uploadedImages['error']) && !empty($uploadedImages['images'])) {
                    $productClass->addProductImages($productId, $uploadedImages['images']);
                }
                $result = $productClass->updateProduct($productId, $payload);
                $message = $result['success'] 
                    ? '<div class="alert alert-success">Product updated successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                break;

            case 'delete':
                $result = $productClass->deleteProduct($productId);
                $message = $result 
                    ? '<div class="alert alert-success">Product deleted successfully</div>'
                    : '<div class="alert alert-danger">Error deleting product</div>';
                if ($result) {
                    header('Location: products.php');
                    exit;
                }
                break;
        }
    }
}

$products = $productClass->getAllProducts();
$categories = $categoryClass->getAllCategories();

if (in_array($action, ['edit', 'view']) && $productId) {
    $product = $productClass->getProductById($productId);
    if (!$product) {
        header('Location: products.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Products
                    <?php if ($action === 'create'): ?>
                        - Add New
                    <?php elseif ($action === 'edit'): ?>
                        - Edit
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Products</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <!-- Products List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Products</h3>
                    <div class="card-tools">
                        <a href="products.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Product
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Base Price (INR)</th>
                                    <th>Images</th>
                                    <th>Variants</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['primary_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['primary_image']); ?>" alt="thumb" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 6px;"><i class="fas fa-image text-muted"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <?php if ($product['source_link']): ?>
                                                <br><small><a href="<?php echo htmlspecialchars($product['source_link']); ?>" target="_blank" class="text-muted">Source Link</a></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo formatPriceINR($product['base_price']); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $product['image_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary"><?php echo $product['variant_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $product['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($product['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="products.php?action=view&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" action="products.php?action=delete&id=<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger delete-btn" data-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif (in_array($action, ['create', 'edit'])): ?>
            <!-- Product Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $action === 'create' ? 'Add New Product' : 'Edit Product'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please provide a product name.</div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="source_link">Source Link</label>
                                    <input type="url" class="form-control" id="source_link" name="source_link" 
                                           value="<?php echo htmlspecialchars($product['source_link'] ?? ''); ?>" 
                                           placeholder="https://example.com">
                                </div>

                                <!-- Images upload -->
                                <div class="form-group">
                                    <label for="images">Product Images</label>
                                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple>
                                    <small class="form-text text-muted">You can select multiple images. JPG, PNG up to 2MB each.</small>
                                </div>
                                
                                <?php if ($action === 'edit' && !empty($product['images'])): ?>
                                    <div class="mt-3">
                                        <label>Existing Images</label>
                                        <div class="row">
                                            <?php foreach ($product['images'] as $img): ?>
                                                <div class="col-md-3 mb-3">
                                                    <div class="card">
                                                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="<?php echo htmlspecialchars($img['alt_text'] ?? ''); ?>">
                                                        <div class="card-body p-2">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div class="form-check mb-0">
                                                                    <input class="form-check-input" type="checkbox" name="delete_image_ids[]" value="<?php echo $img['id']; ?>" id="del-<?php echo $img['id']; ?>">
                                                                    <label class="form-check-label" for="del-<?php echo $img['id']; ?>">Delete</label>
                                                                </div>
                                                                <div>
                                                                    <?php if ((int)$img['is_primary'] === 1): ?>
                                                                        <span class="badge badge-success">Primary</span>
                                                                    <?php else: ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPrimaryImage(<?php echo (int)$product['id']; ?>, <?php echo (int)$img['id']; ?>)">Make Primary</button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_price">Base Price *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">₹</span>
                                        </div>
                                        <input type="number" step="0.01" class="form-control" id="base_price" name="base_price" 
                                               value="<?php echo $product['base_price'] ?? ''; ?>" required>
                                    </div>
                                    <div class="invalid-feedback">Please provide a base price.</div>
                                </div>

                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select class="form-control" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Variants Section -->
                        <?php if ($action === 'edit' && !empty($product['variants'])): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4>Product Variants</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Variant</th>
                                                    <th>SKU</th>
                                                    <th>Price Adjustment</th>
                                                    <th>Stock</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($product['variants'] as $variant): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($variant['variant_name'] . ': ' . $variant['variant_value']); ?></td>
                                                        <td><?php echo htmlspecialchars($variant['sku']); ?></td>
                                                        <td><?php echo formatPrice($variant['price_adjustment']); ?></td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" 
                                                                   value="<?php echo $variant['stock_quantity']; ?>"
                                                                   onchange="updateStock(<?php echo $variant['id']; ?>, this.value)"
                                                                   style="width: 80px;">
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?php echo $variant['is_active'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $variant['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo $action === 'create' ? 'Create Product' : 'Update Product'; ?>
                            </button>
                            <a href="products.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'view'): ?>
            <!-- Product View -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Details</h3>
                    <div class="card-tools">
                        <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit mr-2"></i>Edit Product
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <?php if ($product['source_link']): ?>
                                <p><strong>Source:</strong> <a href="<?php echo htmlspecialchars($product['source_link']); ?>" target="_blank">View Source</a></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Base Price</span>
                                    <span class="info-box-number"><?php echo formatPrice($product['base_price']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($product['variants'])): ?>
                        <div class="mt-4">
                            <h4>Product Variants</h4>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Variant</th>
                                            <th>SKU</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product['variants'] as $variant): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($variant['variant_name'] . ': ' . $variant['variant_value']); ?></td>
                                                <td><?php echo htmlspecialchars($variant['sku']); ?></td>
                                                <td><?php echo formatPrice($product['base_price'] + $variant['price_adjustment']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $variant['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                        <?php echo $variant['stock_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $variant['is_active'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $variant['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<?php
// Helpers
function ensureUploadsDir() {
    if (!is_dir(UPLOAD_PATH)) {
        @mkdir(UPLOAD_PATH, 0775, true);
    }
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename);
    return $filename;
}

function handleProductImageUploads($files) {
    if (empty($files) || empty($files['name'])) {
        return ['images' => []];
    }

    ensureUploadsDir();

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $images = [];

    $names = (array)$files['name'];
    $types = (array)$files['type'];
    $tmpNames = (array)$files['tmp_name'];
    $sizes = (array)$files['size'];
    $errors = (array)$files['error'];

    foreach ($names as $idx => $name) {
        if ($errors[$idx] !== UPLOAD_ERR_OK) {
            continue;
        }
        $type = $types[$idx] ?? '';
        if (!isset($allowed[$type])) {
            return ['error' => 'Unsupported file type. Use JPG, PNG, or WEBP.'];
        }
        if (($sizes[$idx] ?? 0) > $maxSize) {
            return ['error' => 'File too large. Max 2MB per image.'];
        }

        $ext = $allowed[$type];
        $safeName = pathinfo($name, PATHINFO_FILENAME);
        $safeName = sanitizeFilename($safeName);
        $finalName = $safeName . '-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
        $dest = rtrim(UPLOAD_PATH, '/').'/'.$finalName;

        if (!move_uploaded_file($tmpNames[$idx], $dest)) {
            return ['error' => 'Failed to upload image.'];
        }

        $images[] = [
            'url' => $dest,
            'alt_text' => $safeName,
        ];
    }

    return ['images' => $images];
}
?>