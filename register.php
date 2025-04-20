<?php
session_start();
require 'includes/db.php'; // Ensure database connection is correct

// Function to clean user input
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$error = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = clean_input($_POST['email']);
    $password = $_POST['password']; // Don't trim passwords (may remove valid spaces)
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : "";
    $role = isset($_POST['role']) ? clean_input($_POST['role']) : "";

    // ✅ Check if all required fields are provided
    if (empty($email) || empty($password) || empty($name)) {
        $error = "❌ Please enter name, email, and password.";
    } else {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ Invalid email format.";
        } else {
            // Check if email already exists
            $check_email = "SELECT id FROM users WHERE email = ?";
            $stmt_check = $conn->prepare($check_email);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $error = "❌ Email already registered. Try logging in.";
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user
                $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $name, $email, $password_hash, $role);

                if ($stmt->execute()) {
                    // Ensure session is set before redirecting
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = $role;

                    // Redirect to dashboard (adjust if needed)
                    header("Location: ../auth/login.php");
                    exit();
                } else {
                    $error = "❌ Error: " . $conn->error;
                }
            }
            $stmt_check->close();
            if (isset($stmt)) $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(
                135deg,
                rgba(102, 126, 234, 0.8),
                rgba(118, 75, 162, 0.8)
            ),
            url("https://img.freepik.com/free-vector/geometric-gradient-futuristic-background_23-2149116406.jpg?t=st=1739959299~exp=1739962899~hmac=6e7276f197a3a57403675da4c699c2c832019d6e3584f92bf0b6479c3c776115&w=1060");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 50px;
            width: 80%;
        }

        .text-container {
            width: 400px;
            color: white;
            text-align: center;
            opacity: 0;
        }

        .text-container h1 {
            font-size: 30px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .text-container p {
            font-size: 16px;
            font-weight: 500;
            line-height: 1.5;
            min-height: 50px;
        }

        .login-container {
            width: 400px;
            padding: 40px;
            background-color: white;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            text-align: center;
            margin-left: 80px;
            opacity: 0;
            transform: scale(0.8);
            position: relative;
        }

        h2 {
            margin-bottom: 30px;
            margin-top: 15px;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input-box {
            width: 100%;
            margin-bottom: 12px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            transition: 0.3s;
            opacity: 0;
        }

        .input-box:focus {
            border-color: #667eea;
            outline: none;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
            opacity: 0;
            margin-top: 25px;
        }

        .login-btn:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-container">
            <h1>Believe in Your Growth!</h1>
            <p id="dynamic-text"></p>
        </div>
        <div class="login-container">
            <h2>Register</h2>
            <?php if (!empty($error)) { ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php } ?>
            <form method="POST">
                <input type="text" name="name" class="input-box" required placeholder="Name">
                <input type="email" name="email" class="input-box" required placeholder="Email">
                <input type="password" name="password" class="input-box" required placeholder="Password">
                <select name="role" class="input-box" required>
                    <option value="seller">Seller</option>
                    <option value="buyer">Buyer</option>
                </select>
                <button type="submit" class="login-btn">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    <script>
        gsap.registerPlugin(TextPlugin);

        // Typewriter Effect
        const quotes = [
            "💪 Believe in Yourself! Hard work and dedication will lead to success...🚀",
            "📚 Keep Learning, Keep Growing! Knowledge is the key to unlocking your potential...🔑",
            "🔥 Success Comes from Consistency! Small daily improvements lead to stunning results...🌟",
            "🌍 Your Future is in Your Hands! Every decision you make shapes your tomorrow... ✨",
            "🌠 Dream Big, Work Hard! Your efforts today will define your achievements tomorrow...🏆",
            "🚧 Failure is Just a Stepping Stone! Learn from it and keep moving forward...💡",
            "🎯 Stay Focused, Stay Determined! Success is closer than you think...🏅"
        ];

        let textElement = document.querySelector("#dynamic-text"); // Fixed: Target correct element
        let quoteIndex = 0;
        let charIndex = 0;
        let isDeleting = false;

        function typeEffect() {
            let currentQuote = quotes[quoteIndex];
            
            if (isDeleting) {
                textElement.textContent = currentQuote.substring(0, charIndex--);
            } else {
                textElement.textContent = currentQuote.substring(0, charIndex++);
            }

            let typingSpeed = isDeleting ? 50 : 100; // Erase faster, type slower

            if (!isDeleting && charIndex === currentQuote.length + 1) {
                isDeleting = true;
                typingSpeed = 1500; // Pause before erasing
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                quoteIndex = (quoteIndex + 1) % quotes.length;
                typingSpeed = 500; // Pause before typing new text
            }

            setTimeout(typeEffect, typingSpeed);
        }

        // Start the typewriter effect after the page loads
        document.addEventListener("DOMContentLoaded", () => {
            setTimeout(typeEffect, 1000);
        });

        // GSAP Animations for UI
        gsap.to(".text-container", { opacity: 1, x: 0, duration: 2 });
        gsap.to(".login-container", { opacity: 1, scale: 1, duration: 1 });
        gsap.to(".input-box, .login-btn", {
            opacity: 1,
            duration: 1,
            stagger: 0.3,
        });
    </script>
</body>
</html>