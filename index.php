<?php
require_once 'config/app.php';

// Redirect if already logged in
if (isLoggedIn()) {
	redirectTo('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = $_POST['username'] ?? '';
	$password = $_POST['password'] ?? '';
	
	if (empty($username) || empty($password)) {
		$error = 'Please fill in all fields';
	} else {
		$auth = new Auth($db);
		$result = $auth->login($username, $password);
		
		if ($result['success']) {
			redirectTo('dashboard.php');
		} else {
			$error = $result['message'];
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login - <?php echo APP_NAME; ?></title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<script src="https://cdn.tailwindcss.com"></script>
	<style>
		body { 
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
		}
		.login-box {
			backdrop-filter: blur(10px);
			background: rgba(255, 255, 255, 0.95);
			border-radius: 20px;
			box-shadow: 0 20px 40px rgba(0,0,0,0.1);
		}
		.login-logo {
			color: #667eea;
			font-weight: 700;
		}
		.form-control:focus {
			border-color: #667eea;
			box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
		}
		.btn-primary {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			border: none;
			transition: all 0.3s ease;
		}
		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		}
	</style>
</head>
<body class="hold-transition login-page">
	<div class="login-box">
		<div class="login-logo">
			<i class="fas fa-store mr-2"></i>
			<b><?php echo APP_NAME; ?></b>
		</div>
		
		<div class="card">
			<div class="card-body login-card-body">
				<p class="login-box-msg">Sign in to access admin panel</p>

				<?php if ($error): ?>
					<div class="alert alert-danger alert-dismissible">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						<i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
					</div>
				<?php endif; ?>

				<form method="POST" class="needs-validation" novalidate>
					<div class="input-group mb-3">
						<input type="text" class="form-control" name="username" placeholder="Username or Email" required>
						<div class="input-group-append">
							<div class="input-group-text">
								<span class="fas fa-user"></span>
							</div>
						</div>
						<div class="invalid-feedback">Please provide a username or email.</div>
					</div>
					
					<div class="input-group mb-3">
						<input type="password" class="form-control" name="password" placeholder="Password" required>
						<div class="input-group-append">
							<div class="input-group-text">
								<span class="fas fa-lock"></span>
							</div>
						</div>
						<div class="invalid-feedback">Please provide a password.</div>
					</div>
					
					<div class="row">
						<div class="col-12">
							<button type="submit" class="btn btn-primary btn-block">
								<i class="fas fa-sign-in-alt mr-2"></i>Sign In
							</button>
						</div>
					</div>
				</form>

				<div class="text-center mt-3">
					<small class="text-muted">
						Default login: admin / admin123
					</small>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
	<script>
		// Form validation
		$('.needs-validation').on('submit', function(event) {
			if (this.checkValidity() === false) {
				event.preventDefault();
				event.stopPropagation();
			}
			$(this).addClass('was-validated');
		});
	</script>
</body>
</html>

