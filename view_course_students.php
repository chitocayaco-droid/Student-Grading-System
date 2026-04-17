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

// Get course ID from URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id === 0) {
    header("Location: my_courses.php");
    exit();
}

// Verify this course belongs to the teacher
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: my_courses.php");
    exit();
}

// Get all enrolled students with their grades
$stmt = $pdo->prepare("
    SELECT e.id as enrollment_id, 
           s.id as student_id,
           s.student_id as student_number,
           s.first_name, 
           s.last_name, 
           s.email,
           s.phone,
           s.enrollment_date,
           COUNT(g.id) as total_assignments,
           COALESCE(SUM(g.score * g.weight), 0) as weighted_score,
           COALESCE(SUM(g.max_score * g.weight), 0) as weighted_max,
           COALESCE(SUM(g.weight), 0) as total_weight
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    LEFT JOIN grades g ON e.id = g.enrollment_id
    WHERE e.course_id = ?
    GROUP BY e.id, s.id, s.student_id, s.first_name, s.last_name, s.email, s.phone, s.enrollment_date
    ORDER BY s.last_name, s.first_name
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();

$page_title = "View Students - " . $course['course_code'];
include 'includes/header.php';
?>

<style>
    .course-header {
        background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .course-header:hover {
        transform: translateY(-2px);
        box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
    }
    .course-header h2 {
        margin-bottom: 10px;
        font-size: 28px;
        color: #ffcd4d;
    }
    .course-header p {
        opacity: 0.9;
        margin: 5px 0;
    }
    .stats-bar {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .stat-item {
        background: rgba(255,255,255,0.2);
        padding: 10px 20px;
        border-radius: 10px;
        text-align: center;
    }
    .stat-number {
        font-size: 24px;
        font-weight: bold;
    }
    .stat-label {
        font-size: 12px;
        opacity: 0.8;
    }
    .student-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border: 1px solid #e2e8f0;
    }
    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
    }
    .student-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .student-info h3 {
        color: #2d3748;
        margin-bottom: 5px;
    }
    .student-info p {
        color: #718096;
        font-size: 14px;
        margin: 3px 0;
    }
    .student-grade {
        text-align: right;
    }
    .grade-percentage {
        font-size: 28px;
        font-weight: bold;
    }
    .grade-letter {
        font-size: 20px;
        font-weight: bold;
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 5px;
    }
    .grade-a { color: #48bb78; }
    .grade-b { color: #4299e1; }
    .grade-c { color: #ecc94b; }
    .grade-d { color: #ed8936; }
    .grade-f { color: #f56565; }
    .letter-a { background: #c6f6d5; color: #22543d; }
    .letter-b { background: #bee3f8; color: #2c5282; }
    .letter-c { background: #feebc8; color: #7b341e; }
    .letter-d { background: #fed7d7; color: #742a2a; }
    .letter-f { background: #fed7d7; color: #742a2a; }
    
    .grades-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 14px;
    }
    .grades-table th {
        background: #f7fafc;
        color: #4a5568;
        padding: 10px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    .grades-table td {
        padding: 10px;
        border-bottom: 1px solid #e2e8f0;
    }
    .view-grades-btn {
        background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        transition: background 0.3s;
        display: inline-block;
    }
    .view-grades-btn:hover {
        background: #fbbf24;
        color: #1332bd;
    }
    .btn-grade {
        background: #48bb78;
    }
    .btn-grade:hover {
        background: #38a169;
        color: #ffe96d;
    }
    .no-students {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 12px;
        color: #718096;
    }
    .expand-btn {
        background: #edf2f7;
        border: none;
        padding: 5px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.3s;
    }
    .expand-btn:hover {
        background: #e2e8f0;
    }
    .grades-section {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #edf2f7;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 10px;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
        border-radius: 4px;
        transition: width 0.3s;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .btn-sm {
        padding: 5px 12px;
        font-size: 12px;
    }
</style>

<div class="container">
    <!-- Course Header -->
    <div class="course-header">
        <h2>📚 <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h2>
        <p>Credits: <?php echo $course['credits']; ?> | <?php echo htmlspecialchars($course['description'] ?: 'No description'); ?></p>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($students); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $students_with_grades = 0;
                    foreach ($students as $student) {
                        if ($student['total_weight'] > 0) $students_with_grades++;
                    }
                    echo $students_with_grades;
                    ?>
                </div>
                <div class="stat-label">With Grades</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $avg_course_grade = 0;
                    $grade_sum = 0;
                    foreach ($students as $student) {
                        if ($student['total_weight'] > 0) {
                            $percentage = ($student['weighted_score'] / $student['weighted_max']) * 100;
                            $grade_sum += $percentage;
                        }
                    }
                    $avg_course_grade = $students_with_grades > 0 ? round($grade_sum / $students_with_grades, 1) : 0;
                    echo $avg_course_grade . '%';
                    ?>
                </div>
                <div class="stat-label">Class Average</div>
            </div>
        </div>
    </div>
    
    <!-- Students List -->
    <?php if (count($students) > 0): ?>
        <?php foreach ($students as $student): 
            // Calculate student's average for this course
            $percentage = $student['total_weight'] > 0 
                ? round(($student['weighted_score'] / $student['weighted_max']) * 100, 2) 
                : 0;
            
            // Determine letter grade
            $letter_grade = 'N/A';
            $grade_class = '';
            $letter_class = '';
            if ($student['total_weight'] > 0) {
                if ($percentage >= 90) { 
                    $letter_grade = 'A'; 
                    $grade_class = 'grade-a';
                    $letter_class = 'letter-a';
                } elseif ($percentage >= 80) { 
                    $letter_grade = 'B'; 
                    $grade_class = 'grade-b';
                    $letter_class = 'letter-b';
                } elseif ($percentage >= 70) { 
                    $letter_grade = 'C'; 
                    $grade_class = 'grade-c';
                    $letter_class = 'letter-c';
                } elseif ($percentage >= 60) { 
                    $letter_grade = 'D'; 
                    $grade_class = 'grade-d';
                    $letter_class = 'letter-d';
                } else { 
                    $letter_grade = 'F'; 
                    $grade_class = 'grade-f';
                    $letter_class = 'letter-f';
                }
            }
            
            // Get individual grades for this student
            $stmt = $pdo->prepare("
                SELECT g.* 
                FROM grades g
                WHERE g.enrollment_id = ?
                ORDER BY g.date_given DESC, g.id DESC
            ");
            $stmt->execute([$student['enrollment_id']]);
            $individual_grades = $stmt->fetchAll();
        ?>
            <div class="student-card" id="student-<?php echo $student['student_id']; ?>">
                <div class="student-header">
                    <div class="student-info">
                        <h3><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></h3>
                        <p>Student ID: <?php echo htmlspecialchars($student['student_number']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($student['email']); ?></p>
                        <?php if ($student['phone']): ?>
                            <p>Phone: <?php echo htmlspecialchars($student['phone']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="student-grade">
                        <?php if ($student['total_weight'] > 0): ?>
                            <div class="grade-percentage <?php echo $grade_class; ?>">
                                <?php echo $percentage; ?>%
                            </div>
                            <div class="grade-letter <?php echo $letter_class; ?>">
                                <?php echo $letter_grade; ?>
                            </div>
                        <?php else: ?>
                            <div class="grade-percentage" style="color: #a0aec0;">
                                No grades yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($student['total_weight'] > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button class="expand-btn" onclick="toggleGrades(<?php echo $student['student_id']; ?>)">
                        📋 View All Grades
                    </button>
                    <a href="grade_students.php?course_id=<?php echo $course_id; ?>#student-<?php echo $student['student_id']; ?>" 
                       class="view-grades-btn btn-grade btn-sm">
                        ✏️ Add/Edit Grades
                    </a>
                </div>
                
                <!-- Individual Grades Section (Hidden by default) -->
                <div id="grades-<?php echo $student['student_id']; ?>" class="grades-section" style="display: none;">
                    <h4 style="margin-bottom: 10px; color: #2d3748;">📝 All Grades for <?php echo htmlspecialchars($student['first_name']); ?></h4>
                    
                    <?php if (count($individual_grades) > 0): ?>
                        <table class="grades-table">
                            <thead>
                                <tr>
                                    <th>Assignment</th>
                                    <th>Type</th>
                                    <th>Score</th>
                                    <th>Max Score</th>
                                    <th>Percentage</th>
                                    <th>Weight</th>
                                    <th>Date Given</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($individual_grades as $grade): 
                                    $grade_percentage = round(($grade['score'] / $grade['max_score']) * 100, 1);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                                    <td><?php echo ucfirst($grade['assignment_type']); ?></td>
                                    <td><?php echo $grade['score']; ?></td>
                                    <td><?php echo $grade['max_score']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $grade_percentage >= 60 ? '#48bb78' : '#f56565'; ?>;">
                                            <?php echo $grade_percentage; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo $grade['weight']; ?></td>
                                    <td><?php echo $grade['date_given']; ?></td>
                                    <td><?php echo htmlspecialchars($grade['comments'] ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Calculate weighted average from individual grades -->
                        <?php
                        $total_weighted = 0;
                        $total_weight = 0;
                        foreach ($individual_grades as $grade) {
                            $grade_percentage = ($grade['score'] / $grade['max_score']) * 100;
                            $total_weighted += $grade_percentage * $grade['weight'];
                            $total_weight += $grade['weight'];
                        }
                        $calculated_avg = $total_weight > 0 ? round($total_weighted / $total_weight, 2) : 0;
                        ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f7fafc; border-radius: 8px;">
                            <strong>Weighted Average: <?php echo $calculated_avg; ?>%</strong>
                        </div>
                    <?php else: ?>
                        <p style="color: #718096; font-style: italic; padding: 10px;">No individual grades recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-students">
            <h3>👥 No Students Enrolled</h3>
            <p>There are currently no students enrolled in this course.</p>
            <a href="my_courses.php" class="view-grades-btn" style="margin-top: 20px; display: inline-block;">← Back to My Courses</a>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="my_courses.php" class="view-grades-btn">← Back to My Courses</a>
        <a href="grade_students.php?course_id=<?php echo $course_id; ?>" class="view-grades-btn btn-grade" style="margin-left: 10px;">✏️ Enter/Edit Grades</a>
    </div>
</div>

<script>
    function toggleGrades(studentId) {
        var gradesDiv = document.getElementById('grades-' + studentId);
        var allButtons = document.getElementsByClassName('expand-btn');
        
        if (gradesDiv.style.display === 'none' || gradesDiv.style.display === '') {
            gradesDiv.style.display = 'block';
        } else {
            gradesDiv.style.display = 'none';
        }
    }
    
    // Check if URL has a student anchor
    if (window.location.hash) {
        var studentId = window.location.hash.replace('#student-', '');
        if (studentId) {
            setTimeout(function() {
                var gradesDiv = document.getElementById('grades-' + studentId);
                if (gradesDiv) {
                    gradesDiv.style.display = 'block';
                    document.getElementById('student-' + studentId).scrollIntoView({ behavior: 'smooth' });
                }
            }, 500);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>