<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Orders';
$orderClass = new Order($db);

$action = $_GET['action'] ?? 'list';
$orderId = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'update_status':
                $result = $orderClass->updateOrderStatus($orderId, $_POST['status'], $_POST['notes'] ?? null);
                $message = $result['success'] 
                    ? '<div class="alert alert-success">Order status updated successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                break;

            case 'delete':
                $result = $orderClass->deleteOrder($orderId);
                $message = $result['success']
                    ? '<div class="alert alert-success">Order deleted successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: orders.php');
                    exit;
                }
                break;
        }
    }
}

$orders = $orderClass->getAllOrders(50, 0, $status);

if (in_array($action, ['view', 'update_status']) && $orderId) {
    $order = $orderClass->getOrderById($orderId);
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
}

$orderStats = $orderClass->getOrderStats();

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Orders
                    <?php if ($status): ?>
                        - <?php echo ucfirst($status); ?>
                    <?php endif; ?>
                    <?php if ($action === 'view'): ?>
                        - #<?php echo htmlspecialchars($order['order_number']); ?>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Orders</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <!-- Order Statistics -->
        <div class="row mb-4">
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
                        View All <i class="fas fa-arrow-circle-right"></i>
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
                        View Pending <i class="fas fa-arrow-circle-right"></i>
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
                        View Completed <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?php echo number_format($orderStats['today_orders']); ?></h3>
                        <p>Today's Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <a href="orders.php" class="small-box-footer">
                        View Today <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <?php if ($action === 'list'): ?>
            <!-- Orders List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        All Orders 
                        <?php if ($status): ?>
                            (<?php echo ucfirst($status); ?>)
                        <?php endif; ?>
                    </h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <a href="orders.php" class="btn btn-sm <?php echo !$status ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="orders.php?status=pending" class="btn btn-sm <?php echo $status === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                            <a href="orders.php?status=processing" class="btn btn-sm <?php echo $status === 'processing' ? 'btn-info' : 'btn-outline-info'; ?>">Processing</a>
                            <a href="orders.php?status=shipped" class="btn btn-sm <?php echo $status === 'shipped' ? 'btn-success' : 'btn-outline-success'; ?>">Shipped</a>
                            <a href="orders.php?status=delivered" class="btn btn-sm <?php echo $status === 'delivered' ? 'btn-success' : 'btn-outline-success'; ?>">Delivered</a>
                            <a href="orders.php?status=cancelled" class="btn btn-sm <?php echo $status === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Cancelled</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $order['item_count']; ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($order['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <button class="dropdown-item" onclick="updateStatus('order', <?php echo $order['id']; ?>, 'pending')">Mark Pending</button>
                                                        <button class="dropdown-item" onclick="updateStatus('order', <?php echo $order['id']; ?>, 'processing')">Mark Processing</button>
                                                        <button class="dropdown-item" onclick="updateStatus('order', <?php echo $order['id']; ?>, 'shipped')">Mark Shipped</button>
                                                        <button class="dropdown-item" onclick="updateStatus('order', <?php echo $order['id']; ?>, 'delivered')">Mark Delivered</button>
                                                        <div class="dropdown-divider"></div>
                                                        <button class="dropdown-item text-danger" onclick="updateStatus('order', <?php echo $order['id']; ?>, 'cancelled')">Cancel Order</button>
                                                    </div>
                                                </div>
                                                <form method="POST" class="d-inline" action="orders.php?action=delete&id=<?php echo $order['id']; ?>">
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

        <?php elseif ($action === 'view'): ?>
            <!-- Order Details -->
            <div class="row">
                <div class="col-md-8">
                    <!-- Order Items -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Items</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Variant</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($item['variant_details'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary"><?php echo $item['quantity']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo formatPrice($item['unit_price']); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo formatPrice($item['total_price']); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="4" class="text-right">Total Amount:</th>
                                            <th><?php echo formatPrice($order['total_amount']); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Order Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-5">Order #:</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($order['order_number']); ?></dd>

                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5">Total:</dt>
                                <dd class="col-sm-7"><strong><?php echo formatPrice($order['total_amount']); ?></strong></dd>

                                <dt class="col-sm-5">Date:</dt>
                                <dd class="col-sm-7"><?php echo formatDate($order['created_at']); ?></dd>

                                <?php if ($order['updated_at'] !== $order['created_at']): ?>
                                    <dt class="col-sm-5">Updated:</dt>
                                    <dd class="col-sm-7"><?php echo formatDate($order['updated_at']); ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Customer Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Name:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($order['customer_name']); ?></dd>

                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8">
                                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>">
                                        <?php echo htmlspecialchars($order['customer_email']); ?>
                                    </a>
                                </dd>

                                <?php if ($order['customer_phone']): ?>
                                    <dt class="col-sm-4">Phone:</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($order['customer_phone']); ?></dd>
                                <?php endif; ?>

                                <?php if ($order['shipping_address']): ?>
                                    <dt class="col-sm-4">Address:</dt>
                                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>

                    <!-- Order Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Actions</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="orders.php?action=update_status&id=<?php echo $order['id']; ?>">
                                <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-group">
                                    <label for="status">Update Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-2"></i>Update Order
                                </button>
                            </form>

                            <hr>

                            <a href="orders.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>