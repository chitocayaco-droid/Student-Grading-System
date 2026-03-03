<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Grading System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7fafc;
        }
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #4a5568;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: #edf2f7;
        }
        .logout {
            color: #e53e3e !important;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .welcome-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin-bottom: 15px;
            color: #2d3748;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <h2>Student Grading System</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <?php if ($role == 'admin'): ?>
                    <a href="manage_students.php">Students</a>
                    <a href="manage_teachers.php">Teachers</a>
                    <a href="manage_courses.php">Courses</a>
                <?php elseif ($role == 'teacher'): ?>
                    <a href="my_courses.php">My Courses</a>
                    <a href="grade_students.php">Grade Students</a>
                <?php elseif ($role == 'student'): ?>
                    <a href="my_grades.php">My Grades</a>
                <?php endif; ?>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-box">
            <h1>Welcome, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p>You are logged in as <strong><?php echo ucfirst($role); ?></strong></p>
        </div>
        
        <div class="dashboard-grid">
            <?php if ($role == 'admin'): ?>
                <div class="card">
                    <h3>Manage Students</h3>
                    <p>Add, edit, or remove student records</p>
                    <a href="manage_students.php" class="btn">Go to Students</a>
                </div>
                <div class="card">
                    <h3>Manage Teachers</h3>
                    <p>Add, edit, or remove teacher records</p>
                    <a href="manage_teachers.php" class="btn">Go to Teachers</a>
                </div>
                <div class="card">
                    <h3>Manage Courses</h3>
                    <p>Add, edit, or remove courses</p>
                    <a href="manage_courses.php" class="btn">Go to Courses</a>
                </div>
            <?php elseif ($role == 'teacher'): ?>
                <div class="card">
                    <h3>My Courses</h3>
                    <p>View and manage your courses</p>
                    <a href="my_courses.php" class="btn">View Courses</a>
                </div>
                <div class="card">
                    <h3>Grade Students</h3>
                    <p>Enter and manage student grades</p>
                    <a href="grade_students.php" class="btn">Grade Students</a>
                </div>
            <?php elseif ($role == 'student'): ?>
                <div class="card">
                    <h3>My Grades</h3>
                    <p>View your grades and academic progress</p>
                    <a href="my_grades.php" class="btn">View Grades</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>