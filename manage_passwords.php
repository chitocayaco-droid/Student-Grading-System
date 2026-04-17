<?php
require_once 'config/database.php';

$page_title = "Password Management"; // Optional
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = '';

// Handle password change for self
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_self_password'])) {
    $current_password = md5($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND password = ?");
    $stmt->execute([$_SESSION['user_id'], $current_password]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = md5($new_password);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $message = "Your password has been changed successfully!";
                $message_type = 'success';
            } else {
                $message = "New password must be at least 6 characters long!";
                $message_type = 'error';
            }
        } else {
            $message = "New passwords do not match!";
            $message_type = 'error';
        }
    } else {
        $message = "Current password is incorrect!";
        $message_type = 'error';
    }
}

// Handle username change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_username'])) {
    $user_id = $_POST['user_id'];
    $new_username = strtolower(trim($_POST['new_username']));
    
    if (strlen($new_username) < 3) {
        $message = "Username must be at least 3 characters long!";
        $message_type = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $new_username)) {
        $message = "Username can only contain letters, numbers, dots, underscores, and hyphens!";
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$new_username, $user_id]);
        if ($stmt->fetch()) {
            $message = "Username already exists!";
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$new_username, $user_id]);
            $message = "Username updated successfully!";
            $message_type = 'success';
        }
    }
}

// Handle password reset for other users
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_user_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        if (strlen($new_password) >= 6) {
            $hashed_password = md5($new_password);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Get user info for message
            $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $message = "Password for {$user['username']} ({$user['role']}) has been reset successfully!";
            $message_type = 'success';
        } else {
            $message = "New password must be at least 6 characters long!";
            $message_type = 'error';
        }
    } else {
        $message = "New passwords do not match!";
        $message_type = 'error';
    }
}

// Get all users for admin to manage (excluding current admin)
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name, u.role, u.created_at,
           CASE 
               WHEN u.role = 'student' THEN (SELECT student_id FROM students WHERE user_id = u.id)
               WHEN u.role = 'teacher' THEN (SELECT teacher_id FROM teachers WHERE user_id = u.id)
               ELSE 'N/A'
           END as identifier
    FROM users u
    WHERE u.id != ?
    ORDER BY u.role, u.full_name
");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();

