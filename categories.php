<?php
require_once 'config/app.php';
requireAuth();

$pageTitle = 'Categories';
$categoryClass = new Category($db);

$action = $_GET['action'] ?? 'list';
$categoryId = $_GET['id'] ?? null;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['_token'] ?? '')) {
        $message = '<div class="alert alert-danger">Invalid security token</div>';
    } else {
        switch ($action) {
            case 'create':
                $result = $categoryClass->createCategory($_POST);
                $message = $result['success'] 
                    ? '<div class="alert alert-success">Category created successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: categories.php');
                    exit;
                }
                break;

            case 'edit':
                $result = $categoryClass->updateCategory($categoryId, $_POST);
                $message = $result['success'] 
                    ? '<div class="alert alert-success">Category updated successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                break;

            case 'delete':
                $result = $categoryClass->deleteCategory($categoryId);
                $message = $result['success']
                    ? '<div class="alert alert-success">Category deleted successfully</div>'
                    : '<div class="alert alert-danger">Error: ' . $result['message'] . '</div>';
                if ($result['success']) {
                    header('Location: categories.php');
                    exit;
                }
                break;
        }
    }
}

$categories = $categoryClass->getAllCategories();

if (in_array($action, ['edit', 'view']) && $categoryId) {
    $category = $categoryClass->getCategoryById($categoryId);
    if (!$category) {
        header('Location: categories.php');
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
                    Categories
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
                    <li class="breadcrumb-item active">Categories</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php echo $message; ?>

        <?php if ($action === 'list'): ?>
            <!-- Categories List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Categories</h3>
                    <div class="card-tools">
                        <a href="categories.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add Category
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($categories as $category): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <?php if ($category['image']): ?>
                                        <img src="<?php echo htmlspecialchars($category['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                        <p class="card-text flex-grow-1"><?php echo htmlspecialchars($category['description']); ?></p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <small class="text-muted">
                                                <?php echo $category['product_count']; ?> products
                                            </small>
                                            <div>
                                                <span class="badge badge-<?php echo $category['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <div class="btn-group w-100">
                                                <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="updateStatus('category', <?php echo $category['id']; ?>, '<?php echo $category['is_active'] ? 'inactive' : 'active'; ?>')">
                                                    <i class="fas fa-toggle-<?php echo $category['is_active'] ? 'off' : 'on'; ?>"></i>
                                                    <?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                                <?php if ($category['product_count'] == 0): ?>
                                                    <form method="POST" class="d-inline" action="categories.php?action=delete&id=<?php echo $category['id']; ?>">
                                                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif (in_array($action, ['create', 'edit'])): ?>
            <!-- Category Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $action === 'create' ? 'Add New Category' : 'Edit Category'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please provide a category name.</div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Brief description of this category</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="image">Category Image URL</label>
                                    <input type="url" class="form-control" id="image" name="image" 
                                           value="<?php echo htmlspecialchars($category['image'] ?? ''); ?>" 
                                           placeholder="https://example.com/image.jpg">
                                    <small class="form-text text-muted">Optional: URL to category image</small>
                                </div>

                                <?php if (!empty($category['image'])): ?>
                                    <div class="form-group">
                                        <label>Current Image</label>
                                        <div class="text-center">
                                            <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                                 class="img-thumbnail" style="max-width: 200px;" 
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo $action === 'create' ? 'Create Category' : 'Update Category'; ?>
                            </button>
                            <a href="categories.php" class="btn btn-secondary ml-2">
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