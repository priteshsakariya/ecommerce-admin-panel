<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Profile';
$auth = new Auth($db);
$user = $auth->getCurrentUser();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        $userClass = new User($db);
        $payload = [
            'username' => $_POST['username'] ?? $user['username'],
            'email' => $_POST['email'] ?? $user['email'],
            'role' => $user['role'],
            'first_name' => $_POST['first_name'] ?? $user['first_name'],
            'last_name' => $_POST['last_name'] ?? $user['last_name'],
            'is_active' => $user['is_active'],
        ];
        if (!empty($_POST['password'])) {
            $payload['password'] = $_POST['password'];
        }
        $result = $userClass->updateUser($user['id'], $payload);
        if ($result['success']) {
            $_SESSION['username'] = $payload['username'];
            $_SESSION['user_name'] = ($payload['first_name'] ?? '') . ' ' . ($payload['last_name'] ?? '');
            $message = '<div class="alert alert-success">Profile updated</div>';
            $user = $auth->getCurrentUser();
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
        }
    }
}

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">My Profile</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Account Details</h3></div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">New Password (optional)</label>
                                <input type="password" id="password" name="password" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Update Profile</button>
                        <a href="dashboard.php" class="btn btn-secondary ml-2"><i class="fas fa-times mr-2"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


