<?php
// --- 1. CONFIGURATION & SESSION START ---
session_start();

// Database Credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); 
define('DB_NAME', 'shelema'); 
define('DB_TABLE', 'users'); // Updated to use a dedicated users table

$error_message = "";
$success_message = "";
$current_action = $_GET['action'] ?? 'login'; 

// --- 2. LOGOUT LOGIC ---
if ($current_action == 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php'); // Redirect to login page after logout
    exit();
}

// --- 3. DATABASE CONNECTION ---
$conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    $db_error = "Database connection failed. Please ensure the 'shelema' database exists.";
} else {
    $db_error = "";
}

// --- 4. FORM PROCESSING ---
if (!isset($_SESSION['user_id']) && $_SERVER["REQUEST_METHOD"] == "POST" && empty($db_error)) {
    
    // --- LOGIN LOGIC ---
    if (isset($_POST['login_submit'])) {
        $email = trim($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';
        
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM " . DB_TABLE . " WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: home.php'); // Successful login redirects to portfolio
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
        $stmt->close();
    }

    // --- SIGN UP LOGIC ---
    if (isset($_POST['register_submit'])) {
        $username = trim($_POST['register_username'] ?? '');
        $email = trim($_POST['register_email'] ?? '');
        $password = trim($_POST['register_password'] ?? '');
        
        if (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT id FROM " . DB_TABLE . " WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_message = "Email is already registered.";
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO " . DB_TABLE . " (username, email, password_hash) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $username, $email, $password_hash);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Registration successful! You can now log in.";
                    $current_action = 'login'; 
                } else {
                    $error_message = "Registration failed. Please try again.";
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }
}

if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}

