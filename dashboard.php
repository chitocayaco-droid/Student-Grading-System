<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];
$page_title = "Dashboard";

// Function to calculate grade point from percentage
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

// Get student data if logged in as student
$student_courses = [];
$recent_grades = [];
$gpa_data = ['total_points' => 0, 'total_courses' => 0, 'gpa' => 0];

if ($role == 'student') {
    // Get student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    $student_id = $student['id'];
    
    // Get all courses with grade summaries
    $stmt = $pdo->prepare("
        SELECT c.id, c.course_code, c.course_name, c.credits,
               e.id as enrollment_id, e.semester, e.year,
               t.first_name as teacher_fname, t.last_name as teacher_lname,
               COUNT(g.id) as total_assignments,
               COALESCE(SUM(g.score * g.weight), 0) as weighted_score,
               COALESCE(SUM(g.max_score * g.weight), 0) as weighted_max,
               COALESCE(SUM(g.weight), 0) as total_weight
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN teachers t ON c.teacher_id = t.id
        LEFT JOIN grades g ON e.id = g.enrollment_id
        WHERE e.student_id = ?
        GROUP BY c.id, c.course_code, c.course_name, c.credits, 
                 e.id, e.semester, e.year, t.first_name, t.last_name
        ORDER BY e.year DESC, 
                 CASE e.semester 
                    WHEN 'Fall' THEN 1 
                    WHEN 'Summer' THEN 2 
                    WHEN 'Spring' THEN 3 
                 END
    ");
    $stmt->execute([$student_id]);
    $student_courses = $stmt->fetchAll();
    
    // Calculate GPA
    $total_points = 0;
    $total_courses = 0;
    
    foreach ($student_courses as $course) {
        if ($course['total_weight'] > 0) {
            $percentage = ($course['weighted_score'] / $course['weighted_max']) * 100;
            $grade_point = calculateGradePoint($percentage);
            $total_points += $grade_point * $course['credits'];
            $total_courses += $course['credits'];
        }
    }
    
    $gpa_data = [
        'total_points' => $total_points,
        'total_courses' => $total_courses,
        'gpa' => $total_courses > 0 ? round($total_points / $total_courses, 2) : 0
    ];
    
    // Get recent grades (last 5)
    $stmt = $pdo->prepare("
        SELECT g.*, c.course_code, c.course_name,
               CONCAT(s.first_name, ' ', s.last_name) as student_name
        FROM grades g
        JOIN enrollments e ON g.enrollment_id = e.id
        JOIN courses c ON e.course_id = c.id
        JOIN students s ON e.student_id = s.id
        WHERE e.student_id = ?
        ORDER BY g.date_given DESC, g.id DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_grades = $stmt->fetchAll();
}

// Get counts for admin/teacher dashboards
if ($role == 'admin') {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $student_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teachers");
    $teacher_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $course_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollments");
    $enrollment_count = $stmt->fetch()['count'];
}

if ($role == 'teacher') {
    // Get teacher ID
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $teacher = $stmt->fetch();
    $teacher_id = $teacher['id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $course_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.student_id) as count 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $student_count = $stmt->fetch()['count'];
}

include 'includes/header.php';
?>

<style>
    .welcome-box {
        background: linear-gradient(135deg, #2d4996 0%, #2563eb 100%);
        color: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        margin-bottom: 30px;
    }
    .welcome-box h1 {
        font-size: 2.5em;
        margin-bottom: 10px;
    }
    .welcome-box p {
        font-size: 1.2em;
        opacity: 0.9;
    }
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        padding: 25px;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #e2e8f0;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .card h3 {
        color: #2d3748;
        margin-bottom: 15px;
        font-size: 1.3em;
        border-bottom: 2px solid #edf2f7;
        padding-bottom: 10px;
    }
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 5px;
    }
    .stat-label {
        color: #718096;
        font-size: 0.9em;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .btn {
        display: inline-block;
        padding: 12px 24px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        margin-top: 15px;
        font-weight: 500;
        transition: background 0.3s;
        border: none;
        cursor: pointer;
    }
    .btn:hover {
        background: #5a67d8;
    }
    .btn-outline {
        background: transparent;
        border: 2px solid #667eea;
        color: #667eea;
    }
    .btn-outline:hover {
        background: #667eea;
        color: white;
    }
    .course-list {
        margin-top: 15px;
    }
    .course-item {
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 10px;
        background: #f8fafc;
    }
    .course-code {
        font-weight: bold;
        color: #667eea;
        font-size: 1.1em;
    }
    .course-grade {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9em;
    }
    .grade-a { background: #c6f6d5; color: #22543d; }
    .grade-b { background: #bee3f8; color: #2c5282; }
    .grade-c { background: #feebc8; color: #7b341e; }
    .grade-d { background: #fed7d7; color: #742a2a; }
    .grade-f { background: #fed7d7; color: #742a2a; }
    
    .gpa-box {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    .gpa-value {
        font-size: 3em;
        font-weight: bold;
        line-height: 1;
    }
    .gpa-label {
        font-size: 0.9em;
        opacity: 0.9;
        margin-top: 5px;
    }
    .grade-table {
        width: 100%;
        border-collapse: collapse;
    }
    .grade-table th {
        text-align: left;
        padding: 10px;
        background: #f7fafc;
        color: #4a5568;
        font-size: 0.9em;
        font-weight: 600;
    }
    .grade-table td {
        padding: 10px;
        border-bottom: 1px solid #e2e8f0;
    }
    .percentage-bar {
        width: 100%;
        height: 6px;
        background: #edf2f7;
        border-radius: 3px;
        overflow: hidden;
    }
    .percentage-fill {
        height: 100%;
        background: #667eea;
        border-radius: 3px;
        transition: width 0.3s;
    }
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 15px;
    }
    .quick-stat {
        text-align: center;
        padding: 10px;
        background: #f7fafc;
        border-radius: 8px;
    }
    .quick-stat-value {
        font-size: 1.5em;
        font-weight: bold;
        color: #2d3748;
    }
    .quick-stat-label {
        font-size: 0.8em;
        color: #718096;
    }
</style>

<div class="container">
    <div class="welcome-box">
        <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h1>
        <p>You are logged in as <strong><?php echo ucfirst($role); ?></strong></p>
    </div>
    
    <?php if ($role == 'student'): ?>
        <!-- Student Dashboard with Grades Overview -->
        <div class="dashboard-grid">
            <!-- GPA Card -->
            <div class="card">
                <h3>📊 Your GPA</h3>
                <div class="gpa-box">
                    <div class="gpa-value"><?php echo number_format($gpa_data['gpa'], 2); ?></div>
                    <div class="gpa-label">out of 4.0</div>
                </div>
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-value"><?php echo count($student_courses); ?></div>
                        <div class="quick-stat-label">Courses</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-value"><?php 
                            $total_assignments = 0;
                            foreach ($student_courses as $course) {
                                $total_assignments += $course['total_assignments'];
                            }
                            echo $total_assignments;
                        ?></div>
                        <div class="quick-stat-label">Assignments</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-value"><?php 
                            $completed_courses = 0;
                            foreach ($student_courses as $course) {
                                if ($course['total_weight'] > 0) $completed_courses++;
                            }
                            echo $completed_courses;
                        ?></div>
                        <div class="quick-stat-label">Completed</div>
                    </div>
                </div>
            </div>
            
            <!-- Current Courses Card -->
            <div class="card">
                <h3>📚 Current Courses</h3>
                <div class="course-list">
                    <?php if (count($student_courses) > 0): ?>
                        <?php foreach ($student_courses as $index => $course): 
                            if ($index < 5): 
                                $percentage = $course['total_weight'] > 0 
                                    ? round(($course['weighted_score'] / $course['weighted_max']) * 100, 2) 
                                    : 0;
                                
                                $letter_grade = 'N/A';
                                $grade_class = '';
                                if ($course['total_weight'] > 0) {
                                    if ($percentage >= 90) { $letter_grade = 'A'; $grade_class = 'grade-a'; }
                                    elseif ($percentage >= 80) { $letter_grade = 'B'; $grade_class = 'grade-b'; }
                                    elseif ($percentage >= 70) { $letter_grade = 'C'; $grade_class = 'grade-c'; }
                                    elseif ($percentage >= 60) { $letter_grade = 'D'; $grade_class = 'grade-d'; }
                                    else { $letter_grade = 'F'; $grade_class = 'grade-f'; }
                                }
                        ?>
                            <div class="course-item">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                        <div style="font-size: 0.9em; color: #4a5568;"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    </div>
                                    <?php if ($course['total_weight'] > 0): ?>
                                        <span class="course-grade <?php echo $grade_class; ?>">
                                            <?php echo $letter_grade; ?> (<?php echo $percentage; ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="course-grade" style="background: #e2e8f0; color: #4a5568;">
                                            No grades
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($course['total_weight'] > 0): ?>
                                    <div style="margin-top: 10px;">
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                        
                        <?php if (count($student_courses) > 5): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="my_grades.php" class="btn btn-outline" style="padding: 8px 16px;">View All Courses</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #718096; text-align: center; padding: 20px;">
                            You are not enrolled in any courses yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Grades Card -->
            <div class="card">
                <h3>📝 Recent Grades</h3>
                <?php if (count($recent_grades) > 0): ?>
                    <table class="grade-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Assignment</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_grades as $grade): 
                                $percentage = round(($grade['score'] / $grade['max_score']) * 100, 1);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['course_code']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($grade['assignment_name']); ?></div>
                                    <div style="font-size: 0.8em; color: #718096;"><?php echo ucfirst($grade['assignment_type']); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo $grade['score']; ?>/<?php echo $grade['max_score']; ?></strong>
                                    <div style="font-size: 0.8em; color: <?php echo $percentage >= 60 ? '#48bb78' : '#e53e3e'; ?>;">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </td>
                                <td><?php echo date('M d', strtotime($grade['date_given'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="my_grades.php" class="btn btn-outline" style="padding: 8px 16px;">View All Grades</a>
                    </div>
                <?php else: ?>
                    <p style="color: #718096; text-align: center; padding: 20px;">
                        No grades have been posted yet.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($role == 'admin'): ?>
        <!-- Admin Dashboard -->
        <div class="dashboard-grid">
            <div class="card">
                <h3>👥 Students</h3>
                <div class="stat-number"><?php echo $student_count; ?></div>
                <div class="stat-label">Total Enrolled Students</div>
                <a href="manage_students.php" class="btn">Manage Students</a>
            </div>
            
            <div class="card">
                <h3>👨‍🏫 Teachers</h3>
                <div class="stat-number"><?php echo $teacher_count; ?></div>
                <div class="stat-label">Active Teachers</div>
                <a href="manage_teachers.php" class="btn">Manage Teachers</a>
            </div>
            
            <div class="card">
                <h3>📚 Courses</h3>
                <div class="stat-number"><?php echo $course_count; ?></div>
                <div class="stat-label">Available Courses</div>
                <a href="manage_courses.php" class="btn">Manage Courses</a>
            </div>
            
            <div class="card">
                <h3>📝 Enrollments</h3>
                <div class="stat-number"><?php echo $enrollment_count; ?></div>
                <div class="stat-label">Total Course Enrollments</div>
                <a href="manage_courses.php" class="btn">View All Courses</a>
            </div>
            
            <div class="card">
                <h3>🔐 Password Management</h3>
                <div class="stat-number"><?php echo $student_count + $teacher_count + 1; ?></div>
                <div class="stat-label">User Accounts</div>
                <p style="color: #718096; margin: 10px 0; font-size: 14px;">
                    Manage passwords for all users including yourself.
                </p>
                <a href="manage_passwords.php" class="btn">Manage Passwords</a>
            </div>
        </div>
        
    <?php elseif ($role == 'teacher'): ?>
        <!-- Teacher Dashboard -->
        <div class="dashboard-grid">
            <div class="card">
                <h3>📚 My Courses</h3>
                <div class="stat-number"><?php echo $course_count; ?></div>
                <div class="stat-label">Courses You Teach</div>
                <a href="my_courses.php" class="btn">View Courses</a>
            </div>
            
            <div class="card">
                <h3>👥 My Students</h3>
                <div class="stat-number"><?php echo $student_count; ?></div>
                <div class="stat-label">Students Across All Courses</div>
                <a href="grade_students.php" class="btn">Grade Students</a>
            </div>
            
            <div class="card">
                <h3>📝 Recent Activity</h3>
                <?php
                // Get recent grades entered by this teacher
                $stmt = $pdo->prepare("
                    SELECT g.*, c.course_code, s.first_name, s.last_name
                    FROM grades g
                    JOIN enrollments e ON g.enrollment_id = e.id
                    JOIN courses c ON e.course_id = c.id
                    JOIN students s ON e.student_id = s.id
                    WHERE c.teacher_id = ?
                    ORDER BY g.date_given DESC
                    LIMIT 5
                ");
                $stmt->execute([$teacher_id]);
                $recent_teacher_grades = $stmt->fetchAll();
                ?>
                
                <?php if (count($recent_teacher_grades) > 0): ?>
                    <div style="font-size: 0.9em;">
                        <?php foreach ($recent_teacher_grades as $grade): ?>
                            <div style="padding: 8px; border-bottom: 1px solid #e2e8f0;">
                                <strong><?php echo htmlspecialchars($grade['course_code']); ?></strong><br>
                                <?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?>:
                                <?php echo $grade['score']; ?>/<?php echo $grade['max_score']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #718096;">No recent grades</p>
                <?php endif; ?>
                <a href="grade_students.php" class="btn" style="margin-top: 15px;">Enter Grades</a>
            </div>
            
            <div class="card">
                <h3>⚡ Quick Actions</h3>
                <div style="display: grid; gap: 10px;">
                    <a href="grade_students.php" class="btn" style="text-align: center;">Enter New Grades</a>
                    <a href="my_courses.php" class="btn btn-outline" style="text-align: center;">View My Courses</a>
                    <a href="#" class="btn btn-outline" style="text-align: center;" onclick="alert('Grade reports coming soon!')">Generate Reports</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>