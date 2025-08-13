<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Customers';
$customerClass = new Customer($db);

$action = $_GET['action'] ?? 'list';
$customerId = $_GET['id'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                $result = $customerClass->createCustomer($_POST);
                $message = $result['success']
                    ? '<div class="alert alert-success">Customer created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: customers.php');
                    exit;
                }
                break;

            case 'edit':
                $result = $customerClass->updateCustomer($customerId, $_POST);
                $message = $result['success']
                    ? '<div class="alert alert-success">Customer updated successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                break;

            case 'delete':
                $result = $customerClass->deleteCustomer($customerId);
                $message = $result['success']
                    ? '<div class="alert alert-success">Customer deleted successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: customers.php');
                    exit;
                }
                break;
        }
    }
}

$customers = $customerClass->getAllCustomers();

if (in_array($action, ['edit', 'view']) && $customerId) {
    $customer = $customerClass->getCustomerById($customerId);
    if (!$customer) {
        header('Location: customers.php');
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
                    Customers
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
                    <li class="breadcrumb-item active">Customers</li>
                </ol>
            </div>
        </div>
    </div>
    
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <!-- Customers List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Customers</h3>
                    <div class="card-tools">
                        <a href="customers.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Customer
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>"><?php echo htmlspecialchars($c['email']); ?></a></td>
                                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                                        <td><span class="badge badge-info"><?php echo (int)($c['total_orders'] ?? 0); ?></span></td>
                                        <td><strong><?php echo formatPrice($c['total_spent'] ?? 0); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $c['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo formatDate($c['created_at']); ?></small></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="customers.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="updateStatus('customer', <?php echo $c['id']; ?>, '<?php echo $c['is_active'] ? 'inactive' : 'active'; ?>')">
                                                    <i class="fas fa-toggle-<?php echo $c['is_active'] ? 'off' : 'on'; ?>"></i>
                                                </button>
                                                <form method="POST" class="d-inline" action="customers.php?action=delete&id=<?php echo $c['id']; ?>">
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
            <!-- Customer Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action === 'create' ? 'Add New Customer' : 'Edit Customer'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please provide a name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please provide a valid email.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select class="form-control" id="is_active" name="is_active">
                                        <option value="1" <?php echo ($customer['is_active'] ?? 1) ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo isset($customer) && !$customer['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo $action === 'create' ? 'Create Customer' : 'Update Customer'; ?>
                            </button>
                            <a href="customers.php" class="btn btn-secondary ml-2">
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


