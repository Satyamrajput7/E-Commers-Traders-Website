<?php
session_start();
require '../includes/db.php';

// Redirect if not logged in or not a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'buyer') {
    header("Location: ../auth/login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];

// Handle form submission
$error_message = '';
$success_message = '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id > 0) {
    try {
        // Verify product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product) {
            // Check if product is already in cart
            $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cart_item = $result->fetch_assoc();
            $stmt->close();

            if ($cart_item) {
                // Update quantity if already in cart
                $new_quantity = $cart_item['quantity'] + 1;
                $stmt = $conn->prepare("UPDATE cart SET quantity = ?, added_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
                $success_message = "Product quantity updated in cart.";
            } else {
                // Add new cart item
                $quantity = 1;
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
                $success_message = "Product added to cart.";
            }
        } else {
            $error_message = "Product not found.";
        }
    } catch (Exception $e) {
        $error_message = "Error adding to cart: " . $e->getMessage();
    }
} else {
    $error_message = "Invalid product ID.";
}

// Store messages in session and redirect
if ($error_message) {
    $_SESSION['error_message'] = $error_message;
} elseif ($success_message) {
    $_SESSION['success_message'] = $success_message;
}
header("Location: ../views/dashboard.php");
exit();
?>