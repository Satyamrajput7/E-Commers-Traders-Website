<?php
session_start();
require 'includes/db.php';

// Redirect if not logged in or not a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$product = null;
$error_message = '';
$stmt = $conn->prepare("SELECT name, description, price, image_url FROM products WHERE id = ? AND seller_id = ?");
if ($stmt === false) {
    $error_message = "Database error: " . $conn->error;
} else {
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

if (!$product && !$error_message) {
    $error_message = "Product not found or you don’t have permission to edit it.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $image_url = $product['image_url']; // Keep existing image by default

    // Validate inputs
    if (empty($name) || empty($description) || empty($price)) {
        $error_message = "Please fill all required fields.";
    } elseif ($price <= 0) {
        $error_message = "Price must be greater than zero.";
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            // Validate image
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error_message = "Error uploading image.";
            } elseif (!in_array($file['type'], $allowed_types)) {
                $error_message = "Only JPEG, PNG, and GIF images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "Image size must be less than 5MB.";
            } else {
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('product_') . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/';
                $upload_path = $upload_dir . $filename;
                $image_url = 'uploads/' . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old image if it exists
                    if ($product['image_url'] && file_exists(__DIR__ . '/../' . $product['image_url'])) {
                        unlink(__DIR__ . '/../' . $product['image_url']);
                    }
                } else {
                    $error_message = "Failed to save image.";
                }
            }
        }

        // Update product if no errors
        if (!$error_message) {
            try {
                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ? AND seller_id = ?");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ssdssi", $name, $description, $price, $image_url, $product_id, $user_id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
                header("Location: dashboard.php");
                exit();
            } catch (Exception $e) {
                $error_message = "Error updating product: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h2>Edit Product</h2>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($product): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Product Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Product Image (Optional, JPEG/PNG/GIF, Max 5MB)</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                <?php if ($product['image_url']): ?>
                    <p>Current Image: <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current Image" class="img-fluid mt-2" style="max-width: 100px;"></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    <?php else: ?>
        <p>No product to edit.</p>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</body>
</html>