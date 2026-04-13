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
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Bethel Grading System</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(135deg, #002366 0%, #0056b3 100%);
            padding: 15px 0;
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
            flex-wrap: wrap;
            gap: 15px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo-img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: white;
        }
        .logo-text span {
            color: #fde047;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: rgba(255, 255, 255, 0.9);
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
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        .nav-links a.active {
            background: #fde047;
            color: #0284c7;
        }
        .nav-links a.active:hover {
            background: #fef08a;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: 10px;
            padding-left: 15px;
            border-left: 2px solid rgba(255, 255, 255, 0.2);
        }
        .user-info {
            text-align: right;
        }
        .user-name {
            font-weight: 600;
            color: white;
            font-size: 14px;
        }
        .user-role {
            font-size: 12px;
            color: #fef08a;
            text-transform: capitalize;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fde047;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .user-avatar:hover {
            transform: scale(1.05);
        }
        .logout-btn {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }
        .logout-btn:hover {
            background: #ef4444 !important;
            color: white !important;
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
            }
            .nav-links {
                justify-content: center;
            }
            .user-menu {
                border-left: none;
                padding-left: 0;
                margin-left: 0;
            }
            .logo-img {
                height: 35px;
            }
            .logo-text {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">
                <img src="assets/logo.png" alt="Bethel Logo" class="logo-img" 
                     onerror="this.style.display='none';">
                <div class="logo-text">
                    Bethel <span>Grading System</span>
                </div>
            </a>
            
            <div class="nav-links">
                <a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
                    <span>📊</span> Dashboard
                </a>
                
                <?php if ($role == 'admin'): ?>
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
                    <a href="my_courses.php" class="<?php echo isActive('my_courses.php'); ?>">
                        <span>📖</span> My Courses
                    </a>
                    <a href="grade_students.php" class="<?php echo isActive('grade_students.php'); ?>">
                        <span>📝</span> Grade Students
                    </a>
                    
                <?php elseif ($role == 'student'): ?>
                    <a href="my_grades.php" class="<?php echo isActive('my_grades.php'); ?>">
                        <span>📊</span> My Grades
                    </a>
                <?php endif; ?>
                
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