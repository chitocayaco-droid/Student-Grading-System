<?php
require_once 'config/database.php';
require_once 'includes/image_upload.php';

$page_title = "Manage Teachers"; // Optional
include 'includes/header.php';

// ... (rest of the PHP code, similar to students but with teacher fields)

// In the POST handling for add/edit, add image upload similar to students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle Add/Edit Teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Add new teacher
        if ($_POST['action'] == 'add') {
            $teacher_id = $_POST['teacher_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $department = $_POST['department'];
            
            // Create user account
            $username = strtolower($first_name . '.' . $last_name);
            $password = md5('teacher123'); // Default password
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'teacher')");
            $stmt->execute([$username, $password, $first_name . ' ' . $last_name]);
            $user_id = $pdo->lastInsertId();
            
            // Create teacher record
            $stmt = $pdo->prepare("INSERT INTO teachers (user_id, teacher_id, first_name, last_name, email, phone, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $teacher_id, $first_name, $last_name, $email, $phone, $department]);
            
            $success = "Teacher added successfully! Username: $username, Password: teacher123";
        }
        
        // Edit teacher
        if ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $teacher_id = $_POST['teacher_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $department = $_POST['department'];
            
            $stmt = $pdo->prepare("UPDATE teachers SET teacher_id=?, first_name=?, last_name=?, email=?, phone=?, department=? WHERE id=?");
            $stmt->execute([$teacher_id, $first_name, $last_name, $email, $phone, $department, $id]);
            
            // Update user's full name
            $stmt = $pdo->prepare("UPDATE users SET full_name=? WHERE id=(SELECT user_id FROM teachers WHERE id=?)");
            $stmt->execute([$first_name . ' ' . $last_name, $id]);
            
            $success = "Teacher updated successfully!";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get user_id before deleting teacher
    $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id=?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();
    
    // Delete teacher
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE id=?");
    $stmt->execute([$id]);
    
    // Delete user
    if ($teacher) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$teacher['user_id']]);
    }
    
    $success = "Teacher deleted successfully!";
}

// Get all teachers
$stmt = $pdo->query("SELECT t.*, u.username FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY t.last_name, t.first_name");
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Grading System</title>
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
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Manage Teachers</h2>
                <button class="btn btn-success" onclick="openAddModal()">Add New Teacher</button>
            </div>
            
            <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Teacher ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['last_name'] . ', ' . $teacher['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td class="action-buttons">
                            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">Edit</button>
                            <a href="?delete=<?php echo $teacher['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="teacherModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Add New Teacher</h3>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="teacherId">
                
                <div class="form-group">
                    <label>Teacher ID:</label>
                    <input type="text" name="teacher_id" id="teacher_id" required>
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
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" id="phone">
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" id="department" required>
                </div>
                
                <button type="submit" class="btn btn-success">Save Teacher</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Teacher';
            document.getElementById('action').value = 'add';
            document.getElementById('teacherId').value = '';
            document.getElementById('teacher_id').value = '';
            document.getElementById('first_name').value = '';
            document.getElementById('last_name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('department').value = '';
            
            document.getElementById('teacherModal').style.display = 'block';
        }
        
        function openEditModal(teacher) {
            document.getElementById('modalTitle').textContent = 'Edit Teacher';
            document.getElementById('action').value = 'edit';
            document.getElementById('teacherId').value = teacher.id;
            document.getElementById('teacher_id').value = teacher.teacher_id;
            document.getElementById('first_name').value = teacher.first_name;
            document.getElementById('last_name').value = teacher.last_name;
            document.getElementById('email').value = teacher.email;
            document.getElementById('phone').value = teacher.phone;
            document.getElementById('department').value = teacher.department;
            
            document.getElementById('teacherModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('teacherModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('teacherModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>