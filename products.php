<?php
session_start();
require __DIR__ . '/includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

// Fetch user details if not in session
if (!$user_name || !$user_role) {
    $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_username, $db_role);
    if ($stmt->fetch()) {
        $_SESSION['user_name'] = $user_name = $db_username;
        $_SESSION['user_role'] = $user_role = $db_role ? $db_role : 'buyer';
    } else {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $stmt->close();
}

// Handle Add to Quote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_quote']) && $user_role === 'buyer') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

    if ($product_id && $quantity > 0) {
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
        if ($stmt === false) {
            $_SESSION['error_message'] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                if ($stmt === false) {
                    $_SESSION['error_message'] = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("ii", $user_id, $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $new_quantity = $row['quantity'] + $quantity;
                        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                        $stmt->bind_param("ii", $new_quantity, $row['id']);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                    }
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Item added to quote!";
                    } else {
                        $_SESSION['error_message'] = "Failed to add item to quote.";
                    }
                }
            } else {
                $_SESSION['error_message'] = "Invalid or inactive product.";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error_message'] = "Invalid input.";
    }
    header("Location: products.php");
    exit();
}

// Fetch products with filters and pagination
$search = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING) ?: '';
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?: '';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

$where_clauses = ["status = 'active'"];
$params = [];
$types = '';

