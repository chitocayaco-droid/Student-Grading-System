<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bethel School - Grading System Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 450px;
            max-width: 100%;
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .logo-section {
            margin-bottom: 25px;
        }
        .logo-image {
            margin-bottom: 15px;
        }
        .logo-image img {
            max-width: 180px;
            max-height: 100px;
            width: auto;
            height: auto;
            display: inline-block;
        }
        .school-name {
            font-size: 28px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .est-year {
            font-size: 14px;
            color: #fbbf24;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .logo-text {
            font-size: 35px;
            font-weight: bold;
            background: linear-gradient(135deg, #f1cf5e 0%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .system-tagline {
            font-size: 14px;
            color: #6b7280;
            margin-top: 5px;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }
        .input-icon {
            position: relative;
        }
        .input-icon span {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 18px;
        }
        .input-icon input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .input-icon input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 58, 138, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .error {
            color: #e53e3e;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            padding: 10px;
            background: #fed7d7;
            border-radius: 8px;
        }
        
        /* Help Link */
        .help-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .help-link a {
            color: #2563eb;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            transition: color 0.3s;
        }
        .help-link a:hover {
            color: #1e3a8a;
            text-decoration: underline;
        } 
        /* Modal Popup */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 400px;
            border-radius: 15px;
            position: relative;
            animation: slideUp 0.3s ease;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .close-modal {
            font-size: 28px;
            cursor: pointer;
            transition: opacity 0.3s;
            line-height: 1;
        }
        .close-modal:hover {
            opacity: 0.7;
        }
        .modal-body {
            padding: 50px;
        }
        .contact-info {
            background: #f7fafc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .contact-item:last-child {
            border-bottom: none;
        }
        .contact-icon {
            width: 40px;
            height: 40px;
            background: #e0f2fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .contact-details {
            flex: 1;
        }
        .contact-label {
            font-size: 11px;
            color: #718096;
        }
        .contact-value {
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
        }
        .contact-value a {
            color: #2563eb;
            text-decoration: none;
        }
        .tips-box {
            background: #fffbeb;
            border-left: 3px solid #fbbf24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .tips-box h4 {
            font-size: 13px;
            margin-bottom: 8px;
            color: #2d3748;
        }
        .tips-box ul {
            margin-left: 20px;
            font-size: 12px;
            color: #718096;
        }
        .tips-box li {
            margin: 5px 0;
        }
        .btn-help {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }
        .btn-help:hover {
            background: #1e3a8a;
        }
        .demo-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .demo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .demo-item:hover {
            background: #e0f2fe;
            transform: translateX(5px);
        }
        .demo-role {
            font-weight: 600;
            font-size: 14px;
        }
        .demo-creds {
            font-family: monospace;
            font-size: 12px;
            color: #718096;
        }
        .demo-creds span {
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }
        .success-message {
            background: #c6f6d5;
            color: #22543d;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #e2e8f0;
        }
        .footer {
            padding: 5px;
            margin-top: 20px;
            text-align: center;
            color: #0000008a;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-image">
                <?php 
                // Check for PNG logo first
                if (file_exists('assets/bethel.png')) {
                    echo '<img src="assets/bethel.png" alt="Bethel School Logo">';
                } elseif (file_exists('bethel.png')) {
                    echo '<img src="bethel.png" alt="Bethel School Logo">';
                } elseif (file_exists('assets/bethel.jpg')) {
                    echo '<img src="assets/bethel.jpg" alt="Bethel School Logo">';
                } else {
                    // Fallback text
                    echo '<div class="school-name">BETHEL SCHOOL</div>';
                    echo '<div class="est-year">Est. 2001</div>';
                }
                ?>
            </div>
            <div class="logo-text">Bethel GradeMaster</div>
            <div class="system-tagline">Student Grading System</div>
        </div>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label>Username</label>
                <div class="input-icon">
                    <span>👤</span>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <span>🔑</span>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" id="loginBtn">
                <span>🔓</span>
                Login
            </button>
            <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        </form>
        
        <!-- Help Link - Small text link -->
        <div class="help-link">
            <a onclick="openHelpModal()">❓ Forgot username or password? Contact administrator</a>
        </div>

        <div class="footer">
            <div>© <?php echo date('Y'); ?> Bethel International School - GradeMaster.<br>All rights reserved. Version 2.0</div>
        </div>
    </div>
    
    <!-- Help Modal Popup -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <span>❓</span>
                    Need Help?
                </h3>
                <span class="close-modal" onclick="closeHelpModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">👨‍💻</div>
                        <div class="contact-details">
                            <div class="contact-label">System Administrator</div>
                            <div class="contact-value">admin@bethel.edu</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <div class="contact-details">
                            <div class="contact-label">Phone</div>
                            <div class="contact-value">(123) 456-7890</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">🏫</div>
                        <div class="contact-details">
                            <div class="contact-label">Registrar's Office</div>
                            <div class="contact-value">registrar@bethel.edu</div>
                        </div>
                    </div>
                </div>
                
                <div class="tips-box">
                    <h4>💡 For Your Information</h4>
                    <ul>
                        <li>If you've forgotten your username or password, please contact the system administrator for assistance. They can help reset your credentials.</li>
                        <li>Student and teacher accounts are created by the administrator. If you need access, please reach out by making a request below.</li>
                    </ul>
                </div>
                
                <button class="btn-help" onclick="sendHelpRequest()">
                    📧 Send Help Request
                </button>
            </div>
        </div>
    </div>
    
    <!-- Send Request Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <span>📧</span>
                    Send Help Request
                </h3>
                <span class="close-modal" onclick="closeRequestModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="contactForm" onsubmit="return submitHelpRequest(event)">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" id="help_name" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label>Your Email</label>
                        <input type="email" id="help_email" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea id="help_message" rows="4" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit;"></textarea>
                    </div>
                    <button type="submit" style="margin-top: 10px;">Send Request</button>
                </form>
                <div id="requestSuccess" style="display: none;" class="success-message"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openHelpModal() {
            document.getElementById('helpModal').style.display = 'flex';
        }
        
        function closeHelpModal() {
            document.getElementById('helpModal').style.display = 'none';
        }
        
        function openDemoModal() {
            document.getElementById('demoModal').style.display = 'flex';
        }
        
        function closeDemoModal() {
            document.getElementById('demoModal').style.display = 'none';
        }
        
        function openRequestModal() {
            closeHelpModal();
            document.getElementById('requestModal').style.display = 'flex';
        }
        
        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
            document.getElementById('contactForm').reset();
            document.getElementById('contactForm').style.display = 'block';
            document.getElementById('requestSuccess').style.display = 'none';
        }
        
        // Fill credentials function
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            closeDemoModal();
            
            // Highlight the fields briefly
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            usernameField.style.backgroundColor = '#fefcbf';
            passwordField.style.backgroundColor = '#fefcbf';
            
            setTimeout(() => {
                usernameField.style.backgroundColor = '#fff';
                passwordField.style.backgroundColor = '#fff';
            }, 500);
        }
        
        // Send help request
        function sendHelpRequest() {
            openRequestModal();
        }
        
        function submitHelpRequest(event) {
            event.preventDefault();
            
            const name = document.getElementById('help_name').value;
            const email = document.getElementById('help_email').value;
            const message = document.getElementById('help_message').value;
            const submitBtn = document.querySelector('#requestModal button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '⏳ Sending...';
            submitBtn.disabled = true;
            
            // Send AJAX request
            fetch('save_help_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'name=' + encodeURIComponent(name) + 
                    '&email=' + encodeURIComponent(email) + 
                    '&message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    // Show success message
                    document.getElementById('contactForm').style.display = 'none';
                    const successDiv = document.getElementById('requestSuccess');
                    successDiv.innerHTML = '✅ ' + data.message;
                    successDiv.style.display = 'block';
                    
                    // Reset form
                    document.getElementById('contactForm').reset();
                    
                    // Auto close after 3 seconds
                    setTimeout(() => {
                        closeRequestModal();
                        document.getElementById('contactForm').style.display = 'block';
                        document.getElementById('requestSuccess').style.display = 'none';
                    }, 3000);
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
            
            return false;
        }
        
        // Add loading animation on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '⏳ Logging in...';
            btn.disabled = true;
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const helpModal = document.getElementById('helpModal');
            const demoModal = document.getElementById('demoModal');
            const requestModal = document.getElementById('requestModal');
            
            if (event.target == helpModal) closeHelpModal();
            if (event.target == demoModal) closeDemoModal();
            if (event.target == requestModal) closeRequestModal();
        }
    </script>
</body>
</html>