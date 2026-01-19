<?php
// --- 1. DATABASE CONFIGURATION ---
// IMPORTANT: You must have a MySQL database named 'shelema' and a table named 'GO' 
// with columns: name, username, password, message, date, gender, date of birth
$servername = "localhost";
$username_db = "root";       // Default XAMPP username
$password_db = "";           // Default XAMPP password (usually empty)
$dbname = "shelema";         // REPLACE THIS with your actual database name
$tablename = "GO";           // REPLACE THIS with your table name

// Variables to store feedback messages
$statusMsg = "";
$statusType = "";

// --- 2. HANDLE FORM SUBMISSION (Securely using Prepared Statements) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $required_fields = [
        'name' => 'Name', 
        '_replyto' => 'Email', 
        'message' => 'Message'
    ];
    $missing_fields = [];

    // Check all required fields for existence and non-emptiness (after trimming whitespace)
    foreach ($required_fields as $post_key => $friendly_name) {
        if (!isset($_POST[$post_key]) || trim($_POST[$post_key]) === '') {
            $missing_fields[] = $friendly_name;
        }
    }

    if (empty($missing_fields)) {
        // All fields are present and not empty, proceed with database insertion
        $name = $_POST['name'];
        $email_as_username = $_POST['_replyto']; // Using Email as 'username'
        $message = $_POST['message'];
        
        // --- 3. FILLING MISSING DATABASE FIELDS (Default Values for DB integrity) ---
        $default_pass = "NoPassword"; 
        $current_date = date("Y-m-d"); // Current date for 'date' column
        $default_gender = "Not Specified";
        $default_dob = "2000-01-01"; // Dummy date for 'date of birth'

        // Create connection
        $conn = new mysqli($servername, $username_db, $password_db, $dbname);

        // Check connection
        if ($conn->connect_error) {
            $statusMsg = "Connection failed: " . $conn->connect_error;
            $statusType = "error";
        } else {
            // SQL Insert Query (using placeholders '?' instead of variables)
            $sql = "INSERT INTO $tablename (name, username, password, message, date, gender, `date of birth`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            // Prepare the statement
            $stmt = $conn->prepare($sql);
            
            // Bind parameters: 'sssssss' means 7 strings (s) are being passed.
            $stmt->bind_param("sssssss", $name, $email_as_username, $default_pass, $message, $current_date, $default_gender, $default_dob);

            // Execute the statement
            if ($stmt->execute()) {
                $statusMsg = "Success! Your message has been safely saved to the database.";
                $statusType = "success";
            } else {
                // Report specific error from the statement
                $statusMsg = "Database Error executing statement: " . $stmt->error; 
                $statusType = "error";
            }

            // Close statement and connection
            $stmt->close();
            $conn->close();
        }
    } else {
        // Fields are missing or empty. Suppressing the specific error message as requested by the user.
        $statusMsg = ""; 
        $statusType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shelema Tolesa - Personal Portfolio</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* Modern Reset & Font */
        :root {
            --bg-color: #121212;
            --card-color: #1e1e1e;
            --text-color: #e0e0e0;
            --primary-color: #00bfa5;
            --primary-hover: #00a088;
            --border-color: #333;
            --error-color: #d9534f;
            --success-color: #5cb85c;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.7;
            overflow-x: hidden; 
        }

        /* --- Global Styles --- */
        h1, h2 { color: var(--primary-color); margin-bottom: 1.5rem; font-size: 2.5rem; }
        section { padding: 80px 0; }

        /* --- Menu Toggle Button --- */
        .menu-toggle {
            position: fixed; top: 1.5rem; left: 1.5rem; z-index: 1001; 
            background: var(--primary-color); color: var(--bg-color);
            border: none; padding: 0.75rem 1rem; border-radius: 5px;
            cursor: pointer; font-size: 1.2rem; transition: transform 0.3s ease;
        }

        /* --- Navigation --- */
        nav {
            position: fixed; top: 0; left: 0; width: 300px; height: 100vh;
            background: var(--card-color); padding: 5rem 1.5rem 1.5rem; z-index: 1000;
            transform: translateX(-100%); transition: transform 0.4s cubic-bezier(0.7, 0, 0.3, 1);
            box-shadow: 2px 0 10px rgba(151, 145, 145, 0.5);
        }
        nav.nav-open { transform: translateX(0); }
        nav .logo { font-size: 1.8rem; font-weight: 700; color: var(--primary-color); text-decoration: none; display: block; margin-bottom: 2rem; padding-left: 1rem; }
        nav ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }
        nav ul li a { display: flex; align-items: center; text-decoration: none; color: var(--text-color); font-weight: 500; font-size: 1.1rem; padding: 1rem; border-radius: 5px; transition: background 0.3s ease, color 0.3s ease; }
        nav ul li a i { width: 20px; margin-right: 1rem; color: var(--primary-color); font-size: 1.1rem; }
        nav ul li a:hover { background-color: rgba(255, 255, 255, 0.05); color: var(--primary-color); }
        
        /* Main Content Layout */
        main { max-width: 1000px; margin: 0 auto; padding: 4rem 2rem 0; }
        
        /* Hero/About Section */
        #about {
            /* Using the user's uploaded image for the background */
            background-image: url('uploaded:photo_2025-11-25_16-18-34.jpg-e2630d31-5c08-4ad4-aaff-c5a15d7d66a9');
            background-size: cover; background-position: center; background-attachment: fixed;
            position: relative; min-height: 90vh; display: flex; flex-direction: column; justify-content: center;
            /* Overlay to darken image and keep text readable */
            box-shadow: inset 0 0 0 100vw rgba(18, 18, 18, 0.7);
            padding: 2rem; /* Ensure padding on mobile */
        }
        
        /* --- Portfolio Grid --- */
        .portfolio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .portfolio-item { 
            background-color: var(--card-color); 
            padding: 1.5rem; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); 
            transition: transform 0.3s ease; 
        }
        .portfolio-item:hover { transform: translateY(-5px); }
        
        /* Custom Reshaping CSS for Images */
        .portfolio-item img { 
            width: 100%; 
            height: 200px; /* Fixed height for uniform card size */
            object-fit: cover; /* Ensures image covers the space without distortion */
            border-radius: 4px; 
            margin-bottom: 1rem; 
        }

        .portfolio-item h3 { color: var(--primary-color); margin-bottom: 0.5rem; font-size: 1.5rem; }

        /* --- Contact Form Styling --- */
        #contact-form div { margin-bottom: 1.5rem; }
        #contact-form label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        #contact-form input[type="text"], #contact-form input[type="email"], #contact-form textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border-color);
            border-radius: 4px; background-color: var(--card-color); color: var(--text-color);
            font-size: 1rem; transition: border-color 0.3s;
        }
        #contact-form input:focus, #contact-form textarea:focus { border-color: var(--primary-color); outline: none; }
        #contact-form button {
            display: inline-block; padding: 12px 25px; background-color: var(--primary-color);
            color: var(--bg-color); border: none; border-radius: 4px; cursor: pointer;
            font-size: 1rem; font-weight: 600; transition: background-color 0.3s ease;
        }
        #contact-form button:hover { background-color: var(--primary-hover); }
        
        /* PHP Feedback Messages */
        .php-message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 5px; font-weight: 500; }
        .php-message.success { background-color: var(--success-color); color: var(--bg-color); }
        .php-message.error { background-color: var(--error-color); color: var(--text-color); }
        
        /* --- FOOTER STYLES --- */
        footer {
            background-color: var(--card-color);
            padding: 3rem 2rem; 
            border-top: 1px solid var(--border-color);
            color: #888;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            text-align: left;
        }

        .footer-section h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .footer-section p {
            font-size: 0.9rem;
            color: #aaa;
        }

        /* Quick Links Styling */
        .footer-section.links ul {
            list-style: none;
            padding: 0;
        }
        .footer-section.links ul li a {
            color: #aaa;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .footer-section.links ul li a:hover {
            color: var(--primary-color);
        }

        /* Contact & Social Links Styling */
        .contact-info span {
            display: block;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        .contact-info i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .social-links {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
        }
        .social-links a {
            color: #aaa;
            font-size: 1.5rem;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .social-links a:hover {
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Newsletter/Form Styling */
        .newsletter p {
            margin-bottom: 1rem;
        }
        .newsletter form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .newsletter input[type="email"] {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
        }
        .newsletter button {
            padding: 10px;
            background-color: var(--primary-color);
            color: var(--bg-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .newsletter button:hover {
            background-color: var(--primary-hover);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            margin-top: 2rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Animation */
        .animated-section { opacity: 0; transform: translateY(40px); transition: opacity 0.8s ease-out, transform 0.8s ease-out; }
        .animated-section.is-visible { opacity: 1; transform: translateY(0); }

        /* Responsive */
        @media (max-width: 768px) {
            nav { width: 80%; }
            .menu-toggle { top: 1rem; left: 1rem; }
            h1 { font-size: 2rem; } h2 { font-size: 2rem; }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            .social-links {
                justify-content: center; /* Center social links on mobile */
            }
            .footer-section.links ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0 1rem;
            }
            .contact-info span {
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menuToggle">
        <i class="fa-solid fa-bars"></i>
    </button>

    <nav id="sideNav">
        <a href="#about" class="logo">Shelema</a>
        <ul>
            <li><a href="#about"><i class="fa-solid fa-user-circle"></i><span>Profile</span></a></li>
            <li><a href="#about"><i class="fa-solid fa-info-circle"></i><span>About</span></a></li>
            <li><a href="#portfolio"><i class="fa-solid fa-briefcase"></i><span>Portfolio</span></a></li>
            <li><a href="#contact"><i class="fa-solid fa-envelope"></i><span>Contact</span></a></li>
            <hr style="border-color: #333; margin: 1rem 0;">
            <li><a href="#"><i class="fa-solid fa-cog"></i><span>Setting</span></a></li>
            <li><a href="#"><i class="fa-solid fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </nav>

    <main>
        <section id="about" class="animated-section">
        <h2 style= "font-style:italic; color:white;"> Ashama bagaa naagan dhuftaan!</h2>
            <h1>Hi, I'm Shelema Tolesa.</h1>
            <p>I am a passionate web developer who loves building modern, responsive, and dynamic websites. I specialize in the full stack, from pixel-perfect frontends to robust server-side logic.</p>
        </section>

        <section id="portfolio" class="animated-section">
            <h2>My Work</h2>
            <div class="portfolio-grid">
                
                <div class="portfolio-item">
                    <!-- Image path updated to uploaded:frontend.jpg -->
                    <img src="frontend.png" alt="Frontend Project">
                    <h3>Frontend</h3>
                    <p>Building responsive and interactive user interfaces using modern CSS and JS.</p>
                </div>
                
                <div class="portfolio-item">
                    <!-- Image path updated to uploaded:back.jpg -->
                    <img src="back.png" alt="Backend Project">
                    <h3>Backend</h3>
                    <p>Creating robust server logic and APIs to power applications.</p>
                </div>
                
                <div class="portfolio-item">
                    <!-- Image path updated to uploaded:dbms.jpg -->
                    <img src="dbms.png" alt="DBMS Project">
                    <h3>DBMS</h3>
                    <p>Designing efficient database schemas and managing data flow.</p>
                </div>
                
                <div class="portfolio-item">
                    <!-- Image path updated to uploaded:net.jpg -->
                    <img src="net.png" alt="Cybersecurity and Network Project">
                    <h3>Cybersecurity and Network</h3>
                    <p>Implementing network infrastructure, monitoring security protocols, and protecting digital assets.</p>
                </div>
                
            </div>
        </section>

        <section id="contact" class="animated-section">
            <h2>Get In Touch</h2>
            <p style="margin-bottom: 2rem;">Have a question? It will be saved directly to my database!</p>

            <?php if (!empty($statusMsg)): ?>
                <div class="php-message <?php echo $statusType; ?>">
                    <?php echo $statusMsg; ?>
                </div>
            <?php endif; ?>

            <form id="contact-form" action="" method="POST">
                <div>
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="_replyto" required> 
                </div>
                <div>
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="6" required></textarea>
                </div>
                <button type="submit">Send Message</button>
            </form>
        </section>

    </main>
    
    <!-- Enhanced Footer outside <main> tag -->
    <footer>
        <div class="footer-content">
            <!-- 1. About/Logo Section -->
            <div class="footer-section about">
                <h4>Shelema Tolesa</h4>
                <p>Building responsive and scalable web solutions using modern technologies. Passionate about development and clean code practices.</p>
            </div>

            <!-- 2. Quick Links Section -->
            <div class="footer-section links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#about">Profile</a></li>
                    <li><a href="#portfolio">Portfolio</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#">Services</a></li>
                </ul>
            </div>

            <!-- 3. Contact Info & Socials -->
            <div class="footer-section contact">
                <h4>Contact</h4>
                <div class="contact-info">
                    <span><i class="fa-solid fa-map-marker-alt"></i>kuyu, Ethiopia</span>
                    <span><i class="fa-solid fa-envelope"></i>shelematolesa43@gmail.com</span>
                    <span><i class="fa-solid fa-map-marker-alt"></i>Telegram @Shelematoli</span>
                     <span><i class="fa-solid fa-map-marker-alt"></i>https://www.facebook.com/profile.php?id=61566512234475</span>
                </div>
                
                <div class="social-links">
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <!-- 4. Newsletter/Subscription Form (Interpreting "Search/Add Info") -->
            <div class="footer-section newsletter">
                <h4>Stay Updated</h4>
                <p>Get the latest news and portfolio updates directly to your inbox.</p>
                <form action="#" method="POST">
                    <input type="email" placeholder="Your Email Address" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div> 
       

     <div class="footer-bottom" sty>
            <p style= " font-style:italic;color:white;">May God bless you!.</p>
        </div>
  <div class="footer-bottom">
            <p style= " font-style:italic;color:white;">Designed and Built by Shelema Tolesa | &copy; 2025. All rights reserved.</p>
        </div>
    </footer>
      
     

<script>
    document.addEventListener('DOMContentLoaded', () => {
        
        const menuToggle = document.getElementById('menuToggle');
        const sideNav = document.getElementById('sideNav');
        const navLinks = sideNav.querySelectorAll('a[href^="#"]'); 
        
        // --- Toggle Menu ---
        menuToggle.addEventListener('click', () => {
            sideNav.classList.toggle('nav-open');
            const icon = menuToggle.querySelector('i');
            if (sideNav.classList.contains('nav-open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }
        });

        // --- Close Menu on Link Click ---
        // This is necessary for a good mobile user experience.
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                sideNav.classList.remove('nav-open');
                const icon = menuToggle.querySelector('i');
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            });
        });

        // --- Scroll Animation (Intersection Observer) ---
        const animatedSections = document.querySelectorAll('.animated-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 }); // Trigger when 10% of the element is visible
        animatedSections.forEach(section => observer.observe(section));
    });
</script>

</body>
</html>