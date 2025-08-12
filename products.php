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
                $result = $productClass->createProduct($_POST);
                $message = $result['success'] 
                    ? '<div class="alert alert-success">Product created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: products.php');
                    exit;
                }
                break;

            case 'edit':
                $result = $productClass->updateProduct($productId, $_POST);
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
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Base Price</th>
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
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <?php if ($product['source_link']): ?>
                                                <br><small><a href="<?php echo htmlspecialchars($product['source_link']); ?>" target="_blank" class="text-muted">Source Link</a></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo formatPrice($product['base_price']); ?></td>
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
                    <form method="POST" class="needs-validation" novalidate>
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
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_price">Base Price *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
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