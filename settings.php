<?php
require_once 'config/app.php';
requireAuth();

if (!hasRole('admin')) {
    redirectTo('dashboard.php');
}

$pageTitle = 'Settings';
$settingsClass = new Settings($db);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        $payload = [
            'store_name' => $_POST['store_name'] ?? APP_NAME,
            'support_email' => $_POST['support_email'] ?? '',
            'low_stock_threshold' => $_POST['low_stock_threshold'] ?? '10',
            'dashboard_base_url' => $_POST['dashboard_base_url'] ?? BASE_URL,
        ];
        $result = $settingsClass->setSettings($payload);
        $message = $result['success']
            ? '<div class="alert alert-success">Settings saved</div>'
            : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
    }
}

$settings = $settingsClass->getAllSettings();

include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">General</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label for="store_name">Store Name</label>
                        <input type="text" id="store_name" name="store_name" class="form-control" value="<?php echo htmlspecialchars($settings['store_name'] ?? APP_NAME); ?>">
                    </div>

                    <div class="form-group">
                        <label for="support_email">Support Email</label>
                        <input type="email" id="support_email" name="support_email" class="form-control" value="<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="low_stock_threshold">Low Stock Threshold</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" value="<?php echo htmlspecialchars($settings['low_stock_threshold'] ?? '10'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="dashboard_base_url">Dashboard Base URL</label>
                        <input type="url" id="dashboard_base_url" name="dashboard_base_url" class="form-control" value="<?php echo htmlspecialchars($settings['dashboard_base_url'] ?? BASE_URL); ?>">
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Settings</button>
                        <a href="dashboard.php" class="btn btn-secondary ml-2"><i class="fas fa-times mr-2"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


