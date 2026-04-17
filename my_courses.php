<?php
require_once 'config/database.php';

$page_title = "My Courses"; // Optional
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: index.php");
    exit();
}

// Get teacher ID from user ID
$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacher_id = $teacher['id'];

// Get teacher's courses with enrollment counts
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(e.id) as enrolled_students
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.course_code
");
$stmt->execute([$teacher_id]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Grading System</title>
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
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .course-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .course-code {
            font-size: 1.2em;
            font-weight: bold;
            color: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            margin-bottom: 5px;
        }
        .course-name {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .course-info {
            color: #4a5568;
            margin-bottom: 5px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            margin-right: 10px;
            font-size: 14px;
        }
        .btn:hover {
            background: #fbbf24;
            color: #1332bd;
        }
        .btn-grade {
            background: #48bb78;
        }
        .btn-grade:hover {
            background: #38a169;
            color: white;
        }
        .logout {
            color: #e53e3e !important;
        }
        .no-courses {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>My Courses</h2>
            
            <?php if (count($courses) > 0): ?>
                <div class="course-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                            <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                            <div class="course-info">Credits: <?php echo $course['credits']; ?></div>
                            <div class="course-info">Enrolled Students: <?php echo $course['enrolled_students']; ?></div>
                            <?php if ($course['description']): ?>
                                <div class="course-info"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></div>
                            <?php endif; ?>
                            <div>
                                <a href="view_course_students.php?course_id=<?php echo $course['id']; ?>" class="btn">View Students</a>
                                <a href="grade_students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-grade">Enter Grades</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-courses">
                    <p>You haven't been assigned any courses yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php include 'includes/footer.php'; ?>