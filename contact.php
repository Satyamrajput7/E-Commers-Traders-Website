<?php
session_start();
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'guest';

// Handle form submission (placeholder)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if ($name && $email && $message) {
        // Implement email sending or database storage here
        $_SESSION['success_message'] = "Your message has been sent!";
        header("Location: contact.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Please fill out all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildMart - Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <style>
        .nav-link.active {
            color: #f97316 !important;
            font-weight: 600;
        }
        .user-greeting {
            color: #4b5563;
            font-weight: 500;
        }
        .nav-link.active {
            color: #f97316 !important;
            font-weight: 600;
        }
        .user-greeting {
            color: #4b5563;
            font-weight: 500;
        }
        .send-message-btn {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 10;
            background-color: #f97316 !important;
            color: #ffffff !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .send-message-btn:hover {
            background-color: #ea580c !important;
            transform: scale(1.05);
        }
        .contact-form {
            opacity: 1;
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
                    <a href="products.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500">Materials & Services</a>
                    <a href="contact.php" class="nav-link block px-4 py-2 text-gray-700 hover:bg-orange-100 lg:hover:bg-transparent lg:hover:text-orange-500 active">Contact</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="nav-link block px-4 py-2 text-white bg-orange-500 rounded-lg lg:hover:bg-orange-600 text-center">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link block px-4 py-2 text-white bg-orange-500 rounded-lg lg:hover:bg-orange-600 text-center">Login</a>
                    <?php endif; ?>
                    <form action="products.php" method="GET" class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-2">
                        <input type="text" name="query" placeholder="Search materials..." class="p-2 rounded-lg border focus:ring-2 focus:ring-orange-300 text-gray-800 w-full lg:w-40" required>
                        <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 mt-2 lg:mt-0">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contact Section -->
    <section class="container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Contact Us</h2>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <form method="POST" action="contact.php" class="max-w-lg mx-auto">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-semibold mb-2">Name</label>
                <input type="text" name="name" id="name" class="w-full p-2 rounded border focus:ring-2 focus:ring-orange-500" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full p-2 rounded border focus:ring-2 focus:ring-orange-500" required>
            </div>
            <div class="mb-4">
                <label for="message" class="block text-gray-700 font-semibold mb-2">Message</label>
                <textarea name="message" id="message" rows="5" class="w-full p-2 rounded border focus:ring-2 focus:ring-orange-500" required></textarea>
            </div>
            <button type="submit" class="send-message-btn bg-orange-500 text-white px-6 py-3 rounded-lg font-semibold 
hover:bg-orange-600">Send Message</button>
    </section>

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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            gsap.from('section', {
                opacity: 0,
                y: 50,
                duration: 1,
                ease: 'power2.out'
            });
        });
    </script>
</body>
</html>