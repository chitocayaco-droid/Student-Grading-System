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
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Student Grading System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: white;
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
            gap: 10px;
        }
        .logo-icon {
            font-size: 28px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nav-links a {
            text-decoration: none;
            color: #4a5568;
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
            background: #edf2f7;
            color: #2d3748;
        }
        .nav-links a.active {
            background: #667eea;
            color: white;
        }
        .nav-links a.active:hover {
            background: #5a67d8;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: 10px;
            padding-left: 15px;
            border-left: 2px solid #e2e8f0;
        }
        .user-info {
            text-align: right;
        }
        .user-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }
        .user-role {
            font-size: 12px;
            color: #718096;
            text-transform: capitalize;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .user-avatar:hover {
            transform: scale(1.05);
        }
        .logout-btn {
            background: #f56565 !important;
            color: white !important;
        }
        .logout-btn:hover {
            background: #e53e3e !important;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-icon">📚</span>
                <span class="logo-text">GradeMaster</span>
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