if ($user_role === 'seller') {
    $where_clauses[] = "seller_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

if ($search) {
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$where = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";
$order_by = $sort === 'price_asc' ? "price ASC" : ($sort === 'price_desc' ? "price DESC" : "created_at DESC");

try {
    // Count total products for pagination
    $count_sql = "SELECT COUNT(*) FROM products $where";
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt === false) {
        $_SESSION['error_message'] = "Prepare failed: " . $conn->error . " | Query: $count_sql";
        $products = [];
        $total_pages = 1;
    } else {
        if ($params) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $total_products = $count_stmt->get_result()->fetch_row()[0];
        $total_pages = ceil($total_products / $per_page);
        $count_stmt->close();

        // Fetch products
        $sql = "SELECT id, name, description, price, created_at, image_url, status FROM products $where ORDER BY $order_by LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $_SESSION['error_message'] = "Prepare failed: " . $conn->error . " | Query: $sql";
            $products = [];
        } else {
            $params[] = $per_page;
            $params[] = $offset;
            $types .= 'ii';
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching products: " . $e->getMessage();
    $products = [];
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildMart - Materials & Services</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <style>
        .modal {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.show {
            display: block;
            opacity: 1;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .product-card {
            transition: transform 0.3s ease;
        }
        .nav-link.active {
            color: #f97316 !important;
            font-weight: 600;
        }
        .user-greeting {
            color: #4b5563;
            font-weight: 500;
        }
        .pagination a {
            transition: background-color 0.3s ease;
        }
        .pagination a:hover {
            background-color: #fed7aa;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans flex flex-col min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg sticky top-0 z-20">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col lg:flex-row lg:items-center">
                <a href="index.php" class="text-2xl font-bold text-orange-500 mb-4 lg:mb-0">BuildMart</a>
                <div class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-4 w-full lg:w-auto">
                    <div class="p-4 bg-gray-200 rounded-lg">
                        <p class="user-greeting">Welcome, <?php echo htmlspecialchars($user_name); ?>! (<?php echo ucfirst($user_role); ?>)</p>
                    </div>
                    <a href="about.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500">About</a>
                    <a href="dashboard.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500">Dashboard</a>
                    <a href="products.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500 active">Materials & Services</a>
                    <?php if ($user_role === 'buyer'): ?>
                        <a href="view_cart.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500">Quote</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'seller'): ?>
                        <a href="post_product.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500">Add Product</a>
                    <?php endif; ?>
                    <a href="contact.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500">Contact</a>
                    <a href="logout.php" class="nav-link block px-4 py-2 text-white bg-orange-500 rounded-lg lg:hover:bg-orange-600 text-center">Logout</a>
                    <form action="products.php" method="GET" class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-2">
                        <input type="text" name="query" placeholder="Search materials..." value="<?php echo htmlspecialchars($search); ?>" class="p-2 rounded-lg border focus:ring-2 focus:ring-orange-300 text-gray-800 w-full lg:w-40" required>
                        <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 mt-2 lg:mt-0">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto p-6 flex-grow">
        <!-- Alerts -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Search and Sort -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
            <form action="products.php" method="GET" class="w-full md:w-1/3">
                <input type="text" name="query" placeholder="Search materials..." value="<?php echo htmlspecialchars($search); ?>" class="p-2 rounded border w-full focus:ring-2 focus:ring-orange-500">
            </form>
            <select name="sort" onchange="window.location.href='products.php?sort='+this.value+'&query=<?php echo urlencode($search); ?>'" class="p-2 rounded border w-full md:w-1/4 focus:ring-2 focus:ring-orange-500">
                <option value="">Sort by Date</option>
                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Cost: Low to High</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Cost: High to Low</option>
            </select>
        </div>

        <!-- Seller Actions -->
        <?php if ($user_role === 'seller'): ?>
            <div class="mb-6">
                <a href="post_product.php" class="bg-orange-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-orange-600">Add New Material/Service</a>
            </div>
        <?php endif; ?>

        <!-- Product Listings -->
        <section id="products">
            <h3 class="text-3xl font-bold mb-6 text-gray-800">Materials & Services</h3>
            <div id="productGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($products)): ?>
                    <p class="col-span-full text-center text-gray-600">No materials or services found.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white p-4 rounded-lg shadow product-card card-hover">
                            <h4 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover rounded mb-4">
                            <?php endif; ?>
                            <p class="text-gray-600"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            <p class="text-lg font-bold text-orange-500">Cost: $<?php echo number_format($product['price'], 2); ?></p>
                            <p class="text-sm text-gray-500">Posted On: <?php echo $product['created_at']; ?></p>
                            <p class="text-sm text-gray-500">Status: <?php echo ucfirst($product['status']); ?></p>
                            <div class="mt-4 flex space-x-2">
                                <button class="bg-orange-500 text-white px-4 py-2 rounded view-details hover:bg-orange-600" data-product='<?php echo json_encode($product); ?>'>View Details</button>
                                <?php if ($user_role === 'buyer'): ?>
                                    <form method="POST" action="products.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="add_to_quote" value="1">
                                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" title="Add to Quote">
                                            <i class="bi bi-cart-plus"></i> Add to Quote
                                        </button>
                                    </form>
                                    <a href="view_cart.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" title="View Quote">View Quote</a>
                                <?php endif; ?>
                                <?php if ($user_role === 'seller'): ?>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</a>
                                    <form method="POST" action="remove_product.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" onclick="return confirm('Are you sure you want to delete this item?');">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center space-x-2 pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="products.php?page=<?php echo $i; ?>&query=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" class="px-4 py-2 rounded-lg <?php echo $page === $i ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-orange-100"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-lg font-bold mb-4">BuildMart</h4>
                    <p class="text-sm">Your trusted marketplace for construction materials and services.</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="hover:text-orange-400">Home</a></li>
                        <li><a href="about.php" class="hover:text-orange-400">About</a></li>
                        <li><a href="contact.php" class="hover:text-orange-400">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Account</h4>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="hover:text-orange-400">Login</a></li>
                        <li><a href="logout.php" class="hover:text-orange-400">Logout</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-orange-400" title="Facebook">
                            <i class="bi bi-facebook text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-orange-400" title="Twitter">
                            <i class="bi bi-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-orange-400" title="Instagram">
                            <i class="bi bi-instagram text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-orange-400" title="LinkedIn">
                            <i class="bi bi-linkedin text-2xl"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-sm">
                <p>© <?php echo date('Y'); ?> BuildMart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modal for Product Details -->
    <div id="productModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg max-w-lg w-full">
            <h3 id="modalTitle" class="text-2xl font-bold mb-4 text-gray-800"></h3>
            <img id="modalImage" class="w-full h-64 object-cover rounded mb-4" alt="">
            <p id="modalDescription" class="text-gray-600 mb-4"></p>
            <p id="modalPrice" class="text-lg font-bold text-orange-500 mb-4"></p>
            <p id="modalDate" class="text-sm text-gray-500 mb-4"></p>
            <p id="modalStatus" class="text-sm text-gray-500 mb-4"></p>
            <button id="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Close</button>
        </div>
    </div>

    <script>
        // GSAP Animations
        document.addEventListener('DOMContentLoaded', () => {
            gsap.from('nav', {
                y: -100,
                duration: 0.5,
                ease: 'power2.out'
            });

            gsap.from('.product-card', {
                opacity: 0,
                y: 50,
                duration: 0.8,
                stagger: 0.2,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '#productGrid',
                    start: 'top 80%'
                }
            });

            gsap.from('.pagination a', {
                opacity: 0,
                y: 20,
                duration: 0.5,
                stagger: 0.1,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '.pagination',
                    start: 'top 90%'
                }
            });

            gsap.from('footer', {
                opacity: 0,
                y: 50,
                duration: 0.8,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top 80%'
                }
            });

            gsap.from('.bi', {
                scale: 0,
                duration: 0.5,
                stagger: 0.1,
                ease: 'elastic.out(1, 0.3)',
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top 80%'
                }
            });
        });

        // Modal Functionality
        const modal = document.getElementById('productModal');
        const closeModalBtn = document.getElementById('closeModal');
        const viewDetailsButtons = document.querySelectorAll('.view-details');

        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', () => {
                const product = JSON.parse(button.dataset.product);
                document.getElementById('modalTitle').textContent = product.name;
                document.getElementById('modalImage').src = product.image_url || '';
                document.getElementById('modalImage').style.display = product.image_url ? 'block' : 'none';
                document.getElementById('modalDescription').textContent = product.description;
                document.getElementById('modalPrice').textContent = `Cost: $${parseFloat(product.price).toFixed(2)}`;
                document.getElementById('modalDate').textContent = `Posted On: ${product.created_at}`;
                document.getElementById('modalStatus').textContent = `Status: ${product.status.charAt(0).toUpperCase() + product.status.slice(1)}`;
                
                modal.classList.add('show');
                gsap.from(modal.querySelector('.bg-white'), {
                    scale: 0.8,
                    opacity: 0,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });

        closeModalBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    </script>
</body>
</html>