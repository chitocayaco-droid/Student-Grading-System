<?php
require_once 'config/database.php';
require_once 'includes/image_upload.php';

$page_title = "View Profile"; // Optional
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Initialize variables to prevent undefined errors
$profile = [];
$stats = [
    'course_count' => 0,
    'grade_count' => 0,
    'avg_grade' => 0,
    'student_count' => 0
];

try {
    // Get user details based on role
    if ($role == 'student') {
        $stmt = $pdo->prepare("
            SELECT s.*, u.username, u.full_name, u.created_at as account_created
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            // Get course count and average grade
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT e.course_id) as course_count,
                       COUNT(DISTINCT g.id) as grade_count,
                       COALESCE(AVG(g.score/g.max_score * 100), 0) as avg_grade
                FROM enrollments e
                LEFT JOIN grades g ON e.id = g.enrollment_id
                WHERE e.student_id = ?
            ");
            $stmt->execute([$profile['id']]);
            $stats = $stmt->fetch();
        }
        
    } else if ($role == 'teacher') {
        $stmt = $pdo->prepare("
            SELECT t.*, u.username, u.full_name, u.created_at as account_created
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            // Get course count and student count
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as course_count,
                       COUNT(DISTINCT e.student_id) as student_count
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                WHERE c.teacher_id = ?
            ");
            $stmt->execute([$profile['id']]);
            $teacher_stats = $stmt->fetch();
            $stats['course_count'] = $teacher_stats['course_count'] ?? 0;
            $stats['student_count'] = $teacher_stats['student_count'] ?? 0;
        }
        
    } else {
        // Admin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        // Get system stats for admin
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $stats['total_users'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $stats['total_students'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
        $stats['total_teachers'] = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Profile error: " . $e->getMessage());
}

// Handle own username change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_own_username'])) {
    $new_username = strtolower(trim($_POST['new_username']));
    
    // Validate
    if (strlen($new_username) < 3) {
        $error = "Username must be at least 3 characters long!";
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $new_username)) {
        $error = "Username can only contain letters, numbers, dots, underscores, and hyphens!";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$new_username, $user_id]);
        if ($stmt->fetch()) {
            $error = "Username already exists! Please choose a different username.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$new_username, $user_id]);
            $_SESSION['username'] = $new_username;
            $success = "Username changed successfully to: " . htmlspecialchars($new_username);
            $profile['username'] = $new_username;
        }
    }
}