$wrapper_class = ($current_action === 'register') ? 'active' : (($current_action === 'forgot') ? 'forgot-active' : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambo U Auth - Dark Neon</title>
    <!-- Use Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* --- Dark Neon Theme Styling --- */
        :root {
            /* Dark/Black background and card colors */
            --bg-color: #0d0d0d; 
            --card-bg: #1a1a1a;
            /* Neon Accent Colors */
            --accent-color: #00ffc8; /* Neon Green/Cyan */
            --primary-color: #00bfff; /* Bright Blue */
            
            --text-light: #e0e0e0;
            --shadow-dark: 0 0 15px rgba(0, 255, 200, 0.4); /* Neon Glow Effect */
            --transition-speed: 0.6s;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden; 
            overflow-y: auto;
        }
        
        /* Message Styling */
        .message-box {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            z-index: 1000;
            box-sizing: border-box;
            color: #1a1a1a;
        }
        .message-box.error {
            background-color: #ffcccc; /* Soft red background */
            color: #721c24;
        }
        .message-box.success {
            background-color: #ccffcc; /* Soft green background */
            color: #155724;
        }

        /* --- DASHBOARD STYLES (If Logged In) --- */
        .dashboard-container {
            width: 90%;
            max-width: 800px;
            background: var(--card-bg);
            color: var(--text-light);
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-dark);
            text-align: center;
            margin: 20px;
            border: 1px solid rgba(0, 255, 200, 0.2);
        }
        .dashboard-container h1 { 
            color: var(--accent-color); 
            text-shadow: 0 0 5px rgba(0, 255, 200, 0.8);
            margin-bottom: 20px;
        }
        .dashboard-container p { 
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        .dashboard-container a { 
            color: var(--primary-color); 
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            border-radius: 50px;
            transition: background 0.3s, color 0.3s, box-shadow 0.3s;
        }
        .dashboard-container a:hover {
            background: var(--primary-color);
            color: var(--card-bg);
            box-shadow: 0 0 10px var(--primary-color);
        }


        /* --- AUTHENTICATION STYLES (If Logged Out) --- */
        .wrapper {
            position: relative;
            width: 100%;
            max-width: 800px;
            height: 500px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow-dark);
            border: 1px solid rgba(0, 255, 200, 0.2);
            display: flex;
            overflow: hidden;
            transition: transform var(--transition-speed) ease-in-out;
            margin: 20px;
        }
        .form-container {
            width: 50%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity var(--transition-speed), transform var(--transition-speed);
            position: absolute; 
            top: 0;
            height: 100%;
            box-sizing: border-box;
        }
        .login-form-container { left: 0; opacity: 1; z-index: 2; }
        .register-form-container { left: 50%; opacity: 0; z-index: 1; transform: translateX(50%); }
        .forgot-form-container { 
            width: 100%; 
            left: 0; 
            opacity: 0; 
            z-index: 4;
            background: var(--card-bg); 
            transform: translateY(-100%); 
            transition: opacity var(--transition-speed), transform var(--transition-speed);
            border-radius: 12px;
        }
        .panel {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            /* Dark background for the contrast panel */
            background: #000000; 
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            text-align: center;
            transition: left var(--transition-speed), transform var(--transition-speed);
            z-index: 3;
            box-sizing: border-box;
        }
        .panel h3, .panel p {
            color: var(--text-light);
        }
        .panel button {
            background: transparent; 
            border: 2px solid var(--accent-color); 
            color: var(--accent-color); 
            padding: 10px 20px; 
            border-radius: 50px; 
            cursor: pointer; 
            margin-top: 20px; 
            font-weight: 600; 
            transition: background 0.3s, box-shadow 0.3s;
        }
        .panel button:hover { 
            background: rgba(0, 255, 200, 0.1); 
            box-shadow: 0 0 10px var(--accent-color);
        }

        /* Active State: Sign Up */
        .wrapper.active .login-form-container { opacity: 0; transform: translateX(-100%); }
        .wrapper.active .register-form-container { opacity: 1; transform: translateX(-50%); }
        .wrapper.active .panel { left: 0; }
        
        /* Active State: Forgot */
        .wrapper.forgot-active .forgot-form-container { opacity: 1; transform: translateY(0); }
        .wrapper.forgot-active .login-form-container,
        .wrapper.forgot-active .register-form-container,
        .wrapper.forgot-active .panel { opacity: 0; pointer-events: none; }
        
        /* Form Inputs */
        form h2 { 
            color: var(--accent-color); 
            text-shadow: 0 0 5px rgba(0, 255, 200, 0.7);
            margin-bottom: 25px; 
        }
        .input-group { margin-bottom: 20px; position: relative; width: 100%; max-width: 300px; }
        .input-group input { 
            width: 100%; 
            padding: 12px 15px 12px 40px; 
            background-color: #2c2c2c; /* Dark input background */
            color: var(--text-light); /* Light text on dark input */
            border: 1px solid #444; 
            border-radius: 6px; 
            font-size: 1em; 
            box-sizing: border-box; 
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .input-group input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 8px rgba(0, 255, 200, 0.4);
            outline: none;
        }
        .input-group i { 
            position: absolute; 
            top: 50%; 
            left: 15px; 
            transform: translateY(-50%); 
            color: var(--accent-color); /* Neon icon color */
            text-shadow: 0 0 3px rgba(0, 255, 200, 0.5);
        }
        .btn-submit { 
            width: 100%; 
            max-width: 300px; 
            padding: 12px; 
            background-color: var(--accent-color); /* Neon button */
            color: var(--card-bg); /* Dark text on neon button */
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 1em; 
            font-weight: 900; /* Bold text */
            transition: background-color 0.3s, box-shadow 0.3s, transform 0.2s; 
            box-shadow: 0 0 10px rgba(0, 255, 200, 0.5);
        }
        .btn-submit:hover { 
            background-color: #00e6b8; 
            box-shadow: 0 0 15px var(--accent-color);
            transform: translateY(-2px);
        }
        .forgot-link { 
            font-size: 0.9em; 
            color: var(--primary-color); 
            text-decoration: none; 
            margin-top: 10px; 
            display: block;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--accent-color); }

        /* Responsive */
        @media (max-width: 768px) {
            .wrapper { 
                flex-direction: column; 
                height: auto; 
                min-height: 500px;
                max-width: 90%; 
            }
            .form-container { 
                width: 100%; 
                padding: 30px 20px; 
                position: static; 
                left: auto; 
                transform: none !important; 
                opacity: 1 !important; 
                z-index: 1; 
                display: flex;
            }
            .panel { 
                position: static; 
                width: 100%; 
                height: 200px; 
            }
            .panel button { display: none; }
            
            /* Show one form at a time on mobile */
            .wrapper.active .login-form-container { display: none; }
            .wrapper.active .register-form-container { display: flex; }
            .wrapper:not(.active) .register-form-container { display: none; }
            
            /* Forgot overlay takes full area */
            .wrapper.forgot-active .forgot-form-container { 
                position: absolute; 
                top: 0; 
                left: 0; 
                width: 100%; 
                height: 100%; 
                background: var(--card-bg); 
                display: flex !important; 
            }
            
            /* Ensure dashboard looks good on mobile */
            .dashboard-container {
                padding: 30px 15px;
            }
        }
    </style>
</head>
<body>

<?php 
// Display PHP success/error messages, or database connection error
if (!empty($db_error)) {
    echo "<div class='message-box error'>Database Error: $db_error</div>";
} elseif (!empty($error_message)) {
    echo "<div class='message-box error'>$error_message</div>";
} elseif (!empty($success_message)) {
    echo "<div class='message-box success'>$success_message</div>";
}
?>

