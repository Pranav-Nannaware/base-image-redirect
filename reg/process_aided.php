<?php
// process_aided.php
session_start();
require 'db_connect.php';

if (isset($_POST['aidedRegNo'])) {
    $reg_no = trim($_POST['aidedRegNo']);

    $sql = "SELECT * FROM existstudents WHERE registration_number = :reg_no AND registration_type = 'aided'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':reg_no' => $reg_no]);
    $student = $stmt->fetch();

    if ($student) {
        $_SESSION['registration_number'] = $student['registration_number'];
        $_SESSION['student_name'] = $student['name_of_student'];
        $_SESSION['registration_type'] = $student['registration_type'];
        
        echo "<!DOCTYPE html><html><head><title>Student Details</title>";
        ?>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f7f8;
                padding: 20px;
            }
            h2 {
                color: #333;
            }
            table {
                width: 50%;
                border-collapse: collapse;
                margin: 20px auto;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                background-color: #fff;
                border-radius: 8px;
                overflow: hidden;
            }
            th, td {
                text-align: left;
                padding: 12px 20px;
                border-bottom: 1px solid #ddd;
            }
            th {
                background-color: #2c3e50;
                color: white;
            }
            tr:last-child td {
                border-bottom: none;
            }
            .button-container {
                text-align: center;
                margin-top: 30px;
            }
            .fees-button {
                background-color: #2c3e50;
                color: #fff;
                border: none;
                padding: 10px 20px;
                font-size: 16px;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
            }
            .fees-button:hover {
                background-color: #34495e;
            }
        </style>
        <?php
        echo "</head><body>";
        echo "<h2 style='text-align:center;'>Student Details (Aided)</h2>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Details</th></tr>";
        echo "<tr><td>Name</td><td>" . htmlspecialchars($student['name_of_student']) . "</td></tr>";
        echo "<tr><td>Class</td><td>" . htmlspecialchars($student['class']) . "</td></tr>";
        echo "<tr><td>Registration Type</td><td>" . htmlspecialchars($student['registration_type']) . "</td></tr>";
        echo "<tr><td>Division</td><td>" . htmlspecialchars($student['division']) . "</td></tr>";
        echo "<tr><td>Registration Number</td><td>" . htmlspecialchars($student['registration_number']) . "</td></tr>";
        echo "</table>";
        echo "<div class='button-container'>";
        echo "<a href='fees_aided.php' class='fees-button'>Proceed to Fees Structure</a>";
        echo "</div>";
        echo "</body></html>";
    } else {
        header("Location: index.php?error=invalid_reg");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
