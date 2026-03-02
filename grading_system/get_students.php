<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    exit();
}

$course_id = $_GET['course_id'];

// Get enrolled students with their current grades
$stmt = $pdo->prepare("
    SELECT e.id as enrollment_id, s.student_id, s.first_name, s.last_name,
           s.email, c.course_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    WHERE e.course_id = ?
    ORDER BY s.last_name, s.first_name
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();

if (count($students) > 0):
?>
    <h3>Students in <?php echo htmlspecialchars($students[0]['course_name']); ?></h3>
    
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo $student['student_id']; ?></td>
                <td><?php echo $student['last_name'] . ', ' . $student['first_name']; ?></td>
                <td><?php echo $student['email']; ?></td>
                <td>
                    <button onclick="showGradeForm(<?php echo $student['enrollment_id']; ?>)">Add Grade</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div id="grade-form-<?php echo $student['enrollment_id']; ?>" class="grade-form" style="display: none;">
        <h4>Add Grade</h4>
        <form method="POST" action="grade_students.php">
            <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
            
            <div>
                <label>Assignment Name:</label>
                <input type="text" name="assignment_name" required>
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
            
            <div>
                <label>Score:</label>
                <input type="number" step="0.01" name="score" required>
            </div>
            
            <div>
                <label>Max Score:</label>
                <input type="number" step="0.01" name="max_score" required>
            </div>
            
            <div>
                <label>Weight:</label>
                <input type="number" step="0.01" name="weight" value="1.00" required>
            </div>
            
            <button type="submit" name="submit_grade" class="btn">Submit Grade</button>
        </form>
    </div>
    
    <script>
    function showGradeForm(enrollmentId) {
        var form = document.getElementById('grade-form-' + enrollmentId);
        form.style.display = 'block';
    }
    </script>
<?php else: ?>
    <p>No students enrolled in this course.</p>
<?php endif; ?>