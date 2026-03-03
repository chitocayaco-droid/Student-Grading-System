<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle Add/Edit Student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Add new student
        if ($_POST['action'] == 'add') {
            $student_id = $_POST['student_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $enrollment_date = $_POST['enrollment_date'];
            
            // First create user account
            $username = strtolower($first_name . '.' . $last_name);
            $password = md5('student123'); // Default password
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$username, $password, $first_name . ' ' . $last_name]);
            $user_id = $pdo->lastInsertId();
            
            // Then create student record
            $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, email, phone, address, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $student_id, $first_name, $last_name, $email, $phone, $address, $enrollment_date]);
            
            $success = "Student added successfully! Username: $username, Password: student123";
        }
        
        // Edit student
        if ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $student_id = $_POST['student_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $enrollment_date = $_POST['enrollment_date'];
            
            $stmt = $pdo->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, email=?, phone=?, address=?, enrollment_date=? WHERE id=?");
            $stmt->execute([$student_id, $first_name, $last_name, $email, $phone, $address, $enrollment_date, $id]);
            
            // Update user's full name
            $stmt = $pdo->prepare("UPDATE users SET full_name=? WHERE id=(SELECT user_id FROM students WHERE id=?)");
            $stmt->execute([$first_name . ' ' . $last_name, $id]);
            
            $success = "Student updated successfully!";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get user_id before deleting student
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id=?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    // Delete student (will cascade to user due to foreign key)
    $stmt = $pdo->prepare("DELETE FROM students WHERE id=?");
    $stmt->execute([$id]);
    
    // Delete user
    if ($student) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$student['user_id']]);
    }
    
    $success = "Student deleted successfully!";
}

// Get all students
$stmt = $pdo->query("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.last_name, s.first_name");
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Grading System</title>
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
        .form-group input, .form-group textarea {
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
                <h2>Manage Students</h2>
                <button class="btn btn-success" onclick="openAddModal()">Add New Student</button>
            </div>
            
            <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Enrollment Date</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td><?php echo $student['enrollment_date']; ?></td>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td class="action-buttons">
                            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>
                            <a href="?delete=<?php echo $student['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Add New Student</h3>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="studentId">
                
                <div class="form-group">
                    <label>Student ID:</label>
                    <input type="text" name="student_id" id="student_id" required>
                </div>
                
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="email">
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" id="phone">
                </div>
                
                <div class="form-group">
                    <label>Address:</label>
                    <textarea name="address" id="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Enrollment Date:</label>
                    <input type="date" name="enrollment_date" id="enrollment_date" required>
                </div>
                
                <button type="submit" class="btn btn-success">Save Student</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Student';
            document.getElementById('action').value = 'add';
            document.getElementById('studentId').value = '';
            document.getElementById('student_id').value = '';
            document.getElementById('first_name').value = '';
            document.getElementById('last_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('address').value = '';
            document.getElementById('enrollment_date').value = '<?php echo date('Y-m-d'); ?>';
            
            document.getElementById('studentModal').style.display = 'block';
        }
        
        function openEditModal(student) {
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('action').value = 'edit';
            document.getElementById('studentId').value = student.id;
            document.getElementById('student_id').value = student.student_id;
            document.getElementById('first_name').value = student.first_name;
            document.getElementById('last_name').value = student.last_name;
            document.getElementById('email').value = student.email;
            document.getElementById('phone').value = student.phone;
            document.getElementById('address').value = student.address;
            document.getElementById('enrollment_date').value = student.enrollment_date;
            
            document.getElementById('studentModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('studentModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('studentModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>