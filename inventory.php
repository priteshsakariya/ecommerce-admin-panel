<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Inventory Management';
$productClass = new Product($db);

$lowStockThreshold = 10; // Get from settings in production
$lowStockItems = $productClass->getLowStockItems($lowStockThreshold);
$allProducts = $productClass->getAllProducts();

$message = '';

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (verifyCSRFToken($_POST['_token'] ?? '')) {
        $variantId = $_POST['variant_id'];
        $newQuantity = $_POST['quantity'];
        
        $result = $productClass->updateVariantStock($variantId, $newQuantity);
        $message = $result 
            ? '<div class="alert alert-success">Stock updated successfully</div>'
            : '<div class="alert alert-danger">Error updating stock</div>';
    } else {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    }
}

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Inventory Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <!-- Inventory Overview -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($allProducts); ?></h3>
                        <p>Total Products</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count($lowStockItems); ?></h3>
                        <p>Low Stock Alerts</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <?php 
                        $totalStock = 0;
                        foreach ($allProducts as $product) {
                            $variants = $productClass->getProductVariants($product['id']);
                            foreach ($variants as $variant) {
                                $totalStock += $variant['stock_quantity'];
                            }
                        }
                        ?>
                        <h3><?php echo number_format($totalStock); ?></h3>
                        <p>Total Stock Units</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <?php
                        $outOfStockCount = 0;
                        foreach ($allProducts as $product) {
                            $variants = $productClass->getProductVariants($product['id']);
                            foreach ($variants as $variant) {
                                if ($variant['stock_quantity'] == 0) {
                                    $outOfStockCount++;
                                }
                            }
                        }
                        ?>
                        <h3><?php echo $outOfStockCount; ?></h3>
                        <p>Out of Stock</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <?php if (!empty($lowStockItems)): ?>
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Low Stock Alert (Below <?php echo $lowStockThreshold; ?> units)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Quick Update</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $item): ?>
                                    <tr class="<?php echo $item['stock_quantity'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($item['variant_name'] . ': ' . $item['variant_value']); ?>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($item['sku']); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $item['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>" id="stock-<?php echo $item['id']; ?>">
                                                <?php echo $item['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm" style="max-width: 120px;">
                                                <input type="number" class="form-control" id="new-stock-<?php echo $item['id']; ?>" min="0" value="<?php echo $item['stock_quantity']; ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" onclick="updateStock(<?php echo $item['id']; ?>, document.getElementById('new-stock-<?php echo $item['id']; ?>').value)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="products.php?action=edit&id=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i> Edit Product
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- All Products Inventory -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Complete Inventory
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="searchTable" class="form-control" placeholder="Search products...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Variants</th>
                                <th>Total Stock</th>
                                <th>Stock Status</th>
                                <th>Base Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allProducts as $product): ?>
                                <?php
                                $variants = $productClass->getProductVariants($product['id']);
                                $totalProductStock = 0;
                                $hasLowStock = false;
                                $hasOutOfStock = false;
                                
                                foreach ($variants as $variant) {
                                    $totalProductStock += $variant['stock_quantity'];
                                    if ($variant['stock_quantity'] <= $lowStockThreshold) {
                                        $hasLowStock = true;
                                    }
                                    if ($variant['stock_quantity'] == 0) {
                                        $hasOutOfStock = true;
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo count($variants); ?> variants</span>
                                        <?php if (!empty($variants)): ?>
                                            <div class="mt-1">
                                                <?php foreach ($variants as $variant): ?>
                                                    <small class="d-block">
                                                        <?php echo htmlspecialchars($variant['variant_name'] . ': ' . $variant['variant_value']); ?>
                                                        <span class="badge badge-<?php echo $variant['stock_quantity'] > $lowStockThreshold ? 'success' : ($variant['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                                            <?php echo $variant['stock_quantity']; ?>
                                                        </span>
                                                    </small>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($totalProductStock); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($hasOutOfStock): ?>
                                            <span class="badge badge-danger">Out of Stock</span>
                                        <?php elseif ($hasLowStock): ?>
                                            <span class="badge badge-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatPrice($product['base_price']); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="products.php?action=view&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Table search functionality
document.getElementById('searchTable').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#inventoryTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>