// Get current admin info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Management - Grading System</title>
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
        }
        h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: linear-gradient(135deg, #305acf 0%, #2563eb 100%);
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
            transition: background 0.3s;
        }
        .btn:hover {
            background: #fbbf24;
            color: #1332bd;
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
        .btn-warning {
            background: #ecc94b;
            color: #744210;
        }
        .btn-warning:hover {
            background: #d69e2e;
            color: #ffef95;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #6082e0;
            color: #ffffff;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover {
            background: hsla(54, 100%, 96%, 0.69);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-admin {
            background: #fbb6ce;
            color: #97266d;
        }
        .badge-teacher {
            background: #bee3f8;
            color: #2c5282;
        }
        .badge-student {
            background: #c6f6d5;
            color: #22543d;
        }
        .password-reset-form {
            display: none;
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #e2e8f0;
        }
        .password-reset-form.active {
            display: block;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-box p {
            margin: 5px 0;
            color: #2c5282;
        }
        .logout {
            color: #e53e3e !important;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .strength-weak { color: #e53e3e; }
        .strength-medium { color: #ecc94b; }
        .strength-strong { color: #48bb78; }
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid-2">
            <!-- Change Own Password Section -->
            <div class="card">
                <h3>🔑 Change Your Password</h3>
                <div class="info-box">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($current_user['username']); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst($current_user['role']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($current_user['full_name']); ?></p>
                </div>
                
                <form method="POST" action="" onsubmit="return validatePasswordForm()">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required onkeyup="checkPasswordStrength()">
                        <div id="password-strength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required onkeyup="checkPasswordMatch()">
                        <div id="password-match" class="password-strength"></div>
                    </div>
                    
                    <button type="submit" name="change_self_password" class="btn btn-success">Change Password</button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f7fafc; border-radius: 8px;">
                    <h4 style="margin-bottom: 10px; color: #4a5568;">Password Requirements:</h4>
                    <ul style="list-style: none; color: #718096; font-size: 13px;">
                        <li>✓ Minimum 6 characters</li>
                        <li>✓ Use a mix of letters and numbers</li>
                        <li>✓ Avoid common passwords</li>
                    </ul>
                </div>
            </div>
            
            <!-- Quick Tips Card -->
            <div class="card">
                <h3>💡 Password Management Tips</h3>
                <div style="background: #f7fafc; padding: 20px; border-radius: 8px;">
                    <ul style="list-style: none; color: #4a5568;">
                        <li style="margin-bottom: 15px; display: flex; align-items: center;">
                            <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                            Never share passwords with anyone
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center;">
                            <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                            Use different passwords for different accounts
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center;">
                            <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                            Change passwords every 3 months
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center;">
                            <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                            Don't use personal information
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span style="background: #48bb78; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px;">✓</span>
                            Use password manager if possible
                        </li>
                    </ul>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <p style="color: #718096; font-size: 13px;">
                        Last password change: <?php echo date('F j, Y', strtotime($current_user['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Manage Other Users' Passwords -->
        <div class="card">
            <h3>👥 Manage Usernames and Passwords</h3>
            <p style="color: #718096; margin-bottom: 20px;">Reset passwords and change usernames for students and teachers. Default password is usually 'student123' or 'teacher123'.</p>
            
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>ID/Identifier</th>
                            <th>Account Created</th>
                            <th>Passwords</th>
                            <th>Usernames</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['identifier'] ?: 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="toggleResetForm(<?php echo $user['id']; ?>)">
                                    Reset Password
                                </button>
                                
                                <!-- Password Reset Form (Hidden by default) -->
                                <div id="reset-form-<?php echo $user['id']; ?>" class="password-reset-form">
                                    <form method="POST" action="" onsubmit="return validateResetForm(<?php echo $user['id']; ?>)">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label>New Password for <?php echo htmlspecialchars($user['username']); ?>:</label>
                                            <input type="password" name="new_password" id="reset-pass-<?php echo $user['id']; ?>" required minlength="6">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Confirm New Password:</label>
                                            <input type="password" name="confirm_password" id="reset-confirm-<?php echo $user['id']; ?>" required>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" name="reset_user_password" class="btn btn-success btn-sm">Save New Password</button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="toggleResetForm(<?php echo $user['id']; ?>)">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm" style="background: #4299e1; color: white;" onclick="toggleUsernameForm(<?php echo $user['id']; ?>)">
                                    Change Username
                                </button>
                                <div id="username-form-<?php echo $user['id']; ?>" class="password-reset-form" style="display: none;">
                                    <form method="POST" action="">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <div class="form-group">
                                            <label>New Username:</label>
                                            <input type="text" name="new_username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                pattern="[a-zA-Z0-9._-]{3,}" required>
                                        </div>
                                        <button type="submit" name="change_username" class="btn btn-success btn-sm">Save</button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="toggleUsernameForm(<?php echo $user['id']; ?>)">Cancel</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: #718096; background: #f7fafc; border-radius: 8px;">
                    No other users found in the system.
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Bulk Actions Card -->
        <div class="card">
            <h3>⚡ Quick Actions</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="bulk_password_reset.php?role=student" class="btn" onclick="return confirm('This will reset ALL student passwords to \'student123\'. Continue?')">Reset All Student Passwords</a>
                <a href="bulk_password_reset.php?role=teacher" class="btn" onclick="return confirm('This will reset ALL teacher passwords to \'teacher123\'. Continue?')">Reset All Teacher Passwords</a>
                <button class="btn btn-danger" onclick="alert('This feature requires additional database setup. Contact your administrator.')">Force Password Change</button>
            </div>
            <p style="color: #718096; margin-top: 15px; font-size: 13px;">
                ⚠️ Bulk actions will reset passwords to default values. Use with caution.
            </p>
        </div>
    </div>
    
    <script>
        // Toggle password reset form
        function toggleResetForm(userId) {
            var form = document.getElementById('reset-form-' + userId);
            if (form.style.display === 'none' || form.style.display === '') {
                // Hide all other forms first
                var forms = document.getElementsByClassName('password-reset-form');
                for (var i = 0; i < forms.length; i++) {
                    forms[i].style.display = 'none';
                }
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
        
        // Check password strength
        function checkPasswordStrength() {
            var password = document.getElementById('new_password').value;
            var strengthDiv = document.getElementById('password-strength');
            
            var strength = 0;
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[$@#&!]+/)) strength += 1;
            
            var strengthText = '';
            var strengthClass = '';
            
            if (password.length === 0) {
                strengthText = '';
            } else if (strength < 3) {
                strengthText = 'Weak password';
                strengthClass = 'strength-weak';
            } else if (strength < 5) {
                strengthText = 'Medium password';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong password';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        }
        
        // Check if passwords match
        function checkPasswordMatch() {
            var password = document.getElementById('new_password').value;
            var confirm = document.getElementById('confirm_password').value;
            var matchDiv = document.getElementById('password-match');
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchDiv.innerHTML = '✓ Passwords match';
                    matchDiv.className = 'password-strength strength-strong';
                } else {
                    matchDiv.innerHTML = '✗ Passwords do not match';
                    matchDiv.className = 'password-strength strength-weak';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }
        
        // Validate password form
        function validatePasswordForm() {
            var password = document.getElementById('new_password').value;
            var confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        }
        
        // Validate reset form
        function validateResetForm(userId) {
            var password = document.getElementById('reset-pass-' + userId).value;
            var confirm = document.getElementById('reset-confirm-' + userId).value;
            
            if (password !== confirm) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return confirm('Are you sure you want to reset this user\'s password?');
        }

        function toggleUsernameForm(userId) {
            var form = document.getElementById('username-form-' + userId);
            if (form.style.display === 'none' || form.style.display === '') {
                // Hide all other username forms
                var forms = document.getElementsByClassName('password-reset-form');
                for (var i = 0; i < forms.length; i++) {
                    forms[i].style.display = 'none';
                }
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>