<?php if (isset($_SESSION['user_id'])): ?>

    <!-- SECURE DASHBOARD CONTENT (If Logged In) -->
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>You are successfully authenticated. This is your secure dashboard.</p>
        <a href="home.php?action=logout">Log Out Securely</a>
    </div>

<?php else: ?>

    <!-- MODERN AUTHENTICATION FORMS (If Logged Out) -->
    <div class="wrapper <?php echo $wrapper_class; ?>">

        <!-- Login Form -->
        <div class="form-container login-form-container">
            <form method="POST" action="home.php">
                <h2><i class="fa-solid fa-lock"></i> SYSTEM LOGIN</h2>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="login_email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" name="login_password" placeholder="Password" required>
                </div>
                <input type="submit" name="login_submit" class="btn-submit" value="ACCESS">
                
                <!-- Link to trigger Forgot Password state -->
                <a href="home.php?action=forgot" class="forgot-link">Forgot Password?</a>
            </form>
        </div>

        <!-- Sign Up Form -->
        <div class="form-container register-form-container">
            <form method="POST" action="home.php">
                <h2><i class="fa-solid fa-user-plus"></i> REGISTER USER</h2>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="register_username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="register_email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="register_password" placeholder="Password (Min 6 chars)" required>
                </div>
                <input type="submit" name="register_submit" class="btn-submit" value="REGISTER">
            </form>
        </div>
        
        <!-- Forgot Password Form (Hidden/Overlaid) -->
        <div class="form-container forgot-form-container">
            <form method="POST" action="home.php">
                <h2><i class="fa-solid fa-question-circle"></i> RESET ACCESS</h2>
                <p style="text-align: center; margin-bottom: 20px; color: var(--text-light);">Enter your email to receive a reset link.</p>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="forgot_email" placeholder="Email Address" required>
                </div>
                <input type="submit" name="forgot_submit" class="btn-submit" value="SEND LINK">
                <!-- Link to go back to Login state -->
                <a href="home.php?action=login" class="forgot-link">Return to Login</a>
            </form>
        </div>

        <!-- Panel for Toggling Login/Sign Up -->
        <div class="panel">
            <div class="panel-content">
                <h3 id="panel-heading">SYSTEM ACCESS</h3>
                <p id="panel-text">Need an account? Register now and gain full access to the platform.</p>
                <button id="panel-toggle">Sign Up</button>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.querySelector('.wrapper');
            const panelToggle = document.getElementById('panel-toggle');
            const panelHeading = document.getElementById('panel-heading');
            const panelText = document.getElementById('panel-text');
            
            const updatePanel = (isRegisterActive) => {
                // Update panel content based on target state
                if (isRegisterActive) {
                    panelHeading.textContent = "WELCOME BACK";
                    panelText.textContent = "To keep connected, please login with your registered account.";
                    panelToggle.textContent = "Log In";
                    wrapper.classList.add('active');
                    wrapper.classList.remove('forgot-active');
                } else {
                    panelHeading.textContent = "SYSTEM ACCESS";
                    panelText.textContent = "Need an account? Register now and gain full access to the platform.";
                    panelToggle.textContent = "Sign Up";
                    wrapper.classList.remove('active');
                    wrapper.classList.remove('forgot-active');
                }
            };

            // Initialize state based on PHP output
            const initialAction = "<?php echo $current_action; ?>";
            if (initialAction === 'register') {
                 updatePanel(true);
            } else if (initialAction === 'forgot') {
                 wrapper.classList.add('forgot-active');
            } else {
                 updatePanel(false);
            }


            // Toggle button click handler (desktop only)
            if(panelToggle) {
                panelToggle.addEventListener('click', () => {
                    const isCurrentlyRegister = wrapper.classList.contains('active');
                    updatePanel(!isCurrentlyRegister);
                });
            }
            
            // Handle internal link clicks (e.g., "Forgot Password?", "Back to Login")
            document.querySelectorAll('a[href^="home.php?action="]').forEach(link => {
                // Ignore the logout link as it performs a server redirect
                if (!link.href.includes('logout')) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const url = new URL(link.href);
                        const targetAction = url.searchParams.get('action');

                        if (targetAction === 'forgot') {
                            wrapper.classList.add('forgot-active');
                            wrapper.classList.remove('active');
                        } else if (targetAction === 'login') {
                            updatePanel(false); // Switch to login form
                        } else if (targetAction === 'register') {
                            updatePanel(true); // Switch to register form
                        }
                    });
                }
            });
        });
    </script>

<?php endif; ?>

</body>
</html>