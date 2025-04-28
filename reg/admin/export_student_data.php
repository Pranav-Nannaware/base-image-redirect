<?php
// export_student_data.php - Export student data to Excel format

// Start secure session
session_start();

// Security check - verify admin authentication
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header("Location: index.php");
    exit();
}

// Database connection
try {
    $db_host = 'localhost';
    $db_name = 'cmrit_db';
    $db_user = 'cmrit_user';
    $db_pass = 'test';
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Handle export request
if (isset($_POST['export'])) {
    // Get filter parameters for filename
    $status_filter = isset($_POST['status']) ? $_POST['status'] : 'all';
    $division_filter = isset($_POST['division']) ? $_POST['division'] : 'all';
    
    // Create filename with filters
    $filename = "student_data";
    if ($division_filter !== 'all') {
        $filename .= "_division" . $division_filter;
    }
    if ($status_filter !== 'all') {
        $filename .= "_" . $status_filter;
    }
    $filename .= ".xls";
    
    // Set headers for Excel file download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Get filter parameter
    $status_filter = isset($_POST['status']) ? $_POST['status'] : 'all';
    $division_filter = isset($_POST['division']) ? $_POST['division'] : 'all';
    
    // Prepare SQL query based on filters
    $conditions = [];
    $params = [];
    
    if ($status_filter != 'all') {
        $conditions[] = "status = :status";
        $params[':status'] = $status_filter;
    }
    
    if ($division_filter != 'all') {
        $conditions[] = "division = :division";
        $params[':division'] = $division_filter;
    }
    
    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    $sql = "SELECT * FROM approvedstudents {$where_clause} ORDER BY division ASC, class ASC, name_of_student ASC";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    // Create the Excel output
    echo '<table border="1">';
    
    // Add extra row at the top
    echo '<tr>';
    echo '<td colspan="11">Bharat English School &amp; Junior College - Student Data Export (' . date('d-m-Y') . ')</td>';
    echo '</tr>';
    
    // Header row
    echo '<tr>';
    echo '<th></th>'; // Extra column at the start
    echo '<th>Registration Number</th>';
    echo '<th>Name of Student</th>';
    echo '<th>Registration Type</th>';
    echo '<th>Class & Division</th>';
    echo '<th>Tuition Fee (Rs.)</th>';
    echo '<th>Stationary Fee (Rs.)</th>';
    echo '<th>CS Fee (Rs.)</th>';
    echo '<th>IT Fee (Rs.)</th>';
    echo '<th>PTA Fee (Rs.)</th>';
    echo '<th>Total Amount (Rs.)</th>';
    echo '</tr>';
    
    // Data rows
    $row_count = 1;
    $total_tuition = 0;
    $total_stationary = 0;
    $total_cs = 0;
    $total_it = 0;
    $total_pta = 0;
    $total_amount = 0;
    
    foreach ($students as $student) {
        // Calculate fee amounts based on whether receipt exists
        $fee_tuition = ($student['receipt_tuition'] !== null) ? 
            ($student['registration_type'] == 'Aided' ? 340 : 13450) : 0;
        $fee_stationary = ($student['receipt_stationary'] !== null) ? 3000 : 0;
        $fee_cs = ($student['receipt_cs'] !== null) ? 19800 : 0;
        $fee_it = ($student['receipt_it'] !== null) ? 7000 : 0;
        $fee_pta = ($student['receipt_pta'] !== null) ? 200 : 0;
        $fee_total = $fee_tuition + $fee_stationary + $fee_cs + $fee_it + $fee_pta;
        
        // Add to running totals
        $total_tuition += $fee_tuition;
        $total_stationary += $fee_stationary;
        $total_cs += $fee_cs;
        $total_it += $fee_it;
        $total_pta += $fee_pta;
        $total_amount += $fee_total;
        
        echo '<tr>';
        echo '<td>Row ' . $row_count . '</td>'; // Extra column at the start
        echo '<td>' . $student['registration_number'] . '</td>';
        echo '<td>' . $student['name_of_student'] . '</td>';
        echo '<td>' . $student['registration_type'] . '</td>';
        
        // Fix for the Class & Division issue that might be interpreted as time
        $class_value = $student['class'];
        $division_value = $student['division'];
        
        // Check if class is stored as a number or date by ensuring it's treated as a string
        if (is_numeric($class_value)) {
            $class_display = $class_value;
        } else if (!empty($class_value)) {
            // For any non-numeric value, force it to be displayed as a string
            $class_display = strval($class_value);
            // If it looks like a date/time (containing ':' or '/'), just use a numeric class value
            if (strpos($class_display, ':') !== false || strpos($class_display, '/') !== false) {
                $class_display = '12'; // Default to class 12 if it's a date/time format
            }
        } else {
            $class_display = '';
        }
        
        // Ensure division is clean (remove "AM" or "PM" that might be in the data)
        if (!empty($division_value)) {
            // Remove any AM/PM suffix that might be causing issues
            $division_value = str_replace(array(' AM', 'AM', ' PM', 'PM'), '', $division_value);
        }
        
        // Format as "12A" (without space between class and division)
        $class_division = $class_display . $division_value;
        
        echo '<td>' . $class_division . '</td>';
        
        // Display actual fee amounts instead of just "Paid"
        echo '<td>' . ($student['receipt_tuition'] !== null ? $fee_tuition : '') . '</td>';
        echo '<td>' . ($student['receipt_stationary'] !== null ? $fee_stationary : '') . '</td>';
        echo '<td>' . ($student['receipt_cs'] !== null ? $fee_cs : '') . '</td>';
        echo '<td>' . ($student['receipt_it'] !== null ? $fee_it : '') . '</td>';
        echo '<td>' . ($student['receipt_pta'] !== null ? $fee_pta : '') . '</td>';
        
        echo '<td>' . ($fee_total > 0 ? $fee_total : '-') . '</td>'; // Total amount paid - number only
        echo '</tr>';
        $row_count++;
    }
    
    // Add totals row at the end
    echo '<tr>';
    echo '<td></td>'; // Extra column at the start
    echo '<td colspan="4" style="font-weight: bold; text-align: right;">GRAND TOTAL:</td>';
    echo '<td style="font-weight: bold;">' . $total_tuition . '</td>';
    echo '<td style="font-weight: bold;">' . $total_stationary . '</td>';
    echo '<td style="font-weight: bold;">' . $total_cs . '</td>';
    echo '<td style="font-weight: bold;">' . $total_it . '</td>';
    echo '<td style="font-weight: bold;">' . $total_pta . '</td>';
    echo '<td style="font-weight: bold;">' . $total_amount . '</td>';
    echo '</tr>';
    
    // Add extra row at the bottom
    echo '<tr>';
    echo '<td colspan="11">Total Records: ' . count($students) . ' - Generated on ' . date('d-m-Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Export Student Data - Admin Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f8;
            padding: 20px;
            color: #2c3e50;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #2c3e50;
        }
        .export-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button, .btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        button:hover, .btn:hover {
            background-color: #34495e;
        }
        .btn-export {
            background-color: #27ae60;
        }
        .btn-export:hover {
            background-color: #2ecc71;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
        .info-block {
            background-color: #e7f4ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Export Student Data</h1>
        
        <div class="info-block">
            <p>Use this page to export student data to an Excel file. You can filter students by status and division. The exported table will include the following columns:</p>
            <ul>
                <li>Registration Number</li>
                <li>Name of Student</li>
                <li>Registration Type</li>
                <li>Class & Division</li>
                <li>Fee amounts for: Tuition, Stationary, CS, IT, and PTA</li>
                <li>Total fee amount</li>
            </ul>
            <p>Fee columns will show the actual fee amount in rupees if the student has paid, otherwise will be empty.</p>
        </div>
        
        <div class="export-form">
            <h2>Export Options</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="status">Filter by Status:</label>
                    <select id="status" name="status">
                        <option value="all">All Students</option>
                        <option value="approved">Approved Only</option>
                        <option value="rejected">Rejected Only</option>
                        <option value="pending">Pending Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="division">Filter by Division:</label>
                    <select id="division" name="division">
                        <option value="all">All Divisions</option>
                        <option value="A">Division A</option>
                        <option value="B">Division B</option>
                        <option value="C">Division C</option>
                        <option value="D">Division D</option>
                    </select>
                </div>
                
                <button type="submit" name="export" class="btn btn-export">Export to Excel</button>
                <a href="index.php" class="btn">Back to Dashboard</a>
            </form>
        </div>
    </div>
</body>
</html> 