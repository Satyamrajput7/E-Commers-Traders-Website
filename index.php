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
        // Invalid user ID, destroy session and redirect
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    <style>
        .hero-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.15);
        }
        .category-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .category-card:hover {
            transform: scale(1.05); 
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .testimonial-card {
            background: linear-gradient(135deg, #ffffff, #f3f4f6);
            transition: transform 0.3s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-5px);
        }
        .newsletter-bg {
            background: linear-gradient(135deg, #4f46e5, #a855f7);
        }
        .btn-cta {
            transition: transform 0.3s ease, background-color 0.3s ease;
        }
        .btn-cta:hover {
            transform: scale(1.1);
        }
        .nav-link.active {
            color: #4f46e5 !important;
            font-weight: 600;
        }
        .user-greeting {
            color: #4b5563;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans flex flex-col min-h-screen">
    <!-- Custom Navigation Bar -->
    <nav class="bg-gradient-to-r from-indigo-600 to-purple-600 shadow-xl sticky top-0 z-20">
    <div class="container mx-auto px-6 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between">
            <!-- Logo -->
            <a href="#" class="text-3xl font-extrabold text-white tracking-tight transform hover:scale-105 transition-transform duration-300">Traders</a>
            
            <!-- Navigation Links and Search -->
            <div class="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-6 mt-4 lg:mt-0">
                <!-- User Greeting -->
                <div class="p-3 bg-white/10 backdrop-blur-sm rounded-lg text-white">
                    <p class="user-greeting">Welcome, <?php echo htmlspecialchars($user_name); ?>! (<?php echo ucfirst($user_role); ?>)</p>
                </div>
                
                <!-- Nav Links -->
                <div class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-4">
                    <a href="about.php" class="nav-link block px-4 py-2 text-white font-medium rounded-lg hover:bg-white/20 hover:scale-105 transform transition-all duration-300 ease-in-out">About</a>
                    <a href="products.php" class="nav-link block px-4 py-2 text-white font-medium rounded-lg hover:bg-white/20 hover:scale-105 transform transition-all duration-300 ease-in-out">Product</a>
                    <a href="contact.php" class="nav-link block px-4 py-2 text-white font-medium rounded-lg hover:bg-white/20 hover:scale-105 transform transition-all duration-300 ease-in-out">Contact</a>
                    <a href="logout.php" class="nav-link block px-4 py-2 text-white bg-white/20 rounded-lg font-medium hover:bg-white/30 hover:scale-105 transform transition-all duration-300 ease-in-out text-center">Logout</a>
                </div>
                
                <!-- Search Form -->
                <form action="search.php" method="GET" class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-2">
                    <input type="text" name="query" placeholder="Search products..." class="p-2 rounded-lg border border-white/30 bg-white/10 text-white placeholder-white/50 focus:ring-2 focus:ring-white focus:outline-none transition-all duration-300 w-full lg:w-48" required>
                    <button type="submit" class="bg-white text-indigo-600 px-4 py-2 rounded-lg font-medium hover:bg-indigo-100 hover:scale-105 transform transition-all duration-300 ease-in-out">Search for products</button>
                </form>
            </div>
        </div>
    </div>
</nav>
    <!-- Hero Section -->
    <section id="home" class="hero-bg text-white text-center py-24 relative overflow-hidden bg-gradient-to-br from-indigo-900 to-purple-800">
    <div class="container mx-auto px-4 relative z-10">
        <h2 class="text-5xl md:text-7xl font-extrabold mb-6 leading-tight animate-fade-in-down transition-all duration-1000 ease-out transform hover:scale-105">
            Your Marketplace Adventure Begins
        </h2>
        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto animate-fade-in-up transition-all duration-1000 ease-out opacity-90 hover:opacity-100">
            Discover unique products, connect with passionate sellers, and start selling your own creations today.
        </p>
        <div class="flex justify-center space-x-4">
            <a href="#products" class="btn-cta bg-indigo-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-indigo-700 transition-all duration-300 ease-in-out transform hover:scale-110 hover:shadow-lg">
                Shop Now
            </a>
            <a href="signup.php" class="btn-cta bg-white text-indigo-600 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 transition-all duration-300 ease-in-out transform hover:scale-110 hover:shadow-lg">
                Join Now
            </a>
        </div>
    </div>
    <!-- Background overlay for depth -->
    <div class="absolute inset-0 bg-black opacity-20 z-0"></div>
    <!-- Animated background particles -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
    </div>
</section>
   
   


    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h3 class="text-4xl font-bold mb-12 text-gray-800 text-center">Why Choose Marketplace?</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6 bg-gray-50 rounded-lg shadow-lg card-hover">
                    <i class="bi bi-shop text-5xl text-indigo-600 mb-4"></i>
                    <h4 class="text-2xl font-semibold mb-2">Wide Variety</h4>
                    <p class="text-gray-600">Explore thousands of products across diverse categories, from electronics to handmade crafts.</p>
                </div>
                <div class="text-center p-6 bg-gray-50 rounded-lg shadow-lg card-hover">
                    <i class="bi bi-lock text-5xl text-indigo-600 mb-4"></i>
                    <h4 class="text-2xl font-semibold mb-2">Secure Transactions</h4>
                    <p class="text-gray-600">Shop and sell with confidence, backed by our robust security measures.</p>
                </div>
                <div class="text-center p-6 bg-gray-50 rounded-lg shadow-lg card-hover">
                    <i class="bi bi-globe text-5xl text-indigo-600 mb-4"></i>
                    <h4 class="text-2xl font-semibold mb-2">Global Community</h4>
                    <p class="text-gray-600">Connect with buyers and sellers from around the world in our vibrant marketplace.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section id="products" class="py-20 bg-gray-100">
        <div class="container mx-auto px-4">
            <h3 class="text-4xl font-bold mb-12 text-gray-800 text-center">Explore Our Products</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-lg card-hover">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60" alt="Headphones" class="w-full h-48 object-cover rounded mb-4">
                    <h4 class="text-xl font-semibold text-gray-800">Wireless Headphones</h4>
                    <p class="text-gray-600 mb-2">High-quality sound with noise cancellation.</p>
                    <p class="text-lg font-bold text-indigo-600">$99.99</p>
                    <button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">View Details</button>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg card-hover">
                    <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60" alt="Sneakers" class="w-full h-48 object-cover rounded mb-4">
                    <h4 class="text-xl font-semibold text-gray-800">Trendy Sneakers</h4>
                    <p class="text-gray-600 mb-2">Comfortable and stylish for everyday wear.</p>
                    <p class="text-lg font-bold text-indigo-600">$59.99</p>
                    <button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">View Details</button>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg card-hover">
                    <img src="https://images.unsplash.com/photo-1516321497487-e288fb19713f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60" alt="Decor" class="w-full h-48 object-cover rounded mb-4">
                    <h4 class="text-xl font-semibold text-gray-800">Home Decor</h4>
                    <p class="text-gray-600 mb-2">Elegant pieces to enhance your living space.</p>
                    <p class="text-lg font-bold text-indigo-600">$29.99</p>
                    <button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">View Details</button>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg card-hover">
              <img src="images.jpg" alt="Decor" class="w-full h-48 object-cover rounded mb-4">
              <h4 class="text-xl font-semibold text-gray-800">Iphone</h4>
              <p class="text-gray-600 mb-2">Privacy. That’s iPhone</p>
              <p class="text-lg font-bold text-indigo-600">$450</p>
              <button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">View Details</button>
              </div>
            <div class="bg-white p-6 rounded-lg shadow-lg card-hover">
           <img src="watch.jpg" alt="Decor" class="w-full h-48 object-cover rounded mb-4">
           <h4 class="text-xl font-semibold text-gray-800">Apple Watch</h4>
           <p class="text-gray-600 mb-2">Best SmartWatches</p>
            <p class="text-lg font-bold text-indigo-600">$250</p>
             <button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">View Details</button>
               </div>
            <div class="bg-white p-6 rounded-lg shadow-lg card-hover">
           <img src="bat.jpg" alt="Decor" class="w-full h-48 object-cover rounded mb-4">
           <h4 class="text-xl font-semibold text-gray-800">Cricket Bat</h4>
           <p class="text-gray-600 mb-2">Best Crickets Bats
           <p class="text-lg font-bold text-indigo-600">$50</p>
           <button class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">View Details</button>
         </div>

            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h3 class="text-4xl font-bold mb-12 text-gray-800 text-center">What Our Community Says</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="testimonial-card p-6 rounded-lg shadow-lg text-center">
                    <p class="text-gray-600 mb-4">"Marketplace transformed how I sell my art. The platform is intuitive, and the community is amazing!"</p>
                    <h4 class="text-lg font-semibold text-gray-800">Akash Singh</h4>
                    <p class="text-sm text-gray-500">Artist & Seller</p>
                </div>
                <div class="testimonial-card p-6 rounded-lg shadow-lg text-center">
                    <p class="text-gray-600 mb-4">"I found incredible deals on unique items. Shopping here is always a delight!"</p>
                    <h4 class="text-lg font-semibold text-gray-800">Shubham</h4>
                    <p class="text-sm text-gray-500">Buyer</p>
                </div>
                <div class="testimonial-card p-6 rounded-lg shadow-lg text-center">
                    <p class="text-gray-600 mb-4">"The support team is top-notch, and transactions are seamless. Highly recommend!"</p>
                    <h4 class="text-lg font-semibold text-gray-800">Satyam</h4>
                    <p class="text-sm text-gray-500">Buyer & Seller</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-bg text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h3 class="text-4xl font-bold mb-6">Join Our Newsletter</h3>
            <p class="text-xl mb-8 max-w-2xl mx-auto">Stay in the loop with the latest products, exclusive deals, and marketplace updates.</p>
            <form id="newsletterForm" action="subscribe.php" method="POST" class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4 max-w-xl mx-auto">
                <input type="email" id="newsletterEmail" name="email" placeholder="Enter your email" class="p-3 rounded-lg border w-full md:w-2/3 focus:ring-2 focus:ring-indigo-300 text-gray-800" required>
                <button type="submit" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">Subscribe</button>
            </form>
            <p id="formFeedback" class="mt-4 text-sm hidden"></p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-lg font-bold mb-4">Marketplace</h4>
                    <p class="text-sm">Your ultimate destination for buying and selling unique products worldwide.</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#home" class="hover:text-indigo-400">Home</a></li>
                        <li><a href="about.php" class="hover:text-indigo-400">About</a></li>
                        <li><a href="contact.php" class="hover:text-indigo-400">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Account</h4>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="hover:text-indigo-400">Login</a></li>
                        <li><a href="logout.php" class="hover:text-indigo-400">Logout</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-indigo-400" title="Facebook">
                            <i class="bi bi-facebook text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-indigo-400" title="Twitter">
                            <i class="bi bi-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-indigo-400" title="Instagram">
                            <i class="bi bi-instagram text-2xl"></i>
                        </a>
                        <a href="#" class="hover:text-indigo-400" title="LinkedIn">
                            <i class="bi bi-linkedin text-2xl"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-sm">
                <p>© <?php echo date('Y'); ?> Marketplace. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Register GSAP ScrollTrigger
        gsap.registerPlugin(ScrollTrigger);

        // GSAP Animations
        document.addEventListener('DOMContentLoaded', () => {
            // Hero Section Animations
            gsap.from('.hero-bg', {
                opacity: 0,
                duration: 1.5,
                ease: 'power2.out'
            });

            gsap.from('.hero-bg h2', {
                y: 100,
                opacity: 0,
                duration: 1,
                delay: 0.3,
                ease: 'power3.out'
            });

            gsap.from('.hero-bg p', {
                y: 100,
                opacity: 0,
                duration: 1,
                delay: 0.5,
                ease: 'power3.out'
            });

            gsap.from('.btn-cta', {
                scale: 0,
                opacity: 0,
                duration: 0.8,
                stagger: 0.2,
                delay: 0.7,
                ease: 'back.out(1.7)'
            });

            // Header Animation
            gsap.from('nav', {
                y: -100,
                duration: 0.5,
                ease: 'power2.out'
            });

            // Features Section
            gsap.from('.card-hover', {
                opacity: 0,
                y: 50,
                duration: 0.8,
                stagger: 0.2,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '#features',
                    start: 'top 80%'
                }
            });

            // Products Section
            gsap.from('#products .card-hover', {
                opacity: 0,
                y: 50,
                duration: 0.8,
                stagger: 0.2,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '#products',
                    start: 'top 80%'
                }
            });

            // Testimonials Section
            gsap.from('.testimonial-card', {
                opacity: 0,
                y: 50,
                duration: 0.8,
                stagger: 0.2,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '.testimonial-card',
                    start: 'top 80%'
                }
            });

            // Newsletter Section
            gsap.from('.newsletter-bg h3', {
                opacity: 0,
                y: 50,
                duration: 0.8,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '.newsletter-bg',
                    start: 'top 80%'
                }
            });

            gsap.from('.newsletter-bg form', {
                opacity: 0,
                scale: 0.8,
                duration: 0.8,
                delay: 0.3,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '.newsletter-bg',
                    start: 'top 80%'
                }
            });

            // Footer Animation
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

        // Newsletter Form Validation (Client-Side)
        document.getElementById('newsletterForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('newsletterEmail').value;
            const feedback = document.getElementById('formFeedback');

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                feedback.textContent = 'Please enter a valid email address.';
                feedback.classList.remove('hidden', 'text-green-400');
                feedback.classList.add('text-red-400');
                gsap.from(feedback, { opacity: 0, y: 20, duration: 0.5 });
                return;
            }

            // Simulate form submission
            feedback.textContent = 'Thank you for subscribing!';
            feedback.classList.remove('hidden', 'text-red-400');
            feedback.classList.add('text-green-400');
            gsap.from(feedback, { opacity: 0, y: 20, duration: 0.5 });

            // Reset form
            document.getElementById('newsletterForm').reset();
        });
    </script>
</body>
</html>