<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Reviews';
$reviewClass = new Review($db);
$productClass = new Product($db);
$customerClass = new Customer($db);

$action = $_GET['action'] ?? 'list';
$reviewId = $_GET['id'] ?? null;
$statusFilter = $_GET['status'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                $result = $reviewClass->createReview($_POST);
                $message = $result['success']
                    ? '<div class="alert alert-success">Review created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: reviews.php');
                    exit;
                }
                break;

            case 'delete':
                $result = $reviewClass->deleteReview($reviewId);
                $message = $result['success']
                    ? '<div class="alert alert-success">Review deleted successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: reviews.php');
                    exit;
                }
                break;
        }
    }
}

$reviews = $reviewClass->getAllReviews($statusFilter);
$products = $productClass->getAllProducts();
$customers = $customerClass->getAllCustomers();

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Reviews</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reviews</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">All Reviews</h3>
                    <div>
                        <div class="btn-group mr-2">
                            <a href="reviews.php" class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="reviews.php?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                            <a href="reviews.php?status=approved" class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">Approved</a>
                            <a href="reviews.php?status=rejected" class="btn btn-sm <?php echo $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">Rejected</a>
                        </div>
                        <a href="reviews.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Review
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Customer</th>
                                    <th>Rating</h1>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['product_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['customer_name'] ?? 'Guest'); ?></td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo (int)$r['rating']; ?>/5</span>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['title'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($r['status']); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo formatDate($r['created_at']); ?></small></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <button class="dropdown-item" onclick="updateStatus('review', <?php echo $r['id']; ?>, 'pending')">Mark Pending</button>
                                                    <button class="dropdown-item" onclick="updateStatus('review', <?php echo $r['id']; ?>, 'approved')">Approve</button>
                                                    <button class="dropdown-item" onclick="updateStatus('review', <?php echo $r['id']; ?>, 'rejected')">Reject</button>
                                                </div>
                                                <form method="POST" class="d-inline" action="reviews.php?action=delete&id=<?php echo $r['id']; ?>">
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

        <?php elseif ($action === 'create'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Review</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="form-group">
                            <label for="product_id">Product *</label>
                            <select class="form-control" id="product_id" name="product_id" required>
                                <option value="">Select product</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a product.</div>
                        </div>

                        <div class="form-group">
                            <label for="customer_id">Customer</label>
                            <select class="form-control" id="customer_id" name="customer_id">
                                <option value="">Guest</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']) . ' (' . htmlspecialchars($c['email']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="rating">Rating *</label>
                            <select class="form-control" id="rating" name="rating" required>
                                <option value="">Select rating</option>
                                <?php for ($i=5; $i>=1; $i--): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Please select a rating.</div>
                        </div>

                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>

                        <div class="form-group">
                            <label for="comment">Comment *</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                            <div class="invalid-feedback">Please provide a comment.</div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Create Review
                            </button>
                            <a href="reviews.php" class="btn btn-secondary ml-2">
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


