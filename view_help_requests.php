<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$page_title = "Help Requests";

// Mark as read
if (isset($_GET['mark_read'])) {
    $id = $_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE help_requests SET status = 'read' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: view_help_requests.php");
    exit();
}

// Delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM help_requests WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: view_help_requests.php");
    exit();
}

// Get all help requests
$stmt = $pdo->query("SELECT * FROM help_requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll();

include 'includes/header.php';
?>

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
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0px 20px rgba(255, 216, 110, 0.6);
    }
    .requests-table {
        margin-top: 10px;    
        width: 100%;
        border-collapse: collapse;
    }
    .requests-table th {
        background: #345dce;
        color: white;
        padding: 12px;
        text-align: left;
    }
    .requests-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .requests-table tr:hover {
        background: hsla(54, 100%, 96%, 0.69);
    }
    .status-pending {
        background: #fef3c7;
        color: #92400e;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-read {
        background: #d1fae5;
        color: #065f46;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
        margin: 0 2px;
    }
</style>

<body>
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📧 Help Requests</h2>
            </div>
            <?php if (count($requests) > 0): ?>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo $request['id']; ?></td>
                            <td><?php echo htmlspecialchars($request['name']); ?></td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo htmlspecialchars(substr($request['message'], 0, 50)) . '...'; ?></td>
                            <td><?php echo $request['ip_address']; ?></td>
                            <td>
                                <span class="status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></td>
                            <td>
                                <a href="?mark_read=<?php echo $request['id']; ?>" class="btn btn-sm btn-success">Mark Read</a>
                                <a href="?delete=<?php echo $request['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this request?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 40px;">No help requests yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

<?php include 'includes/footer.php'; ?>