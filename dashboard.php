<?php
session_start();
require __DIR__ . '/includes/db.php';

// Get user details
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'guest';

// Fetch user role if not set
if ($user_id && !$user_role) {
    $stmt = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
    if ($stmt === false) {
        $_SESSION['error_message'] = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_name, $db_role);
        if ($stmt->fetch()) {
            $_SESSION['user_name'] = $user_name = $db_name;
            $_SESSION['user_role'] = $user_role = $db_role ? $db_role : 'buyer';
        } else {
            session_destroy();
            header("Location: login.php");
            exit();
        }
        $stmt->close();
    }
}

// Handle Add to Quote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_quote']) && $user_role === 'buyer') {
    if (!$user_id) {
        $_SESSION['error_message'] = "Please log in to add items to your quote.";
        header("Location: login.php");
        exit();
    }

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
    header("Location: index.php");
    exit();
}

// Fetch featured products
$products = [];
$error = '';
try {
    $stmt = $conn->prepare("SELECT id, name, description, price, created_at, image_url FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error fetching products: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildMart - Construction Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <style>
        .modal, .cart-sidebar {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.show, .cart-sidebar.show {
            display: block;
            opacity: 1;
        }
        .hero-bg {
            background: linear-gradient(135deg, #ff6200, #fdba74);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.15);
        }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }
        .nav-link.active {
            color: #ff6200 !important;
            font-weight: 700;
            position: relative;
        }
        .nav-link.active::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: #ff6200;
            bottom: -4px;
            left: 0;
        }
        .user-greeting {
            color: #1f2937;
            font-weight: 600;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 20px;
        }
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 350px;
            height: 100%;
            background: #ffffff;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            z-index: 30;
            overflow-y: auto;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 10px;
        }
        .cart-total {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6200;
            margin-top: 20px;
        }
        .btn-cart {
            background: #ff6200;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .btn-cart:hover {
            background: #e55b00;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans flex flex-col min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-xl sticky top-0 z-20">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between">
                <div class="flex items-center justify-between">
                    <a href="index.php" class="text-3xl font-extrabold text-orange-500">BuildMart</a>
                    <button id="cartToggle" class="lg:hidden btn-cart">
                        <i class="bi bi-cart3"></i> Cart
                    </button>
                </div>
                <div class="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-6 mt-4 lg:mt-0">
                    <div class="p-3 bg-gray-100 rounded-full">
                        <p class="user-greeting">Welcome, <?php echo htmlspecialchars($user_name); ?>! (<?php echo htmlspecialchars($user_role); ?>)</p>
                    </div>
                    <a href="about.php" class="nav-link px-4 py-2 text-gray-700 hover:text-orange-500 transition">About</a>
                    <a href="products.php" class="nav-link px-4 py-2 text-gray-700 hover:text-orange-500 transition">Products</a>
                    <a href="contact.php" class="nav-link px-4 py-2 text-gray-700 hover:text-orange-500 transition">Contact</a>
                    <?php if ($user_id): ?>
                        <a href="logout.php" class="nav-link px-4 py-2 text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link px-4 py-2 text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition">Login</a>
                    <?php endif; ?>
                    <button id="cartToggleDesktop" class="btn-cart hidden lg:block">
                        <i class="bi bi-cart3"></i> Cart
                    </button>
                    <form action="products.php" method="GET" class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-2">
                        <input type="text" name="query" placeholder="Search materials..." class="p-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-bg text-white py-24">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-5xl md:text-6xl font-extrabold mb-6">Discover BuildMart</h2>
            <p class="text-xl md:text-2xl mb-8">Your premier marketplace for top-quality Products and services.</p>
            <a href="#products" class="bg-white text-orange-500 px-8 py-4 rounded-full font-semibold hover:bg-gray-100 transition text-lg">Shop Now</a>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto p-6 flex-grow">
        <!-- Alerts -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-500 text-white p-4 rounded-lg mb-6"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Product Listings -->
        <section id="products">
            <h3 class="text-4xl font-extrabold mb-8 text-gray-800">Featured Materials & Services</h3>
            <div id="productGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (empty($products)): ?>
                    <p class="col-span-full text-center text-gray-600 text-lg">No materials or services available at the moment.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white p-6 rounded-xl shadow-lg product-card card-hover">
                            <h4 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover rounded-lg mt-4">
                            <?php endif; ?>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                            <p class="text-xl font-bold text-orange-500 mt-2">Cost: $<?php echo number_format($product['price'], 2); ?></p>
                            <p class="text-sm text-gray-500 mt-1">Posted On: <?php echo $product['created_at']; ?></p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button class="bg-orange-500 text-white px-4 py-2 rounded-lg view-details hover:bg-orange-600 transition" data-product='<?php echo json_encode($product); ?>'>
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <?php if ($user_role === 'buyer'): ?>
                                    <button class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition add-to-cart" data-product='<?php echo json_encode($product); ?>'>
                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                    </button>
                                    <a href="view_cart.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                                        <i class="bi bi-cart"></i> View Cart
                                    </a>
                                <?php endif; ?>
                                <?php if ($user_role === 'seller' && $product['seller_id'] == $user_id): ?>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="POST" action="remove_product.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="cart-sidebar">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Your Cart</h3>
            <button id="closeCart" class="text-gray-600 hover:text-gray-800">
                <i class="bi bi-x-lg text-2xl"></i>
            </button>
        </div>
        <div id="cartItems" class="mb-6"></div>
        <div class="cart-total" id="cartTotal">Total: $0.00</div>
        <a href="view_cart.php" class="block bg-orange-500 text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition text-center mt-4">Proceed to Checkout</a>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div>
                    <h4 class="text-xl font-bold mb-6">BuildMart</h4>
                    <p class="text-sm">Your trusted marketplace for construction materials and services.</p>
                </div>
                <div>
                    <h4 class="text-xl font-bold mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="hover:text-orange-400 transition">Home</a></li>
                        <li><a href="about.php" class="hover:text-orange-400 transition">About</a></li>
                        <li><a href="contact.php" class="hover:text-orange-400 transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xl font-bold mb-6">Account</h4>
                    <ul class="space-y-3">
                        <li><a href="login.php" class="hover:text-orange-400 transition">Login</a></li>
                        <li><a href="logout.php" class="hover:text-orange-400 transition">Logout</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xl font-bold mb-6">Connect</h4>
                    <div class="flex space-x-6">
                        <a href="#" class="hover:text-orange-400 transition" title="Facebook">
                            <i class="bi bi-facebook text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-orange-400 transition" title="Twitter">
                            <i class="bi bi-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-orange-400 transition" title="Instagram">
                            <i class="bi bi-instagram text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-orange-400 transition" title="LinkedIn">
                            <i class="bi bi-linkedin text-2xl"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-12 text-center text-sm">
                <p>© <?php echo date('Y'); ?> BuildMart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modal for Product Details -->
    <div id="productModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-40">
        <div class="bg-white p-8 rounded-xl max-w-lg w-full relative">
            <button id="closeModal" class="absolute top-4 right-4 text-gray-600 hover:text-gray-800">
                <i class="bi bi-x-lg text-2xl"></i>
            </button>
            <h3 id="modalTitle" class="text-3xl font-bold mb-4 text-gray-800"></h3>
            <img id="modalImage" class="w-full h-64 object-cover rounded-lg mb-4" alt="">
            <p id="modalDescription" class="text-gray-600 mb-4"></p>
            <p id="modalPrice" class="text-xl font-bold text-orange-500 mb-4"></p>
            <p id="modalDate" class="text-sm text-gray-500 mb-4"></p>
            <button id="addToCartModal" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition w-full">
                <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
        </div>
    </div>

    <script>
        // GSAP Animations
        document.addEventListener('DOMContentLoaded', () => {
            gsap.from('.hero-bg', {
                opacity: 0,
                duration: 1.2,
                ease: 'power3.out'
            });

            gsap.from('.hero-bg h2', {
                y: 60,
                opacity: 0,
                duration: 1,
                delay: 0.4,
                ease: 'power3.out'
            });

            gsap.from('.hero-bg p', {
                y: 60,
                opacity: 0,
                duration: 1,
                delay: 0.6,
                ease: 'power3.out'
            });

            gsap.from('.hero-bg a', {
                scale: 0.8,
                opacity: 0,
                duration: 0.8,
                delay: 0.8,
                ease: 'back.out(1.7)'
            });

            gsap.from('.product-card', {
                opacity: 0,
                y: 60,
                duration: 0.8,
                stagger: 0.15,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: '#productGrid',
                    start: 'top 75%'
                }
            });

            gsap.from('nav', {
                y: -100,
                duration: 0.6,
                ease: 'power3.out'
            });

            gsap.from('footer', {
                opacity: 0,
                y: 60,
                duration: 0.8,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top 75%'
                }
            });

            gsap.from('.bi', {
                scale: 0,
                duration: 0.6,
                stagger: 0.1,
                ease: 'elastic.out(1, 0.3)',
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top 75%'
                }
            });
        });

        // Cart Functionality
        const cartSidebar = document.getElementById('cartSidebar');
        const cartToggle = document.getElementById('cartToggle');
        const cartToggleDesktop = document.getElementById('cartToggleDesktop');
        const closeCart = document.getElementById('closeCart');
        const cartItemsContainer = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');

        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        function updateCart() {
            cartItemsContainer.innerHTML = '';
            let total = 0;

            cart.forEach((item, index) => {
                total += parseFloat(item.price);
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.innerHTML = `
                    <img src="${item.image_url || ''}" alt="${item.name}" style="display: ${item.image_url ? 'block' : 'none'};">
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold">${item.name}</h4>
                        <p class="text-gray-600">$${parseFloat(item.price).toFixed(2)}</p>
                    </div>
                    <button class="text-red-500 hover:text-red-700 remove-cart-item" data-index="${index}">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                cartItemsContainer.appendChild(cartItem);
            });

            cartTotal.textContent = `Total: $${total.toFixed(2)}`;
            localStorage.setItem('cart', JSON.stringify(cart));

            // Add event listeners for remove buttons
            document.querySelectorAll('.remove-cart-item').forEach(button => {
                button.addEventListener('click', () => {
                    const index = button.dataset.index;
                    cart.splice(index, 1);
                    updateCart();
                });
            });
        }

        function addToCart(product) {
            cart.push(product);
            updateCart();
            cartSidebar.classList.add('show');
            gsap.from(cartSidebar, {
                x: 350,
                opacity: 0,
                duration: 0.5,
                ease: 'power3.out'
            });
        }

        cartToggle.addEventListener('click', () => {
            cartSidebar.classList.toggle('show');
            if (cartSidebar.classList.contains('show')) {
                gsap.from(cartSidebar, {
                    x: 350,
                    opacity: 0,
                    duration: 0.5,
                    ease: 'power3.out'
                });
            }
        });

        cartToggleDesktop.addEventListener('click', () => {
            cartSidebar.classList.toggle('show');
            if (cartSidebar.classList.contains('show')) {
                gsap.from(cartSidebar, {
                    x: 350,
                    opacity: 0,
                    duration: 0.5,
                    ease: 'power3.out'
                });
            }
        });

        closeCart.addEventListener('click', () => {
            cartSidebar.classList.remove('show');
        });

        // Modal Functionality
        const modal = document.getElementById('productModal');
        const closeModalBtn = document.getElementById('closeModal');
        const viewDetailsButtons = document.querySelectorAll('.view-details');
        const addToCartModalBtn = document.getElementById('addToCartModal');

        let currentProduct = null;

        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', () => {
                currentProduct = JSON.parse(button.dataset.product);
                document.getElementById('modalTitle').textContent = currentProduct.name;
                document.getElementById('modalImage').src = currentProduct.image_url || '';
                document.getElementById('modalImage').style.display = currentProduct.image_url ? 'block' : 'none';
                document.getElementById('modalDescription').textContent = currentProduct.description;
                document.getElementById('modalPrice').textContent = `Cost: $${parseFloat(currentProduct.price).toFixed(2)}`;
                document.getElementById('modalDate').textContent = `Posted On: ${currentProduct.created_at}`;
                
                modal.classList.add('show');
                gsap.from(modal.querySelector('.bg-white'), {
                    scale: 0.8,
                    opacity: 0,
                    duration: 0.4,
                    ease: 'power3.out'
                });
            });
        });

        addToCartModalBtn.addEventListener('click', () => {
            if (currentProduct) {
                addToCart(currentProduct);
                modal.classList.remove('show');
            }
        });

        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', () => {
                const product = JSON.parse(button.dataset.product);
                addToCart(product);
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

        // Initialize cart
        updateCart();
    </script>
</body>
</html>



































































































4">




















">Remove</button>






































































































































































