<?php
require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $referral_percent = floatval($_POST['referral_percent']);
        $closing_fee = floatval($_POST['closing_fee']);
        $zero_referral_threshold = $_POST['zero_referral_threshold'] ? floatval($_POST['zero_referral_threshold']) : null;
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, referral_percent, closing_fee, zero_referral_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $referral_percent, $closing_fee, $zero_referral_threshold]);
    }
    
    elseif (isset($_POST['update_category'])) {
        $id = intval($_POST['id']);
        $name = $_POST['name'];
        $referral_percent = floatval($_POST['referral_percent']);
        $closing_fee = floatval($_POST['closing_fee']);
        $zero_referral_threshold = $_POST['zero_referral_threshold'] ? floatval($_POST['zero_referral_threshold']) : null;
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, referral_percent = ?, closing_fee = ?, zero_referral_threshold = ? WHERE id = ?");
        $stmt->execute([$name, $referral_percent, $closing_fee, $zero_referral_threshold, $id]);
    }
    
    elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    // Similar handlers for shipping_slabs and fba_fulfillment would be added here
}

// Fetch data for display
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$shipping_slabs = $pdo->query("SELECT * FROM shipping_slabs ORDER BY min_weight")->fetchAll(PDO::FETCH_ASSOC);
$fba_slabs = $pdo->query("SELECT * FROM fba_fulfillment ORDER BY min_volume")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Amazon Fee Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">
                    <i class="fas fa-cog me-2"></i>Admin Panel
                </h1>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="name" class="form-control" placeholder="Category Name" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" name="referral_percent" class="form-control" placeholder="Referral %" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" name="closing_fee" class="form-control" placeholder="Closing Fee" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" name="zero_referral_threshold" class="form-control" placeholder="Zero Referral Threshold">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" name="add_category" class="btn btn-success w-100">
                                        <i class="fas fa-plus me-1"></i> Add Category
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Referral %</th>
                                        <th>Closing Fee</th>
                                        <th>Zero Referral Threshold</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                            <td>
                                                <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" class="form-control" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="referral_percent" value="<?= $category['referral_percent'] ?>" class="form-control" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="closing_fee" value="<?= $category['closing_fee'] ?>" class="form-control" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="zero_referral_threshold" value="<?= $category['zero_referral_threshold'] ?>" class="form-control">
                                            </td>
                                            <td>
                                                <button type="submit" name="update_category" class="btn btn-warning btn-sm me-1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="submit" name="delete_category" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Slabs Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Shipping Slabs (Self/Easy Ship)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Min Weight (g)</th>
                                        <th>Max Weight (g)</th>
                                        <th>Fee (₹)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shipping_slabs as $slab): ?>
                                    <tr>
                                        <td><?= $slab['min_weight'] ?></td>
                                        <td><?= $slab['max_weight'] ?></td>
                                        <td><?= $slab['fee'] ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm me-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FBA Slabs Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">FBA Fulfillment Slabs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Min Volume (cm³)</th>
                                        <th>Max Volume (cm³)</th>
                                        <th>Fee (₹)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fba_slabs as $slab): ?>
                                    <tr>
                                        <td><?= $slab['min_volume'] ?></td>
                                        <td><?= $slab['max_volume'] ?></td>
                                        <td><?= $slab['fee'] ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm me-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>