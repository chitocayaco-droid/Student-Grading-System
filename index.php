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
            height: 100vh;
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
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #eee89d 0%, #fbbf24 100%);
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
        .quick-fill {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #fbbf24;
            color: #1e3a8a;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 600;
        }
        .quick-fill:hover {
            background: #f59e0b;
            color: white;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1e3a8a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
</body>
</html>
