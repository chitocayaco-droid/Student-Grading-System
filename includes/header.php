<?php
// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'guest';
$full_name = $_SESSION['full_name'] ?? 'User';

// Function to check if link is active
function isActive($page) {
    global $current_page;
    return $current_page == $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Bethel School Grading System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: #345dce;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo img {
            height: 45px;
            width: auto;
        }
        .logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #fbbf24;
            letter-spacing: 0.1px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nav-links a {
            text-decoration: none;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-links a:hover {
            background: #ffe56fa4;
            color: white;
        }
        .nav-links a.active {
            background: #fbbf24;
            color: #232427;
        }
        .nav-links a.active:hover {
            background: #f59e0b;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: 10px;
            padding-left: 15px;
            border-left: 2px solid #3b82f6;
        }
        .user-info {
            text-align: right;
        }
        .user-name {
            font-weight: 600;
            color: #fbbf24;
            font-size: 14px;
        }
        .user-role {
            font-size: 12px;
            color: #94a3b8;
            text-transform: capitalize;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fbbf24;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e3a8a;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .user-avatar:hover {
            transform: scale(1.05);
            background: #f59e0b;
        }
        .logout-btn {
            background: #dc2626 !important;
            color: white !important;
        }
        .logout-btn:hover {
            background: #b91c1c !important;
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
        }
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 10px;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            .user-menu {
                border-left: none;
                padding-left: 0;
                margin-left: 0;
            }
            .logo img {
                height: 35px;
            }
            .logo-text {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <div class="logo">
                <?php 
                // Check for the new transparent PNG logo
                if (file_exists('assets/betterbethel.png')) {
                    echo '<img src="assets/betterbethel.png" alt="Bethel School">';
                } elseif (file_exists('betterbethel.png')) {
                    echo '<img src="betterbethel.png" alt="Bethel School">';
                } elseif (file_exists('assets/bethel.png')) {
                    echo '<img src="assets/bethel.png" alt="Bethel School">';
                } elseif (file_exists('assets/bethel.jpg')) {
                    echo '<img src="assets/bethel.jpg" alt="Bethel School">';
                } else {
                    // Fallback icon if no logo found
                    echo '<span style="font-size: 32px;">🏫</span>';
                }
                ?>
                <span class="logo-text">Bethel GradeMaster</span>
            </div>
            
            <div class="nav-links">
                <!-- Dashboard Link - Common for all -->
                <a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
                    <span>📊</span> Dashboard
                </a>
                
                <?php if ($role == 'admin'): ?>
                    <!-- Admin Links -->
                    <a href="manage_students.php" class="<?php echo isActive('manage_students.php'); ?>">
                        <span>👥</span> Students
                    </a>
                    <a href="manage_teachers.php" class="<?php echo isActive('manage_teachers.php'); ?>">
                        <span>👨‍🏫</span> Teachers
                    </a>
                    <a href="manage_courses.php" class="<?php echo isActive('manage_courses.php'); ?>">
                        <span>📚</span> Courses
                    </a>
                    <a href="manage_passwords.php" class="<?php echo isActive('manage_passwords.php'); ?>">
                        <span>🔐</span> Passwords
                    </a>
                    
                <?php elseif ($role == 'teacher'): ?>
                    <!-- Teacher Links -->
                    <a href="my_courses.php" class="<?php echo isActive('my_courses.php'); ?>">
                        <span>📖</span> My Courses
                    </a>
                    <a href="grade_students.php" class="<?php echo isActive('grade_students.php'); ?>">
                        <span>📝</span> Grade Students
                    </a>
                    
                <?php elseif ($role == 'student'): ?>
                    <!-- Student Links -->
                    <a href="my_grades.php" class="<?php echo isActive('my_grades.php'); ?>">
                        <span>📊</span> My Grades
                    </a>
                <?php endif; ?>
                
                <!-- Profile Link - Common for all -->
                <a href="view_profile.php" class="<?php echo isActive('view_profile.php'); ?>">
                    <span>👤</span> Profile
                </a>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($full_name); ?></div>
                    <div class="user-role"><?php echo ucfirst($role); ?></div>
                </div>
                <div class="user-avatar" onclick="window.location.href='view_profile.php'">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
                <a href="logout.php" class="logout-btn" style="padding: 8px 16px; text-decoration: none; border-radius: 8px;">🚪 Logout</a>
            </div>
        </div>
    </div>