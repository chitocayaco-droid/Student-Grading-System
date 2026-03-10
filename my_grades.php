<?php
require_once 'config/database.php';

$page_title = "My Grades"; // Optional
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

// Get student ID from user ID
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'];

// Get all enrollments with grades for this student
$stmt = $pdo->prepare("
    SELECT c.id as course_id, c.course_code, c.course_name, c.credits,
           e.id as enrollment_id, e.semester, e.year,
           t.first_name as teacher_fname, t.last_name as teacher_lname,
           g.id as grade_id, g.assignment_name, g.assignment_type, 
           g.score, g.max_score, g.weight, g.comments, g.date_given
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN teachers t ON c.teacher_id = t.id
    LEFT JOIN grades g ON e.id = g.enrollment_id
    WHERE e.student_id = ?
    ORDER BY c.course_code, g.date_given DESC
");
$stmt->execute([$student_id]);
$results = $stmt->fetchAll();

// Organize data by course
$courses = [];
foreach ($results as $row) {
    $course_key = $row['course_id'];
    if (!isset($courses[$course_key])) {
        $courses[$course_key] = [
            'code' => $row['course_code'],
            'name' => $row['course_name'],
            'credits' => $row['credits'],
            'semester' => $row['semester'],
            'year' => $row['year'],
            'teacher' => $row['teacher_fname'] . ' ' . $row['teacher_lname'],
            'grades' => [],
            'total_weighted' => 0,
            'total_weight' => 0
        ];
    }
    
    if ($row['grade_id']) {
        $percentage = ($row['score'] / $row['max_score']) * 100;
        $courses[$course_key]['grades'][] = [
            'name' => $row['assignment_name'],
            'type' => $row['assignment_type'],
            'score' => $row['score'],
            'max' => $row['max_score'],
            'percentage' => $percentage,
            'weight' => $row['weight'],
            'comments' => $row['comments'],
            'date' => $row['date_given']
        ];
        $courses[$course_key]['total_weighted'] += $percentage * $row['weight'];
        $courses[$course_key]['total_weight'] += $row['weight'];
    }
}

// Calculate GPA (simplified - 4.0 scale based on percentage)
function calculateGradePoint($percentage) {
    if ($percentage >= 93) return 4.0;
    if ($percentage >= 90) return 3.7;
    if ($percentage >= 87) return 3.3;
    if ($percentage >= 83) return 3.0;
    if ($percentage >= 80) return 2.7;
    if ($percentage >= 77) return 2.3;
    if ($percentage >= 73) return 2.0;
    if ($percentage >= 70) return 1.7;
    if ($percentage >= 67) return 1.3;
    if ($percentage >= 63) return 1.0;
    if ($percentage >= 60) return 0.7;
    return 0.0;
}
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
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        h2 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .course-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
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
            font-size: 14px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover {
            background: #edf2f7;
        }
        .grade-summary {
            margin-top: 15px;
            padding: 15px;
            background: #f0fff4;
            border-left: 4px solid #48bb78;
            border-radius: 5px;
        }
        .grade-point {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .grade-a { background: #48bb78; color: white; }
        .grade-b { background: #4299e1; color: white; }
        .grade-c { background: #f6ad55; color: white; }
        .grade-d { background: #fc8181; color: white; }
        .grade-f { background: #f56565; color: white; }
        .assignment-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .type-quiz { background: #9f7aea; }
        .type-midterm { background: #f6ad55; }
        .type-final { background: #fc8181; }
        .type-project { background: #4fd1c5; }
        .type-homework { background: #b794f4; }
        .logout {
            color: #e53e3e !important;
        }
        .gpa-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .gpa-box h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .no-grades {
            text-align: center;
            padding: 40px;
            color: #718096;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (empty($courses)): ?>
            <div class="card">
                <div class="no-grades">
                    <h3>No Grades Available</h3>
                    <p>You are not enrolled in any courses yet or no grades have been posted.</p>
                </div>
            </div>
        <?php else: 
            // Calculate overall GPA
            $total_points = 0;
            $total_credits = 0;
        ?>
            <div class="gpa-box">
                <h3><?php 
                    $total_courses_with_grades = 0;
                    $sum_gpa = 0;
                    foreach ($courses as $course) {
                        if ($course['total_weight'] > 0) {
                            $course_avg = $course['total_weighted'] / $course['total_weight'];
                            $sum_gpa += calculateGradePoint($course_avg);
                            $total_courses_with_grades++;
                        }
                    }
                    $overall_gpa = $total_courses_with_grades > 0 ? $sum_gpa / $total_courses_with_grades : 0;
                    echo number_format($overall_gpa, 2);
                ?></h3>
                <p>Overall GPA (on a 4.0 scale)</p>
            </div>
            
            <?php foreach ($courses as $course): 
                $course_avg = $course['total_weight'] > 0 ? $course['total_weighted'] / $course['total_weight'] : 0;
                $grade_point = calculateGradePoint($course_avg);
                $letter_grade = '';
                if ($course_avg >= 90) $letter_grade = 'A';
                elseif ($course_avg >= 80) $letter_grade = 'B';
                elseif ($course_avg >= 70) $letter_grade = 'C';
                elseif ($course_avg >= 60) $letter_grade = 'D';
                else $letter_grade = 'F';
                
                $grade_class = 'grade-' . strtolower($letter_grade);
            ?>
                <div class="course-card">
                    <div class="course-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3><?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?></h3>
                                <p>Teacher: <?php echo htmlspecialchars($course['teacher'] ?: 'Not Assigned'); ?></p>
                                <p>Semester: <?php echo $course['semester'] . ' ' . $course['year']; ?> | Credits: <?php echo $course['credits']; ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5em; font-weight: bold; color: #2d3748;">
                                    <?php echo number_format($course_avg, 2); ?>%
                                </div>
                                <div>
                                    <span class="grade-point <?php echo $grade_class; ?>">
                                        <?php echo $letter_grade; ?> (<?php echo number_format($grade_point, 2); ?>)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($course['grades'])): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Assignment</th>
                                    <th>Type</th>
                                    <th>Score</th>
                                    <th>Max</th>
                                    <th>Percentage</th>
                                    <th>Weight</th>
                                    <th>Date</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($course['grades'] as $grade): 
                                    $type_class = 'type-' . $grade['type'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['name']); ?></td>
                                    <td>
                                        <span class="assignment-type <?php echo $type_class; ?>">
                                            <?php echo ucfirst($grade['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $grade['score']; ?></td>
                                    <td><?php echo $grade['max']; ?></td>
                                    <td><?php echo number_format($grade['percentage'], 2); ?>%</td>
                                    <td><?php echo $grade['weight']; ?></td>
                                    <td><?php echo $grade['date']; ?></td>
                                    <td><?php echo htmlspecialchars($grade['comments'] ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="grade-summary">
                            <p><strong>Course Average: <?php echo number_format($course_avg, 2); ?>%</strong> 
                            (Grade Point: <?php echo number_format($grade_point, 2); ?>)</p>
                        </div>
                    <?php else: ?>
                        <p style="color: #718096; font-style: italic; padding: 15px;">No grades have been posted for this course yet.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>