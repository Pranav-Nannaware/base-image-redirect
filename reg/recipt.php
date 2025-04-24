<?php
// recipt.php - Generate printable receipt for students

// Require student ID or registration number
if (!isset($_GET['id']) && !isset($_GET['reg_no'])) {
    // Check if coming from form submission
    die("Error: No student information provided. Please enter a valid registration number.");
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

$error_message = "";
$student = null;

// Fetch student data
if (isset($_GET['id'])) {
    // Direct access by ID (likely from admin panel)
    $stmt = $pdo->prepare("SELECT * FROM approvedstudents WHERE id = :id AND status = 'approved'");
    $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch();
    
    if (!$student) {
        $error_message = "Student not found or not approved.";
    }
} else {
    // Access by registration number (from student form)
    // Clean and sanitize registration number
    $reg_no = trim(htmlspecialchars($_GET['reg_no']));
    
    // Basic validation
    if (empty($reg_no) || !preg_match('/^[A-Za-z0-9]{3,12}$/', $reg_no)) {
        $error_message = "Invalid registration number format.";
    } else {
        // First check if student is in approvedstudents table AND is approved
        $stmt = $pdo->prepare("SELECT * FROM approvedstudents WHERE registration_number = :reg_no AND status = 'approved'");
        $stmt->bindParam(':reg_no', $reg_no, PDO::PARAM_STR);
        $stmt->execute();
        $student = $stmt->fetch();
        
        if (!$student) {
            // Check if student exists but is not approved
            $stmt = $pdo->prepare("SELECT * FROM approvedstudents WHERE registration_number = :reg_no AND status = 'rejected'");
            $stmt->bindParam(':reg_no', $reg_no, PDO::PARAM_STR);
            $stmt->execute();
            $rejected_student = $stmt->fetch();
            
            if ($rejected_student) {
                $error_message = "Your registration has been rejected. Please contact the administration.";
            } else {
                // Check if student exists in existstudents but not yet approved
                $stmt = $pdo->prepare("SELECT * FROM existstudents WHERE registration_number = :reg_no");
                $stmt->bindParam(':reg_no', $reg_no, PDO::PARAM_STR);
                $stmt->execute();
                $exist_student = $stmt->fetch();
                
                if ($exist_student) {
                    $error_message = "Your registration is pending approval. Please check back later.";
                } else {
                    $error_message = "Registration number not found. Please verify your information.";
                }
            }
        }
    }
}

// If there's an error, show toast message and redirect back
if (!empty($error_message)) {
    // Go back to index with error message
    header("Location: index.php?error=" . urlencode($error_message));
    exit;
}

// Set the current date and academic year
$current_date = date("d/m/Y");
$current_year = date("Y");
$next_year = $current_year + 1;
$academic_year = $current_year . "-" . substr($next_year, -2);

// Calculate fee amounts based on whether receipt exists

$fee_tuition = ($student['receipt_tuition'] !== null) ? 
    ($student['registration_type'] == 'Aided' ? 340 : 13450) : 0;
$fee_stationary = ($student['receipt_stationary'] !== null) ? 3000 : 0;
$fee_cs = ($student['receipt_cs'] !== null) ? 19800 : 0;
$fee_it = ($student['receipt_it'] !== null) ? 7000 : 0;
$fee_pta = ($student['receipt_pta'] !== null) ? 200 : 0;
$fee_total = + $fee_tuition + $fee_stationary + $fee_cs + $fee_it + $fee_pta;

// Convert number to words function
function numberToWords($number) {
    $ones = array(
        0 => "", 1 => "one", 2 => "two", 3 => "three", 4 => "four", 5 => "five", 
        6 => "six", 7 => "seven", 8 => "eight", 9 => "nine", 10 => "ten", 
        11 => "eleven", 12 => "twelve", 13 => "thirteen", 14 => "fourteen", 
        15 => "fifteen", 16 => "sixteen", 17 => "seventeen", 18 => "eighteen", 
        19 => "nineteen"
    );
    $tens = array(
        0 => "", 1 => "", 2 => "twenty", 3 => "thirty", 4 => "forty", 5 => "fifty", 
        6 => "sixty", 7 => "seventy", 8 => "eighty", 9 => "ninety"
    );
    $hundreds = array(
        "hundred", "thousand", "lakh", "crore"
    );

    if ($number == 0) return "zero";

    $words = "";

    if ($number < 0) {
        $words .= "negative ";
        $number = abs($number);
    }

    // For Indian numbering system
    if ($number > 9999999) {
        $crore = intval($number / 10000000);
        $words .= numberToWords($crore) . " crore ";
        $number = $number % 10000000;
    }

    if ($number > 99999) {
        $lakh = intval($number / 100000);
        $words .= numberToWords($lakh) . " lakh ";
        $number = $number % 100000;
    }

    if ($number > 999) {
        $thousand = intval($number / 1000);
        $words .= numberToWords($thousand) . " thousand ";
        $number = $number % 1000;
    }

    if ($number > 99) {
        $hundred = intval($number / 100);
        $words .= numberToWords($hundred) . " hundred ";
        $number = $number % 100;
    }

    if ($number > 0) {
        if ($words != "") {
            $words .= "and ";
        }

        if ($number < 20) {
            $words .= $ones[$number];
        } else {
            $words .= $tens[intval($number / 10)];
            if ($number % 10 > 0) {
                $words .= " " . $ones[$number % 10];
            }
        }
    }

    return $words;
}

// Get amount in words
$amount_in_words = ucfirst(numberToWords($fee_total)) . " rupees only";

// PDF generation if requested
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    // We would implement PDF generation here with a library like FPDF or mPDF
    // For simplicity, we'll just force a download of the HTML
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="receipt_'.$student['registration_number'].'.pdf"');
    // In a real implementation, we would generate a PDF here
    // For now, we'll just display the receipt
}
?>
<html>
 <head>
  <title>Payment Receipt - <?php echo htmlspecialchars($student['registration_number']); ?></title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
   @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
   @media print {
       body {
           -webkit-print-color-adjust: exact;
           print-color-adjust: exact;
       }
       .no-print {
           display: none;
       }
       /* A5 size for printing */
       @page {
           size: B5;
           margin: 0;
       }
       .receipt-container {
           width: 176mm; /* B5 width */
           height: 250mm; /* B5 height */
           margin: 0;
           padding: 15mm;
           box-sizing: border-box;
           page-break-after: always;
           overflow: visible;
       }
   }
   body {
       font-family: 'Roboto', sans-serif;
   }
   .receipt-container {
       width: 176mm; /* B5 width */
       height: 250mm; /* B5 height */
       margin: 0 auto;
       padding: 15mm;
       box-sizing: border-box;
       background-color: white;
       border: 1px solid black;
       /* Scale down the font sizes to fit A5 better */
       font-size: 0.95rem;
   }
   .logo-img {
       max-width: 80px;
       max-height: 80px;
   }
   .print-btn {
       position: fixed;
       top: 20px;
       right: 20px;
       background-color: #2c3e50;
       color: white;
       border: none;
       padding: 10px 15px;
       border-radius: 4px;
       cursor: pointer;
       font-size: 14px;
   }
   .na-fee {
       color: #999;
       font-style: italic;
   }
   .fee-row-inactive {
       background-color: #f8f8f8;
   }
   /* Smaller header and text for A5 */
   h1, h2, h3 {
       margin: 0.25rem 0;
   }
   .school-header {
       margin-right: 75px;
       text-align: center;
   }
   .school-header h1, .school-header h2 {
       font-size: 1.1rem;
   }
   .school-header p {
       font-size: 0.8rem;
   }
   .fees-header {
       font-size: 1.1rem;
       margin-bottom: 0.5rem;
   }
   .student-info {
       font-size: 0.9rem;
   }
   .fees-table {
       font-size: 0.85rem;
   }
   .fees-table th, .fees-table td {
       padding: 4px 8px;
   }
   .amount-words {
       font-size: 0.85rem;
   }
   .footer-section {
       font-size: 0.85rem;
   }
  </style>
 </head>
 <body class="bg-gray-100">
  <button class="print-btn no-print" onclick="window.print()">Print Receipt</button>
  <div class="receipt-container">
   <div class="flex justify-between items-center mb-4">
    <img alt="School logo" class="logo-img  " src="../images/logo2.jpg"/>
    <div class="school-header">
     <h1 class="font-bold">
      V.P.Sabha's
     </h1>
     <h2 class="font-bold">
      Bharat English School
     </h2>
     <h3 class="font-bold">
      &amp;
     </h3>
     <h2 class="font-bold">
      Junior College
     </h2>
     <p>
      19/2, T.P Scheme, Shivajinagar, Pune 411005
     </p>
    </div>
   </div>
   <hr class="border-black mb-3"/>
   <h2 class="text-center font-bold fees-header mb-3">
    FEES RECEIPT
   </h2>
   <div class="student-info mb-3">
    <div class="flex justify-between">
     <p>
      Registration no: <?php echo htmlspecialchars($student['registration_number']); ?>
     </p>
     <p>
      Receipt Date: <?php echo $current_date; ?>
     </p>
    </div>
    <p>
     Student Name: <?php echo htmlspecialchars($student['name_of_student']); ?>
    </p>
    <p>
     Class: <?php echo htmlspecialchars($student['class']); ?><sup>th</sup>, Division: <?php echo htmlspecialchars($student['division']); ?>
    </p>
    <p>
     Academic Year: <?php echo $academic_year; ?>
    </p>
   </div>
   <hr class="border-black mb-3"/>
   <table class="w-full mb-3 border border-black fees-table">
    <thead>
     <tr>
      <th class="border border-black">
       Fees Components
      </th>
      <th class="border border-black">
       Amount
      </th>
     </tr>
    </thead>
    <tbody>
     <!-- <tr>
      <td class="border border-black">
       HSC EXAM FEES
      </td>
      <td class="border border-black">
       <?php echo $fee_hsc; ?>/-
      </td>
     </tr> -->
     <tr <?php if ($fee_tuition == 0) echo 'class="fee-row-inactive"'; ?>>
      <td class="border border-black">
       TUITION FEES
      </td>
      <td class="border border-black">
       <?php echo ($fee_tuition > 0) ? $fee_tuition . '/-' : '<span class="na-fee">N/A</span>'; ?>
      </td>
     </tr>
     <tr <?php if ($fee_stationary == 0) echo 'class="fee-row-inactive"'; ?>>
      <td class="border border-black">
       STATIONARY FEES
      </td>
      <td class="border border-black">
       <?php echo ($fee_stationary > 0) ? $fee_stationary . '/-' : '<span class="na-fee">N/A</span>'; ?>
      </td>
     </tr>
     <tr <?php if ($fee_cs == 0) echo 'class="fee-row-inactive"'; ?>>
      <td class="border border-black">
       C.S FEES
      </td>
      <td class="border border-black">
       <?php echo ($fee_cs > 0) ? $fee_cs . '/-' : '<span class="na-fee">N/A</span>'; ?>
      </td>
     </tr>
     <tr <?php if ($fee_it == 0) echo 'class="fee-row-inactive"'; ?>>
      <td class="border border-black">
       IT FEES
      </td>
      <td class="border border-black">
       <?php echo ($fee_it > 0) ? $fee_it . '/-' : '<span class="na-fee">N/A</span>'; ?>
      </td>
     </tr>
     <tr <?php if ($fee_pta == 0) echo 'class="fee-row-inactive"'; ?>>
      <td class="border border-black">
       PTA FEES
      </td>
      <td class="border border-black">
       <?php echo ($fee_pta > 0) ? $fee_pta . '/-' : '<span class="na-fee">N/A</span>'; ?>
      </td>
     </tr>
     <tr>
      <td class="border border-black font-bold">
       TOTAL FEES
      </td>
      <td class="border border-black font-bold">
       <?php echo $fee_total; ?>/-
      </td>
     </tr>
    </tbody>
   </table>
   <p class="mb-2 amount-words">
    Amount in words:
   </p>
   <p class="mb-3 amount-words">
    <?php echo $amount_in_words; ?>
   </p>
    
   <?php if ($fee_tuition == 0 || $fee_stationary == 0 || $fee_cs == 0 || $fee_it == 0 || $fee_pta == 0): ?>
   <p class="mb-3 text-xs text-gray-600">
    <i>Note: Fees marked as N/A were not applicable based on student's submitted documentation.</i>
   </p>
   <?php endif; ?>
    
   <div class="flex justify-between items-center footer-section">
    <div>
    
    </div>
    <div>
     <p>
      <!-- Date: <?php echo $current_date; ?> -->
      <br></br>
      <p class="font-bold text-center">
            Principal
     </p>
     <p>
      Bharat English School &amp;
     </p>
     <p>
      Junior College, Pune 5
     </p>
     </p>
    </div>
   </div>
  </div>
 </body>
</html>
