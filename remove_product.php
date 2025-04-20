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

// Handle form submission
$error_message = '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id > 0) {
    try {
        // Fetch image_url to delete the file
        $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ? AND seller_id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ii", $product_id, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product) {
            // Delete associated image file
            if ($product['image_url'] && file_exists(__DIR__ . '/../' . $product['image_url'])) {
                unlink(__DIR__ . '/../' . $product['image_url']);
            }

            // Delete product from database
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $product_id, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $error_message = "Product not found or you don’t have permission to delete it.";
        }
    } catch (Exception $e) {
        $error_message = "Error deleting product: " . $e->getMessage();
    }
} else {
    $error_message = "Invalid product ID.";
}

// Redirect to dashboard (with error message in session if needed)
if ($error_message) {
    $_SESSION['error_message'] = $error_message;
}
header("Location: dashboard.php");
exit();
?>