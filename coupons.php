<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Coupons';
$couponClass = new Coupon($db);

$action = $_GET['action'] ?? 'list';
$couponId = $_GET['id'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                $result = $couponClass->createCoupon($_POST);
                $message = $result['success']
                    ? '<div class="alert alert-success">Coupon created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: coupons.php');
                    exit;
                }
                break;

            case 'edit':
                $result = $couponClass->updateCoupon($couponId, $_POST);
                $message = $result['success']
                    ? '<div class="alert alert-success">Coupon updated successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                break;

            case 'delete':
                $result = $couponClass->deleteCoupon($couponId);
                $message = $result['success']
                    ? '<div class="alert alert-success">Coupon deleted successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: coupons.php');
                    exit;
                }
                break;
        }
    }
}

$coupons = $couponClass->getAllCoupons();

if (in_array($action, ['edit', 'view']) && $couponId) {
    $coupon = $couponClass->getCouponById($couponId);
    if (!$coupon) {
        header('Location: coupons.php');
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
                    Coupons
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
                    <li class="breadcrumb-item active">Coupons</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <!-- Coupons List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Coupons</h3>
                    <div class="card-tools">
                        <a href="coupons.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Coupon
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Min Order</th>
                                    <th>Validity</th>
                                    <th>Usage Limit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $c): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($c['code']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($c['discount_type']); ?></span></td>
                                        <td><?php echo $c['discount_type'] === 'percent' ? ((float)$c['discount_value'] . '%') : formatPrice($c['discount_value']); ?></td>
                                        <td><?php echo $c['min_order_amount'] ? formatPrice($c['min_order_amount']) : '-'; ?></td>
                                        <td>
                                            <small>
                                                <?php echo $c['start_date'] ? date('M j, Y', strtotime($c['start_date'])) : '—'; ?>
                                                -
                                                <?php echo $c['end_date'] ? date('M j, Y', strtotime($c['end_date'])) : '—'; ?>
                                            </small>
                                        </td>
                                        <td><?php echo $c['usage_limit'] ? (int)$c['usage_limit'] : '—'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $c['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="coupons.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="updateStatus('coupon', <?php echo $c['id']; ?>, '<?php echo $c['is_active'] ? 'inactive' : 'active'; ?>')">
                                                    <i class="fas fa-toggle-<?php echo $c['is_active'] ? 'off' : 'on'; ?>"></i>
                                                </button>
                                                <form method="POST" class="d-inline" action="coupons.php?action=delete&id=<?php echo $c['id']; ?>">
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
            <!-- Coupon Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'create' ? 'Add New Coupon' : 'Edit Coupon'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="code">Code *</label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($coupon['code'] ?? ''); ?>" required>
                                    <small class="form-text text-muted">Use letters and numbers only.</small>
                                    <div class="invalid-feedback">Please provide a code.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="discount_type">Discount Type *</label>
                                    <select class="form-control" id="discount_type" name="discount_type" required>
                                        <option value="">Select type</option>
                                        <option value="percent" <?php echo ($coupon['discount_type'] ?? '') === 'percent' ? 'selected' : ''; ?>>Percent</option>
                                        <option value="fixed" <?php echo ($coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a discount type.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="discount_value">Discount Value *</label>
                                    <input type="number" step="0.01" class="form-control" id="discount_value" name="discount_value" value="<?php echo htmlspecialchars($coupon['discount_value'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please provide a discount value.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_order_amount">Minimum Order Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="min_order_amount" name="min_order_amount" value="<?php echo htmlspecialchars($coupon['min_order_amount'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($coupon['start_date']) ? date('Y-m-d', strtotime($coupon['start_date'])) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($coupon['end_date']) ? date('Y-m-d', strtotime($coupon['end_date'])) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="usage_limit">Usage Limit</label>
                                    <input type="number" class="form-control" id="usage_limit" name="usage_limit" value="<?php echo htmlspecialchars($coupon['usage_limit'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($coupon['description'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select class="form-control" id="is_active" name="is_active">
                                        <option value="1" <?php echo ($coupon['is_active'] ?? 1) ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo isset($coupon) && !$coupon['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo $action === 'create' ? 'Create Coupon' : 'Update Coupon'; ?>
                            </button>
                            <a href="coupons.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


