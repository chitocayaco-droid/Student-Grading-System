<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Grading System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .grade-A { background-color: #d4edda; }
        .grade-B { background-color: #d1ecf1; }
        .grade-C { background-color: #fff3cd; }
        .grade-D { background-color: #f8d7da; }
        .grade-F { background-color: #f8d7da; }
        
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>School Grading System</h1>
        
        <?php
        // Define grading scale
        $grading_scale = [
            'A' => [90, 100],
            'B' => [80, 89],
            'C' => [70, 79],
            'D' => [60, 69],
            'F' => [0, 59]
        ];
        
        // Function to calculate grade based on score
        function calculateGrade($score, $grading_scale) {
            foreach ($grading_scale as $grade => $range) {
                if ($score >= $range[0] && $score <= $range[1]) {
                    return $grade;
                }
            }
            return 'F';
        }
        
        // Initialize variables
        $students = [];
        $message = '';
        $message_type = '';
        
        // Check if form is submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_student'])) {
                // Validate inputs
                $name = trim($_POST['name']);
                $math = floatval($_POST['math']);
                $science = floatval($_POST['science']);
                $english = floatval($_POST['english']);
                $history = floatval($_POST['history']);
                
                if (!empty($name) && $math >= 0 && $math <= 100 && 
                    $science >= 0 && $science <= 100 && 
                    $english >= 0 && $english <= 100 && 
                    $history >= 0 && $history <= 100) {
                    
                    // Calculate average and grade
                    $average = ($math + $science + $english + $history) / 4;
                    $grade = calculateGrade($average, $grading_scale);
                    
                    // Add to students array
                    $students = isset($_POST['students']) ? json_decode($_POST['students'], true) : [];
                    $students[] = [
                        'name' => $name,
                        'math' => $math,
                        'science' => $science,
                        'english' => $english,
                        'history' => $history,
                        'average' => round($average, 2),
                        'grade' => $grade
                    ];
                    
                    $message = 'Student added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Please fill all fields correctly. Scores must be between 0 and 100.';
                    $message_type = 'error';
                }
            } elseif (isset($_POST['clear_all'])) {
                $students = [];
                $message = 'All records cleared.';
                $message_type = 'success';
            }
        }
        ?>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <h2>Add New Student</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Student Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="math">Math Score (0-100):</label>
                <input type="number" id="math" name="math" min="0" max="100" required>
            </div>
            
            <div class="form-group">
                <label for="science">Science Score (0-100):</label>
                <input type="number" id="science" name="science" min="0" max="100" required>
            </div>
            
            <div class="form-group">
                <label for="english">English Score (0-100):</label>
                <input type="number" id="english" name="english" min="0" max="100" required>
            </div>
            
            <div class="form-group">
                <label for="history">History Score (0-100):</label>
                <input type="number" id="history" name="history" min="0" max="100" required>
            </div>
            
            <input type="hidden" name="students" value="<?php echo isset($students) ? htmlspecialchars(json_encode($students)) : ''; ?>">
            
            <button type="submit" name="add_student">Add Student</button>
            <button type="submit" name="clear_all" style="background-color: #e74c3c;">Clear All</button>
        </form>
        
        <?php if (!empty($students)): ?>
            <h2>Student Grades</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Math</th>
                        <th>Science</th>
                        <th>English</th>
                        <th>History</th>
                        <th>Average</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr class="grade-<?php echo $student['grade']; ?>">
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo $student['math']; ?></td>
                            <td><?php echo $student['science']; ?></td>
                            <td><?php echo $student['english']; ?></td>
                            <td><?php echo $student['history']; ?></td>
                            <td><?php echo $student['average']; ?></td>
                            <td><?php echo $student['grade']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px;">
                <h3>Grading Scale:</h3>
                <p>A: 90-100, B: 80-89, C: 70-79, D: 60-69, F: