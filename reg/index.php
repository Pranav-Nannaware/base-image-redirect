<?php
// index.php

// Start the session to generate and store the CSRF token
session_start();
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for error messages from recipt.php
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$show_toast = !empty($error_message);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Portal</title>
    <style>
        /* Professional page styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 15px 25px;
            margin: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        #moreButtons, .registrationForm {
            display: none;
            margin-top: 20px;
        }
        /* Toast message styling */
        .toast {
            visibility: hidden;
            min-width: 300px;
            margin-left: -150px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
            opacity: 0;
            transition: opacity 0.5s, visibility 0.5s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .toast.show {
            visibility: visible;
            opacity: 1;
        }
        .toast.error {
            background-color: #e74c3c;
            border-left: 5px solid #c0392b;
        }
        .form-container {
            text-align: left;
            padding: 20px;
            background: #eef3f7;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 400px;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
        .form-container input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container input[type="submit"] {
            background-color: #28a745;
            border: none;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .form-container input[type="submit"]:hover {
            background-color: #218838;
        }
        .receipt-section {
            margin-top: 40px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .receipt-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .receipt-section .form-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .receipt-section p {
            margin-bottom: 15px;
            color: #555;
            text-align: center;
        }
        
        .submit-btn {
            background-color: #2980b9;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background-color: #3498db;
        }
    </style>
    <!-- Load jQuery from CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
<div class="container">
    <h2>Registration Portal</h2>
    <button id="reg11">New Registration of 11th</button>
    <button id="reg12">Existing Registration of 12th</button>
    <div id="moreButtons">
        <button id="aided">Aided</button>
        <button id="unaided">Unaided</button>
    </div>
</div>

<!-- Toast message container -->
<div id="toast" class="toast <?php echo $show_toast ? 'error' : ''; ?>"><?php echo $error_message ?: 'Not applicable for now'; ?></div>

<!-- Registration Forms -->
<div id="aidedForm" class="registrationForm">
    <div class="form-container">
        <h3>Aided Student Registration</h3>
        <form action="process_aided.php" method="post" autocomplete="off">
            <!-- CSRF token field for security -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label for="aidedRegNo">Registration Number:</label>
            <!-- Allow only alphanumeric characters, limit length to 12, require minimum of 5 characters -->
            <input type="text" id="aidedRegNo" name="aidedRegNo" 
                   pattern="[A-Za-z0-9]{4,12}" maxlength="04" 
                   placeholder="Enter your registration number" required autocomplete="off">
            <input type="submit" value="Register">
        </form>
    </div>
</div>

<div id="unaidedForm" class="registrationForm">
    <div class="form-container">
        <h3>Unaided Registration</h3>
        <form action="process_unaided.php" method="post" enctype="multipart/form-data" autocomplete="off">
            <!-- CSRF token field for security -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label for="unaidedRegNo">Registration Number:</label>
            <!-- Same security restrictions applied -->
            <input type="text" id="unaidedRegNo" name="unaidedRegNo" 
                   pattern="[A-Za-z0-9]{4,12}" maxlength="4" 
                   placeholder="Enter your registration number" required autocomplete="off">
            <input type="submit" value="Register">
        </form>
    </div>
</div>

<!-- Receipt Printing Section -->
<div class="receipt-section">
    <div class="container">
        <h2>Student Receipt</h2>
        <div class="form-container">
            <p>Already registered? Print your receipt below:</p>
            <form action="recipt.php" method="get" autocomplete="off">
                <?php 
                // Generate CSRF token if it doesn't exist
                if (!isset($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
                ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <label for="reg_no">Registration Number:</label>
                <input type="text" id="reg_no" name="reg_no" 
                      pattern="[A-Za-z0-9]{3,12}" maxlength="12" 
                      placeholder="Enter your registration number" required autocomplete="off">
                <input type="submit" value="Print Receipt" class="submit-btn">
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Show toast if there was an error
    <?php if($show_toast): ?>
    var toast = $("#toast");
    toast.addClass("show");
    setTimeout(function(){
        toast.removeClass("show");
    }, 5000); // Show for 5 seconds
    <?php endif; ?>
    
    // Show toast on clicking "New Registration of 11th"
    $("#reg11").click(function(){
        var toast = $("#toast");
        toast.text("Not applicable for now");
        toast.addClass("show");
        setTimeout(function(){
            toast.removeClass("show");
        }, 3000);
    });
    
    // Toggle display of additional buttons for 12th registration
    $("#reg12").click(function(){
        $("#moreButtons").slideToggle("slow");
    });
    
    // When clicking on the Aided button, display the aided registration form
    $("#aided").click(function(){
        // Hide the unaided form if visible
        $("#unaidedForm").slideUp("slow");
        // Toggle the aided form with slide effect
        $("#aidedForm").slideToggle("slow");
    });
    
    // When clicking on the Unaided button, display the unaided registration form
    $("#unaided").click(function(){
        // Hide the aided form if visible
        $("#aidedForm").slideUp("slow");
        // Toggle the unaided form with slide effect
        $("#unaidedForm").slideToggle("slow");
    });
});
</script>
</body>
</html>
