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
    <title>Student Grading System - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #7d7ffc 0%, #38bdf8 50%, hsla(59, 100%, 71%, 0.73) 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
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
        .logo {
            margin-bottom: 20px;
        }
        .logo-img {
            max-height: 80px;
            width: auto;
            margin-bottom: 10px;
        }
        .logo-icon {
            font-size: 64px;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-top: 10px;
        }
        h2 {
            color: #4a5568;
            margin-bottom: 30px;
            font-size: 18px;
            font-weight: normal;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }
        .input-icon input {
            width: 100%;
            padding: 14px 14px 14px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .input-icon input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
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
            animation: shake 0.5s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Demo Credentials Dropdown */
        .demo-section {
            margin-top: 30px;
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
        }
        .demo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 10px;
            background: #f7fafc;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .demo-header:hover {
            background: #edf2f7;
        }
        .demo-header h3 {
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .demo-header .arrow {
            color: #a0aec0;
            transition: transform 0.3s;
        }
        .demo-header.active .arrow {
            transform: rotate(180deg);
        }
        .demo-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease;
            margin-top: 0;
        }
        .demo-content.show {
            max-height: 500px;
            margin-top: 15px;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
        }
        .demo-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .demo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .demo-card.admin:hover {
            background: linear-gradient(135deg, #fbb6ce 0%, #f687b3 100%);
            border-color: #97266d;
        }
        .demo-card.teacher:hover {
            background: linear-gradient(135deg, #bee3f8 0%, #90cdf4 100%);
            border-color: #2c5282;
        }
        .demo-card.student:hover {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            border-color: #22543d;
        }
        .demo-card .role-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .demo-card .role-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .demo-card .role-creds {
            font-size: 11px;
            color: #718096;
        }
        .demo-card .username {
            font-weight: 600;
            color: #2d3748;
            margin: 5px 0 2px;
        }
        .demo-card .password {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
            display: inline-block;
        }
        
        /* Quick Fill Button */
        .quick-fill {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #48bb78;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .quick-fill:hover {
            background: #38a169;
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #2d3748;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 11px;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/logo.png" alt="Bethel Logo" class="logo-img" onerror="this.style.display='none'; this.nextSibling.style.display='block';">
            <div class="logo-text" style="display: none;">Bethel Grading System</div>
            <div class="logo-text-fallback" style="margin-top: 10px;">
                <span style="font-size: 32px; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Bethel Grading System</span>
            </div>
        </div>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label>Username</label>
                <div class="input-icon">
                    <i>👤</i>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <i>🔑</i>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" id="loginBtn">
                <span>🔓</span>
                Login
            </button>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </form>
        
        <!-- Demo Credentials Dropdown -->
        <div class="demo-section">
            <div class="demo-header" onclick="toggleDemo()" id="demoHeader">
                <h3>
                    <span>🔐</span>
                    Demo Credentials
                </h3>
                <span class="arrow" id="demoArrow">▼</span>
            </div>
            <div class="demo-content" id="demoContent">
                <p style="color: #718096; font-size: 12px; margin-bottom: 10px; text-align: left;">
                    Click on any card to auto-fill credentials:
                </p>
                <div class="demo-grid">
                    <!-- Admin Card -->
                    <div class="demo-card admin" onclick="fillCredentials('admin', 'admin123')">
                        <div class="role-icon">👑</div>
                        <div class="role-name">Admin</div>
                        <div class="username">admin</div>
                        <div class="password">admin123</div>
                    </div>
                    
                    <!-- Teacher Card -->
                    <div class="demo-card teacher" onclick="fillCredentials('teacher1', 'teacher123')">
                        <div class="role-icon">👨‍🏫</div>
                        <div class="role-name">Teacher</div>
                        <div class="username">teacher1</div>
                        <div class="password">teacher123</div>
                    </div>
                    
                    <!-- Student Card -->
                    <div class="demo-card student" onclick="fillCredentials('student1', 'student123')">
                        <div class="role-icon">👨‍🎓</div>
                        <div class="role-name">Student</div>
                        <div class="username">student1</div>
                        <div class="password">student123</div>
                    </div>
                    
                    <!-- Additional Student Card -->
                    <div class="demo-card student" onclick="fillCredentials('alice.johnson', 'student123')">
                        <div class="role-icon">👩‍🎓</div>
                        <div class="role-name">Student 2</div>
                        <div class="username">alice.johnson</div>
                        <div class="password">student123</div>
                    </div>
                </div>
                
                <!-- Quick Fill All Button -->
                <button class="quick-fill" onclick="fillRandomDemo()">
                    🎲 Random Demo Account
                </button>
                
                <p style="color: #a0aec0; font-size: 11px; margin-top: 15px; text-align: left;">
                    <span>ℹ️</span> These are demo accounts. In production, change default passwords.
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle demo credentials dropdown
        function toggleDemo() {
            const content = document.getElementById('demoContent');
            const header = document.getElementById('demoHeader');
            const arrow = document.getElementById('demoArrow');
            
            content.classList.toggle('show');
            header.classList.toggle('active');
            
            if (content.classList.contains('show')) {
                arrow.style.transform = 'rotate(180deg)';
            } else {
                arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Fill credentials function
        function fillCredentials(username, password) {
            // Add animation to show filling
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            
            // Clear fields with animation
            usernameField.style.transition = 'background-color 0.3s';
            passwordField.style.transition = 'background-color 0.3s';
            
            usernameField.style.backgroundColor = '#fefcbf';
            passwordField.style.backgroundColor = '#fefcbf';
            
            setTimeout(() => {
                usernameField.value = username;
                passwordField.value = password;
                
                usernameField.style.backgroundColor = '#fff';
                passwordField.style.backgroundColor = '#fff';
            }, 200);
            
            // Optional: Auto submit after fill
            // setTimeout(() => {
            //     document.getElementById('loginForm').submit();
            // }, 500);
        }
        
        // Fill random demo account
        function fillRandomDemo() {
            const demos = [
                { username: 'admin', password: 'admin123' },
                { username: 'teacher1', password: 'teacher123' },
                { username: 'student1', password: 'student123' },
                { username: 'alice.johnson', password: 'student123' }
            ];
            
            const random = demos[Math.floor(Math.random() * demos.length)];
            fillCredentials(random.username, random.password);
        }
        
        // Add loading animation on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<span class="loading"></span> Logging in...';
            btn.disabled = true;
        });
        
        // Auto-fill with keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + 1 for Admin
            if (e.altKey && e.key === '1') {
                e.preventDefault();
                fillCredentials('admin', 'admin123');
            }
            // Alt + 2 for Teacher
            if (e.altKey && e.key === '2') {
                e.preventDefault();
                fillCredentials('teacher1', 'teacher123');
            }
            // Alt + 3 for Student
            if (e.altKey && e.key === '3') {
                e.preventDefault();
                fillCredentials('student1', 'student123');
            }
        });
        
        // Open dropdown by default on first visit (optional)
        // setTimeout(() => {
        //     if (!sessionStorage.getItem('demoShown')) {
        //         toggleDemo();
        //         sessionStorage.setItem('demoShown', 'true');
        //     }
        // }, 1000);
    </script>
</body>
</html>
