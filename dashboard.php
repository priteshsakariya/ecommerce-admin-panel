<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Dashboard';

// Get dashboard statistics
$productClass = new Product($db);
$orderClass = new Order($db);

$totalProducts = $productClass->getTotalCount();
$orderStats = $orderClass->getOrderStats();
$lowStockItems = $productClass->getLowStockItems(10);

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box stat-card">
                    <div class="inner">
                        <h3><?php echo number_format($totalProducts); ?></h3>
                        <p>Total Products</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <a href="products.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($orderStats['total_orders']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <a href="orders.php" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($orderStats['pending_orders']); ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="orders.php?status=pending" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo formatPrice($orderStats['total_revenue']); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <a href="orders.php?status=delivered" class="small-box-footer">
                        More info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Low Stock Alert -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                            Low Stock Alert
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lowStockItems)): ?>
                            <p class="text-muted">No low stock items at the moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Variant</th>
                                            <th>Stock</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockItems as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['variant_name'] . ': ' . $item['variant_value']); ?></td>
                                                <td>
                                                    <span class="badge badge-danger"><?php echo $item['stock_quantity']; ?></span>
                                                </td>
                                                <td>
                                                    <a href="products.php?edit=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Quick Stats
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 text-center">
                                <div class="description-block">
                                    <span class="description-percentage text-success">
                                        <i class="fas fa-caret-up"></i> Today
                                    </span>
                                    <h5 class="description-header"><?php echo number_format($orderStats['today_orders']); ?></h5>
                                    <span class="description-text">New Orders</span>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="description-block">
                                    <span class="description-percentage text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Alert
                                    </span>
                                    <h5 class="description-header"><?php echo count($lowStockItems); ?></h5>
                                    <span class="description-text">Low Stock Items</span>
                                </div>
                            </div>
                        </div>

                        <div class="progress-group mt-3">
                            Inventory Health
                            <span class="float-right"><b><?php echo count($lowStockItems); ?></b>/<?php echo $totalProducts; ?></span>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: <?php echo $totalProducts > 0 ? (($totalProducts - count($lowStockItems)) / $totalProducts) * 100 : 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-2"></i>
                            Welcome Back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-users"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Your Role</span>
                                        <span class="info-box-number">
                                            <?php echo ucfirst($_SESSION['user_role']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Session Time</span>
                                        <span class="info-box-number">
                                            <?php echo formatDate($_SESSION['login_time']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning">
                                        <i class="fas fa-tasks"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Quick Actions</span>
                                        <div class="info-box-number">
                                            <a href="products.php?action=create" class="btn btn-sm btn-primary mr-2">
                                                <i class="fas fa-plus"></i> Add Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>