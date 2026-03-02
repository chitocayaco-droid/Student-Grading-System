<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: index.php");
    exit();
}

// Get teacher ID from user ID
$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacher_id = $teacher['id'];

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_grade'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $assignment_name = $_POST['assignment_name'];
    $assignment_type = $_POST['assignment_type'];
    $score = $_POST['score'];
    $max_score = $_POST['max_score'];
    $weight = $_POST['weight'];
    
    $stmt = $pdo->prepare("INSERT INTO grades (enrollment_id, assignment_name, assignment_type, score, max_score, weight, date_given) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
    $stmt->execute([$enrollment_id, $assignment_name, $assignment_type, $score, $max_score, $weight]);
    
    $success = "Grade added successfully!";
}

// Get teacher's courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Students - Grading System</title>
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
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .grade-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .course-selector {
            margin-bottom: 30px;
        }
        select, input {
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .grade-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .logout {
            color: #e53e3e !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <h2>Student Grading System</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="my_courses.php">My Courses</a>
                <a href="grade_students.php">Grade Students</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="grade-card">
            <h2>Grade Students</h2>
            
            <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <div class="course-selector">
                <label for="course">Select Course:</label>
                <select id="course" onchange="loadStudents(this.value)">
                    <option value="">Choose a course...</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="student-list"></div>
        </div>
    </div>
    
    <script>
    function loadStudents(courseId) {
        if (courseId) {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("student-list").innerHTML = this.responseText;
                }
            };
            xhr.open("GET", "get_students.php?course_id=" + courseId, true);
            xhr.send();
        } else {
            document.getElementById("student-list").innerHTML = "";
        }
    }
    </script>
</body>
</html>