// Handle reset profile image (add this BEFORE the update_profile code)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_image'])) {
    if ($role == 'student') {
        // Get current profile image
        $stmt = $pdo->prepare("SELECT profile_image FROM students WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current = $stmt->fetch();
        
        // Delete old image if exists and not default
        if ($current['profile_image'] && $current['profile_image'] != 'default.jpg') {
            $old_file = "uploads/profiles/" . $current['profile_image'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        // Set to NULL (will show default)
        $stmt = $pdo->prepare("UPDATE students SET profile_image = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $success = "Profile picture reset to default!";
        
        // Refresh profile data
        $profile['profile_image'] = null;
        
    } else if ($role == 'teacher') {
        // Get current profile image
        $stmt = $pdo->prepare("SELECT profile_image FROM teachers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current = $stmt->fetch();
        
        // Delete old image if exists and not default
        if ($current['profile_image'] && $current['profile_image'] != 'default.jpg') {
            $old_file = "uploads/profiles/" . $current['profile_image'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        // Set to NULL (will show default)
        $stmt = $pdo->prepare("UPDATE teachers SET profile_image = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $success = "Profile picture reset to default!";
        
        // Refresh profile data
        $profile['profile_image'] = null;
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        if ($role == 'student' && $profile) {
            $address = $_POST['address'] ?? '';
            
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $upload_result = uploadProfileImage($_FILES['profile_image']);
                if ($upload_result['success']) {
                    // Delete old image
                    if (!empty($profile['profile_image'])) {
                        deleteProfileImage($profile['profile_image']);
                    }
                    
                    // Update with new image
                    $stmt = $pdo->prepare("UPDATE students SET email = ?, phone = ?, address = ?, profile_image = ? WHERE id = ?");
                    $stmt->execute([$email, $phone, $address, $upload_result['filename'], $profile['id']]);
                    $success = "Profile updated successfully!";
                    
                    // Refresh profile data
                    $profile['profile_image'] = $upload_result['filename'];
                    $profile['email'] = $email;
                    $profile['phone'] = $phone;
                    $profile['address'] = $address;
                } else {
                    $error = $upload_result['message'];
                }
            } else {
                // Update without image change
                $stmt = $pdo->prepare("UPDATE students SET email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$email, $phone, $address, $profile['id']]);
                $success = "Profile updated successfully!";
                
                // Refresh profile data
                $profile['email'] = $email;
                $profile['phone'] = $phone;
                $profile['address'] = $address;
            }
        } else if ($role == 'teacher' && $profile) {
            $department = $_POST['department'] ?? '';
            
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $upload_result = uploadProfileImage($_FILES['profile_image']);
                if ($upload_result['success']) {
                    // Delete old image
                    if (!empty($profile['profile_image'])) {
                        deleteProfileImage($profile['profile_image']);
                    }
                    
                    // Update with new image
                    $stmt = $pdo->prepare("UPDATE teachers SET email = ?, phone = ?, department = ?, profile_image = ? WHERE id = ?");
                    $stmt->execute([$email, $phone, $department, $upload_result['filename'], $profile['id']]);
                    $success = "Profile updated successfully!";
                    
                    // Refresh profile data
                    $profile['profile_image'] = $upload_result['filename'];
                    $profile['email'] = $email;
                    $profile['phone'] = $phone;
                    $profile['department'] = $department;
                } else {
                    $error = $upload_result['message'];
                }
            } else {
                // Update without image change
                $stmt = $pdo->prepare("UPDATE teachers SET email = ?, phone = ?, department = ? WHERE id = ?");
                $stmt->execute([$email, $phone, $department, $profile['id']]);
                $success = "Profile updated successfully!";
                
                // Refresh profile data
                $profile['email'] = $email;
                $profile['phone'] = $phone;
                $profile['department'] = $department;
            }
        }
    } catch (Exception $e) {
        $error = "An error occurred while updating profile.";
        error_log("Profile update error: " . $e->getMessage());
    }
}

// Check if profile exists
if (!$profile) {
    die("Profile not found. Please contact administrator.");
}

// Function to get grade letter
function getGradeLetter($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}

// In the PHP section, update the profile_image_url:
$profile_image_url = isset($profile['profile_image']) && !empty($profile['profile_image']) && file_exists("uploads/profiles/" . $profile['profile_image']) 
    ? "uploads/profiles/" . $profile['profile_image'] 
    : "uploads/profiles/default.jpg"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Grading System</title>
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
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
        }
        .profile-header {
            background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            padding: 40px;
            text-align: center;
            color: white;
            position: relative;
        }
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            object-fit: cover;
            margin-bottom: 20px;
            background: white;
        }
        .edit-image-btn {
            position: absolute;
            bottom: 20px;
            right: 0;
            background: #48bb78;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid white;
            font-size: 20px;
            transition: background 0.3s;
        }
        .edit-image-btn:hover {
            background: #38a169;
        }
        .profile-name {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
            color: #ffcd4d;
        }
        .profile-role {
            font-size: 1.2em;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 5px 20px;
            border-radius: 20px;
        }
        .profile-body {
            padding: 40px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8fafc;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }
        .info-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 1.2em;
        }
        .info-item {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
            width: 120px;
            font-size: 0.9em;
            flex-shrink: 0;
        }
        .info-value {
            color: #2d3748;
            flex: 1;
            word-break: break-word;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 0px 20px rgba(241, 206, 108, 0.62);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #ffcd4d;
            line-height: 1;
        }
        .stat-label {
            color: #718096;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .stat-unit {
            font-size: 0.5em;
            color: #a0aec0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #fbbf24;
            color: #1332bd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-outline {
            background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            border: 2px solid linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            color: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
        }
        .btn-outline:hover {
            background: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
            color: #ffe96d;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
            color: #ffe96d;
        }
        .btn-danger {
            background: #e53e3e;
        }
        .btn-danger:hover {
            background: #c53030;
            color: white;
        }
        .logout {
            color: #e53e3e !important;
        }
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }
        
        /* Edit Modal */
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
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            position: relative;
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
            margin-bottom: 20px;
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
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
        }
        .image-upload-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .upload-btn {
            background: #4299e1;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
        }
        #edit_profile_image {
            display: none;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-grade-a { background: #c6f6d5; color: #22543d; }
        .badge-grade-b { background: #bee3f8; color: #2c5282; }
        .badge-grade-c { background: #feebc8; color: #7b341e; }
        .badge-grade-d { background: #fed7d7; color: #742a2a; }
        .badge-grade-f { background: #fed7d7; color: #742a2a; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-image-container">
                <?php if ($role == 'student' || $role == 'teacher'): ?>   
                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" 
                        alt="Profile" 
                        class="profile-image"
                        data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                        onerror="this.src='uploads/profiles/default.jpg'">
                    <div class="edit-image-btn" onclick="openEditModal()">✎</div>
                <?php endif; ?>
                </div>
                <div class="profile-name">
                    <?php 
                    if ($role == 'student' || $role == 'teacher') {
                        $first_name = $profile['first_name'] ?? '';
                        $last_name = $profile['last_name'] ?? '';
                        echo htmlspecialchars(trim($first_name . ' ' . $last_name));
                    } else {
                        echo htmlspecialchars($profile['full_name'] ?? 'Admin');
                    }
                    ?>
                </div>
                <div class="profile-role"><?php echo ucfirst($role); ?></div>
            </div>
            
            <div class="profile-body">
                <!-- Statistics Section -->
                <div class="stats-grid">
                    <?php if ($role == 'student'): ?>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['course_count'] ?? 0); ?></div>
                            <div class="stat-label">Courses Enrolled</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['grade_count'] ?? 0); ?></div>
                            <div class="stat-label">Grades Received</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number">
                                <?php 
                                $avg_grade = floatval($stats['avg_grade'] ?? 0);
                                echo number_format($avg_grade, 1); ?><span class="stat-unit">%</span>
                            </div>
                            <div class="stat-label">
                                Average Grade
                                <?php if ($avg_grade > 0): ?>
                                    <span class="badge badge-grade-<?php echo strtolower(getGradeLetter($avg_grade)); ?>" style="margin-left: 5px;">
                                        <?php echo getGradeLetter($avg_grade); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($role == 'teacher'): ?>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['course_count'] ?? 0); ?></div>
                            <div class="stat-label">Courses Teaching</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['student_count'] ?? 0); ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number">
                                <?php 
                                // Get total grades given
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT COUNT(*) as count 
                                        FROM grades g
                                        JOIN enrollments e ON g.enrollment_id = e.id
                                        JOIN courses c ON e.course_id = c.id
                                        WHERE c.teacher_id = ?
                                    ");
                                    $stmt->execute([$profile['id'] ?? 0]);
                                    $grade_count = $stmt->fetchColumn();
                                    echo intval($grade_count);
                                } catch (Exception $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="stat-label">Grades Given</div>
                        </div>
                    <?php else: ?>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['total_users'] ?? 0); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['total_students'] ?? 0); ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo intval($stats['total_teachers'] ?? 0); ?></div>
                            <div class="stat-label">Teachers</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Information Grid -->
                <div class="info-grid">
                    <!-- Personal Information -->
                    <div class="info-section">
                        <h3>📋 Personal Information</h3>
                        <?php if ($role == 'student' || $role == 'teacher'): ?>
                            <div class="info-item">
                                <span class="info-label">ID:</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile[$role . '_id'] ?? 'N/A'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['username'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <?php if ($role == 'student' || $role == 'teacher'): ?>
                            <div class="info-item">
                                <span class="info-label">First Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['first_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['last_name'] ?? 'N/A'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['email'] ?? 'Not provided'); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        
                        <?php if ($role == 'student'): ?>
                            <div class="info-item">
                                <span class="info-label">Address:</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['address'] ?? 'Not provided'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Enrolled:</span>
                                <span class="info-value"><?php echo isset($profile['enrollment_date']) ? date('F j, Y', strtotime($profile['enrollment_date'])) : 'N/A'; ?></span>
                            </div>
                        <?php elseif ($role == 'teacher'): ?>
                            <div class="info-item">
                                <span class="info-label">Department:</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['department'] ?? 'Not assigned'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <span class="info-label">Account Created:</span>
                            <span class="info-value"><?php echo isset($profile['account_created']) ? date('F j, Y', strtotime($profile['account_created'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                    
                    <!-- Account Activity -->
                    <div class="info-section">
                        <h3>📊 Account Activity</h3>
                        <?php if ($role == 'student'): ?>
                            <div class="info-item">
                                <span class="info-label">Courses:</span>
                                <span class="info-value"><?php echo intval($stats['course_count'] ?? 0); ?> enrolled</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Assignments:</span>
                                <span class="info-value"><?php echo intval($stats['grade_count'] ?? 0); ?> graded</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Performance:</span>
                                <span class="info-value">
                                    <?php 
                                    $avg_grade = floatval($stats['avg_grade'] ?? 0);
                                    if ($avg_grade > 0): ?>
                                        <span style="color: <?php echo $avg_grade >= 60 ? '#48bb78' : '#e53e3e'; ?>;">
                                            <?php echo number_format($avg_grade, 1); ?>% average
                                        </span>
                                    <?php else: ?>
                                        No grades yet
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php elseif ($role == 'teacher'): ?>
                            <div class="info-item">
                                <span class="info-label">Active Courses:</span>
                                <span class="info-value"><?php echo intval($stats['course_count'] ?? 0); ?> courses</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Students:</span>
                                <span class="info-value"><?php echo intval($stats['student_count'] ?? 0); ?> total</span>
                            </div>
                        <?php else: ?>
                            <div class="info-item">
                                <span class="info-label">System Users:</span>
                                <span class="info-value"><?php echo intval($stats['total_users'] ?? 0); ?> total</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Students:</span>
                                <span class="info-value"><?php echo intval($stats['total_students'] ?? 0); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Teachers:</span>
                                <span class="info-value"><?php echo intval($stats['total_teachers'] ?? 0); ?></span>
                            </div>
                        <?php endif; ?>             
                        <div style="margin-top: 20px;">
                            <button class="btn btn-outline" onclick="openEditModal()" style="width: 100%;">✎ Edit Profile</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Profile</h3>
            
            <!-- Form for updating profile info -->
            <form method="POST" action="" enctype="multipart/form-data" id="editProfileForm">
                <!-- Profile Image Upload -->
                <?php if ($role == 'student' || $role == 'teacher'): ?>
                <div class="image-upload-container">
                    <img id="editImagePreview" 
                        src="<?php echo htmlspecialchars($profile_image_url); ?>" 
                        alt="Profile Preview" 
                        style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea; margin-bottom: 10px;">
                    
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <input type="file" id="edit_profile_image" name="profile_image" accept="image/*" onchange="previewEditImage(this)" style="display: none;">
                        <label for="edit_profile_image" class="upload-btn" style="background: #3b82f6; cursor: pointer; padding: 8px 16px; border-radius: 5px; color: white; display: inline-block;">
                            📷 Change Photo
                        </label>
                        <!-- Separate form for resetting image (only shown if custom image exists) -->
                        <?php if (!empty($profile['profile_image']) && $profile['profile_image'] != 'default.jpg' && $profile['profile_image'] != 'default-avatar.png'): ?>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reset your profile picture to the default image?')">
                                <input type="hidden" name="reset_image" value="1">
                                <button type="submit" class="upload-btn" style="background: #f59e0b;">
                                    🔄 Reset Profile Picture
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                </div>
                
                <?php if ($role == 'student'): ?>
                    <div class="form-group">
                        <label>Address:</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                <?php elseif ($role == 'teacher'): ?>
                    <div class="form-group">
                        <label>Department:</label>
                        <input type="text" name="department" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function previewEditImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editImagePreview').src = e.target.result;
                    document.getElementById('profileImageDisplay').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeProfileImage() {
            document.getElementById('editImagePreview').src = 'uploads/profiles/default.jpg';
            document.getElementById('profileImageDisplay').src = 'uploads/profiles/default.jpg';
            document.getElementById('edit_profile_image').value = '';
        }
        
        // Reset profile image to default
        function resetToDefault() {
            if (confirm('Are you sure you want to reset your profile picture to the default image?')) {
                // Show loading state
                const resetBtn = event.target;
                const originalText = resetBtn.innerHTML;
                resetBtn.innerHTML = '⏳ Resetting...';
                resetBtn.disabled = true;
                
                // Create form data
                const formData = new FormData();
                formData.append('reset_image', '1');
                
                // Send AJAX request
                fetch('reset_profile_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the image preview
                        document.getElementById('editImagePreview').src = 'uploads/profiles/default.jpg?' + new Date().getTime();
                        document.getElementById('profileImageDisplay').src = 'uploads/profiles/default.jpg?' + new Date().getTime();
                        
                        // Show success message
                        const statusDiv = document.getElementById('imageStatus');
                        statusDiv.innerHTML = '✅ Profile picture reset to default!';
                        statusDiv.style.color = '#10b981';
                        
                        // Hide the reset button
                        resetBtn.style.display = 'none';
                        
                        // Add hidden field to form
                        if (!document.getElementById('reset_image')) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'reset_image';
                            input.id = 'reset_image';
                            input.value = '1';
                            document.getElementById('editProfileForm').appendChild(input);
                        }
                        
                        setTimeout(() => {
                            statusDiv.innerHTML = '';
                        }, 3000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    resetBtn.innerHTML = originalText;
                    resetBtn.disabled = false;
                });
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>