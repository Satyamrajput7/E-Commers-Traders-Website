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
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Fetch cart items
$cart_items = [];
$error = '';

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_url
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ");
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error fetching cart items: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="container mt-4">
    <h2>Your Cart, <?php echo htmlspecialchars($user_name); ?>!</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <p>Your cart is empty.</p>
        <a href="../views/dashboard.php" class="btn btn-primary">Continue Shopping</a>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['image_url']): ?>
                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid" style="max-width: 50px;">
                            <?php else: ?>
                                <span>No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                    <td>
                        <?php
                        $grand_total = 0;
                        foreach ($cart_items as $item) {
                            $grand_total += $item['price'] * $item['quantity'];
                        }
                        echo '$' . number_format($grand_total, 2);
                        ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <a href="../views/dashboard.php" class="btn btn-primary">Continue Shopping</a>
        <!-- Placeholder for future checkout button -->
        <button class="btn btn-success disabled" disabled>Proceed to Checkout</button>
    <?php endif; ?>

    <a href="../auth/logout.php" class="btn btn-danger mt-4">Logout</a>
</body>
</html>