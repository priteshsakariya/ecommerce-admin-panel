<?php
require_once 'config/app.php';
requireAuth();

if (!hasRole('admin')) {
    redirectTo('dashboard.php');
}

$pageTitle = 'Users';
$userClass = new User($db);
$message = '';
$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                $result = $userClass->createUser($_POST);
                $message = $result['success'] ? '<div class="alert alert-success">User created</div>' : '<div class="alert alert-danger">' . $result['message'] . '</div>';
                if ($result['success']) { header('Location: users.php'); exit; }
                break;
            case 'edit':
                $result = $userClass->updateUser($userId, $_POST);
                $message = $result['success'] ? '<div class="alert alert-success">User updated</div>' : '<div class="alert alert-danger">' . $result['message'] . '</div>';
                break;
            case 'delete':
                $result = $userClass->deleteUser($userId);
                $message = $result['success'] ? '<div class="alert alert-success">User deleted</div>' : '<div class="alert alert-danger">' . $result['message'] . '</div>';
                if ($result['success']) { header('Location: users.php'); exit; }
                break;
        }
    }
}

if (in_array($action, ['edit']) && $userId) {
    $editUser = $userClass->getUserById($userId);
}

$users = $userClass->getAllUsers();

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Users</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
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
                    <h3 class="card-title">All Users</h3>
                    <a href="users.php?action=create" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Add User</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                        <td><span class="badge badge-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                        <td><small><?php echo $u['last_login'] ? formatDate($u['last_login']) : '—'; ?></small></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                                <form method="POST" class="d-inline" action="users.php?action=delete&id=<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger delete-btn"><i class="fas fa-trash"></i></button>
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
        <?php else: ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title"><?php echo $action === 'create' ? 'Add User' : 'Edit User'; ?></h3></div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($editUser['first_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($editUser['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">Role *</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="editor" <?php echo ($editUser['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        <option value="viewer" <?php echo ($editUser['role'] ?? '') === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select id="is_active" name="is_active" class="form-control">
                                        <option value="1" <?php echo ($editUser['is_active'] ?? 1) ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo isset($editUser) && !$editUser['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password <?php echo $action === 'create' ? '*' : '(leave blank to keep)'; ?></label>
                                    <input type="password" id="password" name="password" class="form-control" <?php echo $action === 'create' ? 'required' : ''; ?>>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i><?php echo $action === 'create' ? 'Create User' : 'Update User'; ?></button>
                            <a href="users.php" class="btn btn-secondary ml-2"><i class="fas fa-times mr-2"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


