<?php
require_once 'config/database.php';
require_once 'includes/image_upload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle Add/Edit Student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Handle profile image upload
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_result = uploadProfileImage($_FILES['profile_image']);
            if ($upload_result['success']) {
                $profile_image = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }
        
        // Add new student
        if ($_POST['action'] == 'add' && !isset($error)) {
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
            
            // Then create student record with profile image
            $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, email, phone, address, enrollment_date, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $student_id, $first_name, $last_name, $email, $phone, $address, $enrollment_date, $profile_image]);
            
            $success = "Student added successfully! Username: $username, Password: student123";
        }
        
        // Edit student
        if ($_POST['action'] == 'edit' && !isset($error)) {
            $id = $_POST['id'];
            $student_id = $_POST['student_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $enrollment_date = $_POST['enrollment_date'];
            
            // Get current profile image
            $stmt = $pdo->prepare("SELECT profile_image FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            
            // If new image uploaded, delete old one
            if ($profile_image && $current['profile_image']) {
                deleteProfileImage($current['profile_image']);
            } elseif (!$profile_image) {
                $profile_image = $current['profile_image']; // Keep existing image
            }
            
            $stmt = $pdo->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, email=?, phone=?, address=?, enrollment_date=?, profile_image=? WHERE id=?");
            $stmt->execute([$student_id, $first_name, $last_name, $email, $phone, $address, $enrollment_date, $profile_image, $id]);
            
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
    
    // Get profile image and user_id before deleting
    $stmt = $pdo->prepare("SELECT profile_image, user_id FROM students WHERE id=?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    // Delete profile image
    if ($student && $student['profile_image']) {
        deleteProfileImage($student['profile_image']);
    }
    
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
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #4a5568;
            padding: 8px 12px;
            border-radius: 5px;
        }
        .nav-links a:hover {
            background: #edf2f7;
        }
        .nav-links a.active {
            background: #667eea;
            color: white;
        }
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
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
            vertical-align: middle;
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
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 12px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #718096;
        }
        .close:hover {
            color: #2d3748;
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
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
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
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            border: 4px solid #667eea;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <h2>📚 Student Grading System</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_students.php" class="active">Students</a>
                <a href="manage_teachers.php">Teachers</a>
                <a href="manage_courses.php">Courses</a>
                <a href="manage_passwords.php">Password Management</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>👥 Manage Students</h2>
                <button class="btn btn-success" onclick="openAddModal()">+ Add New Student</button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
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
                        <td>
                            <img src="<?php echo getProfileImageUrl($student['profile_image']); ?>" 
                                 alt="Profile" 
                                 class="profile-thumb"
                                 onerror="this.src='uploads/profiles/default-avatar.png'">
                        </td>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td><?php echo $student['enrollment_date']; ?></td>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-sm" onclick='openEditModal(<?php echo json_encode($student); ?>)'>Edit</button>
                            <a href="?delete=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
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
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="id" id="studentId">
                
                <!-- Profile Image Upload -->
                <div class="image-upload-container">
                    <img id="profilePreview" src="uploads/profiles/default-avatar.png" alt="Profile Preview" class="profile-preview">
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this)">
                    <label for="profile_image" class="upload-btn">Choose Profile Photo</label>
                    <div id="fileName" class="file-name">No file chosen</div>
                    <button type="button" class="remove-image" onclick="removeImage()" style="display: none;">Remove Image</button>
                </div>
                
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
        let currentProfileImage = null;
        
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
            
            // Reset image preview
            document.getElementById('profilePreview').src = 'uploads/profiles/default-avatar.png';
            document.getElementById('fileName').textContent = 'No file chosen';
            document.getElementById('profile_image').value = '';
            document.querySelector('.remove-image').style.display = 'none';
            currentProfileImage = null;
            
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
            
            // Set profile image preview
            if (student.profile_image) {
                document.getElementById('profilePreview').src = 'uploads/profiles/' + student.profile_image;
                document.querySelector('.remove-image').style.display = 'inline-block';
                currentProfileImage = student.profile_image;
            } else {
                document.getElementById('profilePreview').src = 'uploads/profiles/default-avatar.png';
                document.querySelector('.remove-image').style.display = 'none';
            }
            document.getElementById('fileName').textContent = currentProfileImage ? 'Current: ' + currentProfileImage : 'No file chosen';
            document.getElementById('profile_image').value = '';
            
            document.getElementById('studentModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('studentModal').style.display = 'none';
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                    document.getElementById('fileName').textContent = 'Selected: ' + input.files[0].name;
                    document.querySelector('.remove-image').style.display = 'inline-block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeImage() {
            document.getElementById('profilePreview').src = 'uploads/profiles/default-avatar.png';
            document.getElementById('profile_image').value = '';
            document.getElementById('fileName').textContent = 'Image will be removed';
            document.querySelector('.remove-image').style.display = 'none';
            
            // If editing, we need to mark for deletion
            if (document.getElementById('action').value === 'edit') {
                // You could add a hidden field to indicate image deletion
                // For now, we'll just set a flag
                if (!document.getElementById('delete_image')) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_image';
                    input.id = 'delete_image';
                    input.value = '1';
                    document.querySelector('form').appendChild(input);
                }
            }
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