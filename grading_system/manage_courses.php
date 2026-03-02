<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle Add/Edit Course
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Add new course
        if ($_POST['action'] == 'add') {
            $course_code = $_POST['course_code'];
            $course_name = $_POST['course_name'];
            $credits = $_POST['credits'];
            $teacher_id = $_POST['teacher_id'] ?: null;
            $description = $_POST['description'];
            
            $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, credits, teacher_id, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$course_code, $course_name, $credits, $teacher_id, $description]);
            
            $success = "Course added successfully!";
        }
        
        // Edit course
        if ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $course_code = $_POST['course_code'];
            $course_name = $_POST['course_name'];
            $credits = $_POST['credits'];
            $teacher_id = $_POST['teacher_id'] ?: null;
            $description = $_POST['description'];
            
            $stmt = $pdo->prepare("UPDATE courses SET course_code=?, course_name=?, credits=?, teacher_id=?, description=? WHERE id=?");
            $stmt->execute([$course_code, $course_name, $credits, $teacher_id, $description, $id]);
            
            $success = "Course updated successfully!";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id=?");
    $stmt->execute([$id]);
    $success = "Course deleted successfully!";
}

// Get all courses with teacher names
$stmt = $pdo->query("
    SELECT c.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name 
    FROM courses c 
    LEFT JOIN teachers t ON c.teacher_id = t.id 
    ORDER BY c.course_code
");
$courses = $stmt->fetchAll();

// Get all teachers for dropdown
$stmt = $pdo->query("SELECT id, first_name, last_name, teacher_id FROM teachers ORDER BY last_name, first_name");
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Grading System</title>
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
        }
        h2 {
            color: #2d3748;
            margin-bottom: 20px;
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
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            width: 500px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            position: relative;
        }
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
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
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .enroll-btn {
            background: #4299e1;
        }
        .enroll-btn:hover {
            background: #3182ce;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <h2>Student Grading System</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_students.php">Students</a>
                <a href="manage_teachers.php">Teachers</a>
                <a href="manage_courses.php">Courses</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Manage Courses</h2>
                <button class="btn btn-success" onclick="openAddModal()">Add New Course</button>
            </div>
            
            <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Credits</th>
                        <th>Teacher</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo $course['credits']; ?></td>
                        <td><?php echo htmlspecialchars($course['teacher_name'] ?: 'Not Assigned'); ?></td>
                        <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . '...'; ?></td>
                        <td class="action-buttons">
                            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">Edit</button>
                            <a href="?delete=<?php echo $course['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure? This will also delete all enrollments and grades for this course.')">Delete</a>
                            <a href="manage_enrollments.php?course_id=<?php echo $course['id']; ?>" class="btn enroll-btn">Enrollments</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="courseModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Add New Course</h3>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="courseId">
                
                <div class="form-group">
                    <label>Course Code:</label>
                    <input type="text" name="course_code" id="course_code" required>
                </div>
                
                <div class="form-group">
                    <label>Course Name:</label>
                    <input type="text" name="course_name" id="course_name" required>
                </div>
                
                <div class="form-group">
                    <label>Credits:</label>
                    <input type="number" name="credits" id="credits" min="1" max="6" required>
                </div>
                
                <div class="form-group">
                    <label>Assign Teacher:</label>
                    <select name="teacher_id" id="teacher_id">
                        <option value="">-- Select Teacher --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>">
                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['teacher_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" id="description" rows="4"></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">Save Course</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Course';
            document.getElementById('action').value = 'add';
            document.getElementById('courseId').value = '';
            document.getElementById('course_code').value = '';
            document.getElementById('course_name').value = '';
            document.getElementById('credits').value = '3';
            document.getElementById('teacher_id').value = '';
            document.getElementById('description').value = '';
            
            document.getElementById('courseModal').style.display = 'block';
        }
        
        function openEditModal(course) {
            document.getElementById('modalTitle').textContent = 'Edit Course';
            document.getElementById('action').value = 'edit';
            document.getElementById('courseId').value = course.id;
            document.getElementById('course_code').value = course.course_code;
            document.getElementById('course_name').value = course.course_name;
            document.getElementById('credits').value = course.credits;
            document.getElementById('teacher_id').value = course.teacher_id || '';
            document.getElementById('description').value = course.description || '';
            
            document.getElementById('courseModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('courseModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('courseModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>