<?php
require_once 'config/database.php';

$page_title = "Grade Students"; // Optional
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

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_grade'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $assignment_name = $_POST['assignment_name'];
    $assignment_type = $_POST['assignment_type'];
    $score = $_POST['score'];
    $max_score = $_POST['max_score'];
    $weight = $_POST['weight'];
    $comments = $_POST['comments'];
    
    $stmt = $pdo->prepare("INSERT INTO grades (enrollment_id, assignment_name, assignment_type, score, max_score, weight, comments, date_given) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
    $stmt->execute([$enrollment_id, $assignment_name, $assignment_type, $score, $max_score, $weight, $comments]);
    
    $success = "Grade added successfully!";
}

// Handle grade deletion
if (isset($_GET['delete_grade'])) {
    $grade_id = $_GET['delete_grade'];
    $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
    $stmt->execute([$grade_id]);
    $success = "Grade deleted successfully!";
}

// Get teacher's courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ? ORDER BY course_code");
$stmt->execute([$teacher_id]);
$courses = $stmt->fetchAll();

// Get selected course
$selected_course = isset($_GET['course_id']) ? $_GET['course_id'] : (isset($_POST['course_id']) ? $_POST['course_id'] : '');
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
        .container {
            max-width: 1400px;
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
        h2, h3 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .course-selector {
            margin-bottom: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 8px;
        }
        select, input, textarea {
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 10px;
            font-size: 14px;
        }
        select {
            background: white;
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
            vertical-align: top;
        }
        tr:hover {
            background: #f7fafc;
        }
        .grade-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }
        .grade-form h4 {
            margin-bottom: 15px;
            color: #2d3748;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
        }
        .btn-danger {
            background: #e53e3e;
        }
        .btn-danger:hover {
            background: #c53030;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #48bb78;
        }
        .logout {
            color: #e53e3e !important;
        }
        .student-section {
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
        }
        .student-header {
            background: #edf2f7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .student-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #2d3748;
        }
        .grades-table {
            width: 100%;
            margin-top: 10px;
            font-size: 14px;
        }
        .grades-table th {
            background: #4299e1;
            font-size: 13px;
        }
        .toggle-form {
            background: #48bb78;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }
        .toggle-form:hover {
            background: #38a169;
        }
        .assignment-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .type-quiz { background: #9f7aea; color: white; }
        .type-midterm { background: #f6ad55; color: white; }
        .type-final { background: #fc8181; color: white; }
        .type-project { background: #4fd1c5; color: white; }
        .type-homework { background: #b794f4; color: white; }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Grade Students</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Course Selection -->
            <div class="course-selector">
                <form method="GET" action="">
                    <label for="course_id"><strong>Select Course:</strong></label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()">
                        <option value="">-- Choose a course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if ($selected_course): ?>
                <?php
                // Get enrolled students with their grades for this course
                $stmt = $pdo->prepare("
                    SELECT e.id as enrollment_id, s.id as student_id, s.student_id as student_number, 
                           s.first_name, s.last_name, s.email,
                           c.course_name, c.course_code
                    FROM enrollments e
                    JOIN students s ON e.student_id = s.id
                    JOIN courses c ON e.course_id = c.id
                    WHERE e.course_id = ?
                    ORDER BY s.last_name, s.first_name
                ");
                $stmt->execute([$selected_course]);
                $students = $stmt->fetchAll();
                
                // Get course info
                $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
                $stmt->execute([$selected_course]);
                $course_info = $stmt->fetch();
                ?>
                
                <h3><?php echo htmlspecialchars($course_info['course_code'] . ' - ' . $course_info['course_name']); ?></h3>
                <p>Total Students Enrolled: <?php echo count($students); ?></p>
                
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): 
                        // Get grades for this student in this course
                        $stmt = $pdo->prepare("
                            SELECT g.* 
                            FROM grades g
                            WHERE g.enrollment_id = ?
                            ORDER BY g.date_given DESC, g.id DESC
                        ");
                        $stmt->execute([$student['enrollment_id']]);
                        $grades = $stmt->fetchAll();
                        
                        // Calculate average
                        $total_percentage = 0;
                        $total_weight = 0;
                        foreach ($grades as $grade) {
                            $percentage = ($grade['score'] / $grade['max_score']) * 100;
                            $total_percentage += $percentage * $grade['weight'];
                            $total_weight += $grade['weight'];
                        }
                        $average = ($total_weight > 0) ? round($total_percentage / $total_weight, 2) : 0;
                    ?>
                    <div class="student-section">
                        <div class="student-header">
                            <div>
                                <span class="student-name"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></span>
                                <span style="margin-left: 15px; color: #718096;">(<?php echo $student['student_number']; ?>)</span>
                                <span style="margin-left: 15px;">Email: <?php echo $student['email']; ?></span>
                            </div>
                            <div>
                                <strong>Average: <?php echo $average; ?>%</strong>
                                <button class="toggle-form" onclick="toggleGradeForm(<?php echo $student['enrollment_id']; ?>)">+ Add Grade</button>
                            </div>
                        </div>
                        
                        <!-- Grade Form (Hidden by default) -->
                        <div id="grade-form-<?php echo $student['enrollment_id']; ?>" class="grade-form" style="display: none;">
                            <h4>Add Grade for <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                            <form method="POST" action="">
                                <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
                                <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                                
                                <div class="form-row">
                                    <div>
                                        <label>Assignment Name:</label>
                                        <input type="text" name="assignment_name" placeholder="e.g., Midterm Exam" required>
                                    </div>
                                    <div>
                                        <label>Assignment Type:</label>
                                        <select name="assignment_type" required>
                                            <option value="quiz">Quiz</option>
                                            <option value="midterm">Midterm</option>
                                            <option value="final">Final</option>
                                            <option value="project">Project</option>
                                            <option value="homework">Homework</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div>
                                        <label>Score:</label>
                                        <input type="number" step="0.01" name="score" placeholder="e.g., 85" required>
                                    </div>
                                    <div>
                                        <label>Max Score:</label>
                                        <input type="number" step="0.01" name="max_score" placeholder="e.g., 100" required>
                                    </div>
                                    <div>
                                        <label>Weight:</label>
                                        <input type="number" step="0.01" name="weight" value="1.00" placeholder="e.g., 1.0">
                                    </div>
                                </div>
                                
                                <div>
                                    <label>Comments (Optional):</label>
                                    <textarea name="comments" rows="2" placeholder="Add any comments about this grade..."></textarea>
                                </div>
                                
                                <button type="submit" name="submit_grade" class="btn btn-success">Save Grade</button>
                                <button type="button" class="btn" onclick="toggleGradeForm(<?php echo $student['enrollment_id']; ?>)">Cancel</button>
                            </form>
                        </div>
                        
                        <!-- Existing Grades Table -->
                        <?php if (count($grades) > 0): ?>
                            <table class="grades-table">
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): 
                                        $percentage = round(($grade['score'] / $grade['max_score']) * 100, 2);
                                        $type_class = 'type-' . $grade['assignment_type'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                                        <td>
                                            <span class="assignment-type <?php echo $type_class; ?>">
                                                <?php echo ucfirst($grade['assignment_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $grade['score']; ?></td>
                                        <td><?php echo $grade['max_score']; ?></td>
                                        <td><?php echo $percentage; ?>%</td>
                                        <td><?php echo $grade['weight']; ?></td>
                                        <td><?php echo $grade['date_given']; ?></td>
                                        <td><?php echo htmlspecialchars($grade['comments'] ?: '-'); ?></td>
                                        <td>
                                            <a href="?course_id=<?php echo $selected_course; ?>&delete_grade=<?php echo $grade['id']; ?>" 
                                               class="btn btn-small btn-danger" 
                                               onclick="return confirm('Delete this grade?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #718096; font-style: italic; margin-top: 10px;">No grades added yet for this student.</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No students enrolled in this course.</p>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; color: #718096; padding: 40px;">Please select a course to start grading.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function toggleGradeForm(enrollmentId) {
        var form = document.getElementById('grade-form-' + enrollmentId);
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
    </script>
</body>
</html>