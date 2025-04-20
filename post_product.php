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

// Handle form submission
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $image_url = null;

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
                $upload_dir = __DIR__ . 'uploads/';
                $upload_path = $upload_dir . $filename;
                $image_url = 'uploads/' . $filename;

                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $error_message = "Failed to save image.";
                }
            }
        }

        // Proceed if no errors
        if (!$error_message) {
            try {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, seller_id, image_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ssdss", $name, $description, $price, $user_id, $image_url);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
                header("Location: dashboard.php");
                exit();
            } catch (Exception $e) {
                $error_message = "Error posting product: " . $e->getMessage();
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
    <title>Post Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h2>Post a New Product</h2>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Product Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Product Description</label>
            <textarea name="description" id="description" class="form-control" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price (₹)</label>
            <input type="number" name="price" id="price" class="form-control" step="0.01" min="0.01" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Product Image (Optional, JPEG/PNG/GIF, Max 5MB)</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/gif">
        </div>
        <button type="submit" class="btn btn-primary">Post Product</button>
    </form>

    <a href="dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</body>
</html>