<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Feedback';
$feedbackClass = new Feedback($db);
$customerClass = new Customer($db);

$action = $_GET['action'] ?? 'list';
$feedbackId = $_GET['id'] ?? null;
$statusFilter = $_GET['status'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                $result = $feedbackClass->createFeedback($_POST);
                $message = $result['success']
                    ? '<div class="alert alert-success">Feedback created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: feedback.php');
                    exit;
                }
                break;

            case 'delete':
                $result = $feedbackClass->deleteFeedback($feedbackId);
                $message = $result['success']
                    ? '<div class="alert alert-success">Feedback deleted successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: feedback.php');
                    exit;
                }
                break;
        }
    }
}

$feedbackList = $feedbackClass->getAllFeedback($statusFilter);
$customers = $customerClass->getAllCustomers();

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Feedback</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Feedback</li>
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
                    <h3 class="card-title">All Feedback</h3>
                    <div>
                        <div class="btn-group mr-2">
                            <a href="feedback.php" class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="feedback.php?status=new" class="btn btn-sm <?php echo $statusFilter === 'new' ? 'btn-info' : 'btn-outline-info'; ?>">New</a>
                            <a href="feedback.php?status=in_progress" class="btn btn-sm <?php echo $statusFilter === 'in_progress' ? 'btn-warning' : 'btn-outline-warning'; ?>">In Progress</a>
                            <a href="feedback.php?status=resolved" class="btn btn-sm <?php echo $statusFilter === 'resolved' ? 'btn-success' : 'btn-outline-success'; ?>">Resolved</a>
                        </div>
                        <a href="feedback.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Feedback
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedbackList as $f): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($f['subject']); ?></strong>
                                            <div class="text-muted small">ID: <?php echo $f['id']; ?></div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($f['customer_name'] ?? 'Guest'); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($f['customer_email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $f['status'] === 'resolved' ? 'success' : ($f['status'] === 'in_progress' ? 'warning' : 'info'); ?>">
                                                <?php echo ucwords(str_replace('_',' ', $f['status'])); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo formatDate($f['created_at']); ?></small></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <button class="dropdown-item" onclick="updateStatus('feedback', <?php echo $f['id']; ?>, 'new')">Mark New</button>
                                                    <button class="dropdown-item" onclick="updateStatus('feedback', <?php echo $f['id']; ?>, 'in_progress')">Mark In Progress</button>
                                                    <button class="dropdown-item" onclick="updateStatus('feedback', <?php echo $f['id']; ?>, 'resolved')">Mark Resolved</button>
                                                </div>
                                                <form method="POST" class="d-inline" action="feedback.php?action=delete&id=<?php echo $f['id']; ?>">
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
                    <h3 class="card-title">Add Feedback</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">

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
                            <label for="subject">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                            <div class="invalid-feedback">Please provide a subject.</div>
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback">Please provide a message.</div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Create Feedback
                            </button>
                            <a href="feedback.php" class="btn btn-secondary ml-2">
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


