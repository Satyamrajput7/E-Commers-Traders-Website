<?php
session_start();
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildMart - About Us</title>
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
        @keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
}

/* Ensure responsiveness */
@media (max-width: 768px) {
    footer {
        padding-top: 3rem;
        padding-bottom: 3rem;
        background-color: blue;
    }

    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .grid {
        gap: 2rem;
    }

    h4 {
        font-size: 1.125rem;
    }

    .text-sm {
        font-size: 0.875rem;
    }
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.6s ease-out forwards;
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }

    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    input[name="query"] {
        width: 100%;
    }
}

@media (min-width: 1024px) {
    .nav-link:hover {
        transform: scale(1.05) translateY(-2px);
    }
}

</style>

</head>
<body class="bg-gray-100 font-sans flex flex-col min-h-screen">
    <!-- Navigation Bar -->

    <nav class="bg-white shadow-lg sticky top-0 z-20 transition-all duration-300 ease-in-out">
    <div class="container mx-auto px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between">
            <!-- Logo -->
            <a href="index.php" class="text-3xl font-extrabold text-orange-500 mb-4 lg:mb-0 animate-fade-in">
                BuildMart
            </a>
            <!-- Navigation Links and Search -->
            <div class="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-6 w-full lg:w-auto">
                <!-- User Greeting -->
                <div class="p-3 bg-gray-100 rounded-lg transition-all duration-300 hover:bg-gray-200 animate-fade-in-up">
                    <p class="user-greeting text-gray-800 text-sm font-medium">
                        Welcome, <?php echo htmlspecialchars($user_name); ?>! (<?php echo ucfirst($user_role); ?>)
                    </p>
                </div>
                <!-- Nav Links -->
                <div class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-4">
                    <a href="about.php" class="nav-link block px-4 py-2 text-gray-700 text-base font-semibold rounded-lg hover:bg-orange-100 hover:text-orange-500 transition-all duration-300 ease-in-out transform hover:scale-105 lg:hover:bg-transparent active">
                        About
                    </a>
                    <a href="products.php" class="nav-link block px-4 py-2 text-gray-700 text-base font-semibold rounded-lg hover:bg-orange-100 hover:text-orange-500 transition-all duration-300 ease-in-out transform hover:scale-105 lg:hover:bg-transparent">
                        Materials & Services
                    </a>
                    <a href="contact.php" class="nav-link block px-4 py-2 text-gray-700 text-base font-semibold rounded-lg hover:bg-orange-100 hover:text-orange-500 transition-all duration-300 ease-in-out transform hover:scale-105 lg:hover:bg-transparent">
                        Contact
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="nav-link block px-6 py-2 text-white bg-orange-500 rounded-lg text-base font-semibold hover:bg-orange-600 transition-all duration-300 ease-in-out transform hover:scale-105 text-center shadow-md">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link block px-6 py-2 text-white bg-orange-500 rounded-lg text-base font-semibold hover:bg-orange-600 transition-all duration-300 ease-in-out transform hover:scale-105 text-center shadow-md">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Search Form -->
                <form action="products.php" method="GET" class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-2 animate-fade-in-up" style="animation-delay: 0.2s;">
                    <input type="text" name="query" placeholder="Search materials..." class="p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-300 text-gray-800 w-full lg:w-48 transition-all duration-300 focus:border-orange-500" required>
                    <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-all duration-300 ease-in-out transform hover:scale-105 shadow-md">
                        Search
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

    <!-- About Section -->
    <section class="container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6 text-gray-800">About BuildMart</h2>
        <p class="text-gray-600 mb-4">BuildMart is your trusted online marketplace for all construction needs. We connect buyers with reliable suppliers and contractors, offering a wide range of materials like bricks, cement, and steel, as well as professional services such as masonry and project management.</p>
        <p class="text-gray-600 mb-4">Our mission is to streamline the construction industry by providing a platform where quality meets convenience. Whether you’re a contractor sourcing materials or a homeowner planning a renovation, BuildMart ensures transparency, competitive pricing, and dependable service.</p>
        <a href="products.php" class="bg-orange-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-orange-600">Explore Materials & Services</a>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16 relative overflow-hidden">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
            <!-- BuildMart Info -->
            <div class="animate-fade-in-up">
                <h4 class="text-xl font-bold mb-6 tracking-wide">BuildMart</h4>
                <p class="text-sm text-gray-300 leading-relaxed max-w-xs">
                    Your trusted marketplace for construction materials and services.
                </p>
            </div>
            <!-- Quick Links -->
            <div class="animate-fade-in-up" style="animation-delay: 0.2s;">
                <h4 class="text-xl font-bold mb-6 tracking-wide">Quick Links</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="index.php" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:translate-x-2">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="about.php" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:translate-x-2">
                            About
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:translate-x-2">
                            Contact
                        </a>
                    </li>
                </ul>
            </div>
            <!-- Account -->
            <div class="animate-fade-in-up" style="animation-delay: 0.4s;">
                <h4 class="text-xl font-bold mb-6 tracking-wide">Account</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="login.php" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:translate-x-2">
                            Login
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:translate-x-2">
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
            <!-- Connect -->
            <div class="animate-fade-in-up" style="animation-delay: 0.6s;">
                <h4 class="text-xl font-bold mb-6 tracking-wide">Connect</h4>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:scale-125" title="Facebook">
                        <i class="bi bi-facebook text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:scale-125" title="Twitter">
                        <i class="bi bi-twitter text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:scale-125" title="Instagram">
                        <i class="bi bi-instagram text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-orange-400 transition-all duration-300 ease-in-out transform hover:scale-125" title="LinkedIn">
                        <i class="bi bi-linkedin text-2xl"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- Copyright -->
        <div class="mt-12 pt-8 border-t border-gray-700 text-center text-sm text-gray-400 animate-fade-in-up" style="animation-delay: 0.8s;">
            <p>© <?php echo date('Y'); ?> BuildMart. All rights reserved.</p>
        </div>
    </div>
    <!-- Subtle background effect -->
    <div class="absolute inset-0 bg-gradient-to-t from-gray-800 to-transparent opacity-50 z-0"></div>
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