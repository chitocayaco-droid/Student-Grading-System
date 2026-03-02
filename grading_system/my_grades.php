<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

// Get student ID from user ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'];

// Get enrollments with grades
$stmt = $pdo->prepare("
    SELECT c.course_code, c.course_name, c.credits,
           e.semester, e.year,
           g.assignment_name, g.assignment_type, g.score, g.max_score, g.weight,
           t.first_name as teacher_fname, t.last_name as teacher_lname
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN grades g ON e.id = g.enrollment_id
    LEFT JOIN teachers t ON c.teacher_id = t.id
    WHERE e.student_id = ?
    ORDER BY c.course_code, g.date_given
");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Grading System</title>
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
        .grades-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        h2 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .course-header {
            background: #edf2f7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .course-header h3 {
            color: #2d3748;
            margin-bottom: 5px;
        }
        .course-header p {
            color: #4a5568;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        tr:hover {
            background: #f7fafc;
        }
        .grade-summary {
            margin-top: 20px;
            padding: 15px;
            background: #f0fff4;
            border-left: 4px solid #48bb78;
            border-radius: 5px;
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
                <a href="my_grades.php">My Grades</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="grades-card">
            <h2>My Academic Record</h2>
            
            <?php
            $current_course = '';
            $total_credits = 0;
            $total_weighted_score = 0;
            $total_weight = 0;
            
            foreach ($grades as $grade):
                if ($current_course != $grade['course_code']):
                    if ($current_course != ''):
                        // Show course summary
                        $course_gpa = ($total_weight > 0) ? ($total_weighted_score / $total_weight) : 0;
                        echo "<div class='grade-summary'>";
                        echo "<p><strong>Course Average: " . number_format($course_gpa, 2) . "%</strong></p>";
                        echo "</div>";
                        echo "</div>";
                    endif;
                    
                    $current_course = $grade['course_code'];
                    $total_weighted_score = 0;
                    $total_weight = 0;
                    ?>
                    <div class="course-header">
                        <h3><?php echo $grade['course_code'] . ' - ' . $grade['course_name']; ?></h3>
                        <p>Teacher: <?php echo $grade['teacher_fname'] . ' ' . $grade['teacher_lname']; ?></p>
                        <p>Credits: <?php echo $grade['credits']; ?> | Semester: <?php echo $grade['semester'] . ' ' . $grade['year']; ?></p>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Type</th>
                                <th>Score</th>
                                <th>Max Score</th>
                                <th>Percentage</th>
                                <th>Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                <?php endif; ?>
                
                <?php if ($grade['assignment_name']): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                        <td><?php echo ucfirst($grade['assignment_type']); ?></td>
                        <td><?php echo $grade['score']; ?></td>
                        <td><?php echo $grade['max_score']; ?></td>
                        <td><?php echo number_format(($grade['score'] / $grade['max_score']) * 100, 2); ?>%</td>
                        <td><?php echo $grade['weight']; ?></td>
                    </tr>
                    <?php
                    $percentage = ($grade['score'] / $grade['max_score']) * 100;
                    $total_weighted_score += $percentage * $grade['weight'];
                    $total_weight += $grade['weight'];
                endif;
            endforeach;
            
            // Show last course summary
            if ($current_course != ''):
                $course_gpa = ($total_weight > 0) ? ($total_weighted_score / $total_weight) : 0;
                echo "</tbody></table>";
                echo "<div class='grade-summary'>";
                echo "<p><strong>Course Average: " . number_format($course_gpa, 2) . "%</strong></p>";
                echo "</div>";
            endif;
            ?>
        </div>
    </div>
</body>
</html>