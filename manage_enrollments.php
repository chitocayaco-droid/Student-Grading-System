<?php
require_once 'config/database.php';

$page_title = "Manage Enrollments"; // Optional
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Get course_id from URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id === 0) {
    header("Location: manage_courses.php");
    exit();
}

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: manage_courses.php");
    exit();
}

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Enroll student
        if ($_POST['action'] == 'enroll') {
            $student_id = $_POST['student_id'];
            $semester = $_POST['semester'];
            $year = $_POST['year'];
            
            // Check if already enrolled
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND semester = ? AND year = ?");
            $stmt->execute([$student_id, $course_id, $semester, $year]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, semester, year, enrollment_date) VALUES (?, ?, ?, ?, CURDATE())");
                $stmt->execute([$student_id, $course_id, $semester, $year]);
                $success = "Student enrolled successfully!";
            } else {
                $error = "Student is already enrolled in this course for the selected semester!";
            }
        }
        
        // Unenroll student
        if ($_POST['action'] == 'unenroll') {
            $enrollment_id = $_POST['enrollment_id'];
            
            // First delete all grades for this enrollment
            $stmt = $pdo->prepare("DELETE FROM grades WHERE enrollment_id = ?");
            $stmt->execute([$enrollment_id]);
            
            // Then delete the enrollment
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
            $stmt->execute([$enrollment_id]);
            
            $success = "Student unenrolled successfully!";
        }
    }
}

// Get all enrollments for this course
$stmt = $pdo->prepare("
    SELECT e.*, 
           s.student_id, 
           s.first_name, 
           s.last_name, 
           s.email,
           (SELECT COUNT(*) FROM grades WHERE enrollment_id = e.id) as grade_count
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.course_id = ?
    ORDER BY s.last_name, s.first_name
");
$stmt->execute([$course_id]);
$enrollments = $stmt->fetchAll();

// Get all students for dropdown (excluding those already enrolled in the current semester/year)
$current_semester = isset($_POST['semester']) ? $_POST['semester'] : 'Fall';
$current_year = isset($_POST['year']) ? $_POST['year'] : date('Y');

$stmt = $pdo->prepare("
    SELECT s.* 
    FROM students s
    WHERE s.id NOT IN (
        SELECT student_id 
        FROM enrollments 
        WHERE course_id = ? AND semester = ? AND year = ?
    )
    ORDER BY s.last_name, s.first_name
");
$stmt->execute([$course_id, $current_semester, $current_year]);
$available_students = $stmt->fetchAll();

// Get all semesters and years from existing enrollments for this course
$stmt = $pdo->prepare("
    SELECT DISTINCT semester, year 
    FROM enrollments 
    WHERE course_id = ? 
    ORDER BY year DESC, 
             CASE semester 
                WHEN 'Spring' THEN 1 
                WHEN 'Summer' THEN 2 
                WHEN 'Fall' THEN 3 
             END
");
$stmt->execute([$course_id]);
$existing_semesters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrollments - <?php echo htmlspecialchars($course['course_code']); ?></title>
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
            margin-bottom: 20px;
        }
        h2 {
            color: #2d3748;
            margin-bottom: 10px;
        }
        h3 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .course-info {
            background: #ebf4ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4299e1;
        }
        .course-info p {
            margin: 5px 0;
            color: #2c5282;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-danger {
            background: #e53e3e;
        }
        .btn-danger:hover {
            background: #c53030;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
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
        tr:hover {
            background: #f7fafc;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #48bb78;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f56565;
        }
        .logout {
            color: #e53e3e !important;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #718096;
            font-size: 14px;
        }
        .enrollment-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-grades {
            background: #9f7aea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manage_courses.php" class="back-link">← Back to Courses</a>
        
        <div class="card">
            <div class="course-info">
                <h2><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h2>
                <p>Credits: <?php echo $course['credits']; ?> | Description: <?php echo htmlspecialchars($course['description'] ?: 'No description'); ?></p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-box">
                <div class="stat">
                    <div class="stat-value"><?php echo count($enrollments); ?></div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo count($available_students); ?></div>
                    <div class="stat-label">Available to Enroll</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo count($existing_semesters); ?></div>
                    <div class="stat-label">Active Semesters</div>
                </div>
            </div>
            
            <!-- Enrolled Students Section -->
            <h3>Current Enrollments</h3>
            
            <?php if (count($enrollments) > 0): ?>
                <!-- Semester Filter -->
                <?php if (count($existing_semesters) > 0): ?>
                <div style="margin-bottom: 15px;">
                    <form method="GET" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <select name="semester_filter" onchange="this.form.submit()" style="padding: 5px;">
                            <option value="">All Semesters</option>
                            <?php foreach ($existing_semesters as $sem): ?>
                                <option value="<?php echo $sem['semester'] . '-' . $sem['year']; ?>" 
                                    <?php echo (isset($_GET['semester_filter']) && $_GET['semester_filter'] == $sem['semester'] . '-' . $sem['year']) ? 'selected' : ''; ?>>
                                    <?php echo $sem['semester'] . ' ' . $sem['year']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Semester</th>
                            <th>Year</th>
                            <th>Enrollment Date</th>
                            <th>Grades</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $filter = isset($_GET['semester_filter']) ? $_GET['semester_filter'] : '';
                        foreach ($enrollments as $enrollment):
                            if ($filter) {
                                list($filter_sem, $filter_year) = explode('-', $filter);
                                if ($enrollment['semester'] != $filter_sem || $enrollment['year'] != $filter_year) {
                                    continue;
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enrollment['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['last_name'] . ', ' . $enrollment['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                            <td><?php echo $enrollment['semester']; ?></td>
                            <td><?php echo $enrollment['year']; ?></td>
                            <td><?php echo $enrollment['enrollment_date']; ?></td>
                            <td>
                                <span class="badge badge-grades"><?php echo $enrollment['grade_count']; ?> grades</span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to unenroll this student? This will also delete all their grades for this course.')">
                                    <input type="hidden" name="action" value="unenroll">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Unenroll</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: #718096; background: #f7fafc; border-radius: 5px;">
                    No students enrolled in this course yet.
                </p>
            <?php endif; ?>
            
            <!-- Enroll New Student Section -->
            <div style="margin-top: 40px;">
                <h3>Enroll New Student</h3>
                
                <?php if (count($available_students) > 0): ?>
                    <div class="enrollment-form">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="enroll">
                            
                            <div class="form-group">
                                <label for="student_id">Select Student:</label>
                                <select name="student_id" id="student_id" required>
                                    <option value="">-- Choose a student --</option>
                                    <?php foreach ($available_students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['student_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="semester">Semester:</label>
                                <select name="semester" id="semester" required>
                                    <option value="Fall">Fall</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="year">Year:</label>
                                <select name="year" id="year" required>
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Enroll Student</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p style="color: #718096; background: #f7fafc; padding: 20px; border-radius: 5px;">
                        No available students to enroll. All students are already enrolled in this course for the selected semester.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>