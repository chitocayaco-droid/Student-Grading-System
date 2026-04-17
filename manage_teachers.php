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

// Handle username change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_username'])) {
    $teacher_id = $_POST['teacher_id'];
    $new_username = strtolower(trim($_POST['new_username']));
    
    // Get user_id from teacher
    $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    if ($teacher) {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$new_username, $teacher['user_id']]);
        
        if ($stmt->fetch()) {
            $error = "Username already exists! Please choose a different username.";
        } elseif (strlen($new_username) < 3) {
            $error = "Username must be at least 3 characters long!";
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $new_username)) {
            $error = "Username can only contain letters, numbers, dots, underscores, and hyphens!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$new_username, $teacher['user_id']]);
            $success = "Username updated successfully!";
        }
    }
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
        }
        h2 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #fbbf24;
            color: #1332bd;
        }
        .btn-danger {
            background: #e53e3e;
        }
        .btn-danger:hover {
            background: #c53030;
            color: white;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
            color: #ffe96d;
        }
        .btn-sm {
            padding: 10px 20px;
            font-size: 14px;
            margin-left: 5px;
        }

        .username-edit-btn {
            background: #4299e1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .username-edit-btn:hover {
            background: #3182ce;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #345dce;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover {
            background: hsla(54, 100%, 96%, 0.69);
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

        /* Profile Image Styles */
        .profile-thumb {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        .profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            margin: 10px auto;
            display: block;
        }
        .image-upload-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .upload-btn {
            background: #4299e1;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
        }
        .upload-btn:hover {
            background: #3182ce;
            color: #ffe96d;
        }
        #profile_image {
            display: none;
        }
        .file-name {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }
        .remove-image {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 5px;
        }
        .remove-image:hover {
            background: #c53030;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>🍎 Manage Teachers</h2>
                <button class="btn btn-success" onclick="openAddModal()">+ Add New Teacher</button>
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
                        <td>
                            <span id="username-display-<?php echo $teacher['id']; ?>">
                                <?php echo htmlspecialchars($teacher['username']); ?>
                            </span>
                            <button class="btn btn-sm" style="background: #4299e1; padding: 4px 8px; font-size: 11px; margin-left: 5px;" 
                                    onclick="openUsernameModal(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['username']); ?>')">
                                ✏️
                            </button>
                        </td>
                        <td class="action-buttons" style="top: 20px;">
                            <button class="btn btn-sm" onclick='openEditModal(<?php echo json_encode($teacher); ?>)'>Edit</button>
                            <a href="?delete=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
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
    <!-- Username Change Modal -->
    <div id="usernameModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="closeUsernameModal()">&times;</span>
            <h3>Change Username</h3>
            
            <form method="POST" action="" onsubmit="return submitUsernameChange()">
                <input type="hidden" name="teacher_id" id="username_teacher_id">
                <input type="hidden" name="change_username" value="1">
                
                <div class="form-group">
                    <label>New Username:</label>
                    <input type="text" name="new_username" id="new_username_input" 
                        pattern="[a-zA-Z0-9._-]{3,}" 
                        required>
                    <small style="color: #718096; font-size: 11px; display: block; margin-top: 5px;">
                        Username must be at least 3 characters. Only letters, numbers, dots, underscores, and hyphens allowed.
                    </small>
                </div>
                
                <div class="action-buttons" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-danger" onclick="closeUsernameModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Username</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openUsernameModal(teacherId, currentUsername) {
        document.getElementById('username_teacher_id').value = teacherId;
        document.getElementById('new_username_input').value = currentUsername;
        document.getElementById('usernameModal').style.display = 'block';
    }

    function closeUsernameModal() {
        document.getElementById('usernameModal').style.display = 'none';
    }

    function submitUsernameChange() {
        var username = document.getElementById('new_username_input').value;
        
        if (username.length < 3) {
            alert('Username must be at least 3 characters long!');
            return false;
        }
        
        var usernameRegex = /^[a-zA-Z0-9._-]+$/;
        if (!usernameRegex.test(username)) {
            alert('Username can only contain letters, numbers, dots, underscores, and hyphens!');
            return false;
        }
        
        return confirm('Are you sure you want to change this teacher\'s username to "' + username + '"?');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('usernameModal');
        if (event.target == modal) {
            closeUsernameModal();
        }
    }
    </script>

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

<?php include 'includes/footer.php'; ?>