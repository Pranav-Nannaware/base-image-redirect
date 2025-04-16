    <?php
    // view_approved_students.php - Admin page to view approved and rejected students

    // Start secure session
    session_start();

    // Logout functionality
    if (isset($_GET['logout'])) {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to the login page
        header("Location: index.php");
        exit();
    }

    // Basic authentication - in a real implementation, use proper authentication
    $admin_username = 'admin';
    $admin_password = 'admin123'; // This should be properly hashed in production

    // Check if user is already authenticated
    $authenticated = false;
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        $authenticated = true;
    } elseif (isset($_POST['username']) && isset($_POST['password'])) {
        // Verify credentials (in production, use password_hash and password_verify)
        if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
            $_SESSION['admin_authenticated'] = true;
            $authenticated = true;
        } else {
            $error_message = "Invalid credentials";
        }
    }

    // Only continue if authenticated
    if ($authenticated) {
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
        
        // Search variables
        $search_reg_no = isset($_GET['reg_no']) ? trim($_GET['reg_no']) : '';
        $search_type = isset($_GET['type']) ? $_GET['type'] : '';
        $search_division = isset($_GET['division']) ? $_GET['division'] : '';
        $search_status = isset($_GET['status']) ? $_GET['status'] : '';
        
        // Check if the approvedstudents table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'approvedstudents'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $table_missing = true;
        } else {
            // Check if status column exists
            $stmt = $pdo->prepare("SHOW COLUMNS FROM approvedstudents LIKE 'status'");
            $stmt->execute();
            $status_column_exists = ($stmt->rowCount() > 0);
            
            // Check if approval_date column exists
            $stmt = $pdo->prepare("SHOW COLUMNS FROM approvedstudents LIKE 'approval_date'");
            $stmt->execute();
            $approval_date_column_exists = ($stmt->rowCount() > 0);
            
            // Base query
            $query = "SELECT id, registration_number, name_of_student, registration_type, class, division, 
                            CASE WHEN receipt_tuition IS NOT NULL THEN 'Yes' ELSE 'No' END as has_tuition_receipt,
                            CASE WHEN receipt_stationary IS NOT NULL THEN 'Yes' ELSE 'No' END as has_stationary_receipt,
                            CASE WHEN receipt_cs IS NOT NULL THEN 'Yes' ELSE 'No' END as has_cs_receipt,
                            CASE WHEN receipt_it IS NOT NULL THEN 'Yes' ELSE 'No' END as has_it_receipt,
                            CASE WHEN receipt_pta IS NOT NULL THEN 'Yes' ELSE 'No' END as has_pta_receipt";
            
            // Add status and approval_date to the query only if they exist
            if ($status_column_exists) {
                $query .= ", status";
            } else {
                $query .= ", 'approved' AS status"; // Default status if column doesn't exist
            }
            
            if ($approval_date_column_exists) {
                $query .= ", approval_date";
            } else {
                $query .= ", NOW() AS approval_date"; // Default current time if column doesn't exist
            }
            
            $query .= " FROM approvedstudents 
                    WHERE class = '12'";
            
            $params = [];
            
            // Add search filters
            if (!empty($search_reg_no)) {
                $query .= " AND registration_number = :reg_no";
                $params[':reg_no'] = $search_reg_no;
            }
            
            if (!empty($search_type)) {
                $query .= " AND registration_type = :type";
                $params[':type'] = $search_type;
            }
            
            if (!empty($search_division)) {
                $query .= " AND division = :division";
                $params[':division'] = $search_division;
            }
            
            if (!empty($search_status) && $status_column_exists) {
                $query .= " AND status = :status";
                $params[':status'] = $search_status;
            }
            
            // Order the results
            $query .= " ORDER BY approval_date DESC, registration_number";
            
            // Prepare and execute the query
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $students = $stmt->fetchAll();
        }
        
        // For viewing individual receipts
        if (isset($_GET['view_receipt']) && isset($_GET['student_id']) && isset($_GET['receipt_type'])) {
            $student_id = $_GET['student_id'];
            $receipt_type = $_GET['receipt_type'];
            
            // Map receipt types to database columns
            $column_mapping = [
                'tuition' => 'receipt_tuition',
                'stationary' => 'receipt_stationary',
                'cs' => 'receipt_cs',
                'it' => 'receipt_it',
                'pta' => 'receipt_pta'
            ];
            
            if (isset($column_mapping[$receipt_type])) {
                $column = $column_mapping[$receipt_type];
                
                $stmt = $pdo->prepare("SELECT registration_number, {$column} FROM approvedstudents WHERE id = :id");
                $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
                $stmt->execute();
                $receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($receipt_data && $receipt_data[$column] !== null) {
                    // Send appropriate headers for image display
                    header('Content-Type: image/jpeg');
                    echo $receipt_data[$column];
                    exit;
                }
            }
            
            // If we get here, there was an error
            header('HTTP/1.1 404 Not Found');
            echo "Receipt not found";
            exit;
        }
        
        // For deleting student from approvedstudents table
        if (isset($_GET['delete_student']) && isset($_GET['student_id']) && isset($_GET['csrf_token'])) {
            // Verify CSRF token
            if (!isset($_SESSION['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
                $delete_error = "Invalid security token. Please try again.";
            } else {
                $student_id = $_GET['student_id'];
                
                try {
                    // Get student info first for confirmation message
                    $stmt = $pdo->prepare("SELECT registration_number, name_of_student FROM approvedstudents WHERE id = :id");
                    $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $student_info = $stmt->fetch();
                    
                    if ($student_info) {
                        // Delete the student from approvedstudents
                        $stmt = $pdo->prepare("DELETE FROM approvedstudents WHERE id = :id");
                        $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            $delete_success = "Student " . htmlspecialchars($student_info['name_of_student']) . 
                                            " (Reg #: " . htmlspecialchars($student_info['registration_number']) . 
                                            ") has been deleted from the approved students list.";
                        } else {
                            $delete_error = "Failed to delete student.";
                        }
                    } else {
                        $delete_error = "Student not found.";
                    }
                } catch (PDOException $e) {
                    $delete_error = "Database error: " . $e->getMessage();
                }
            }
            
            // Regenerate CSRF token after processing
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Generate CSRF token if it doesn't exist
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Approved Students - Admin Panel</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f4f7f8;
                padding: 20px;
                color: #2c3e50;
            }
            .container {
                max-width: 1400px;
                margin: 0 auto;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                overflow-x: auto; /* Add horizontal scrolling if needed */
            }
            h1, h2 {
                text-align: center;
                color: #2c3e50;
            }
            .login-form {
                max-width: 400px;
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
            input[type="text"], input[type="password"], select {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .search-form {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 5px;
            }
            .search-group {
                flex: 1;
                min-width: 200px;
            }
            button, .btn {
                background-color: #2c3e50;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            button:hover, .btn:hover {
                background-color: #34495e;
            }
            .error {
                color: #e74c3c;
                background-color: #f9e7e7;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            .success {
                color: #27ae60;
                background-color: #eafaf1;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                table-layout: fixed; /* Fixed table layout */
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: center;
                vertical-align: middle;
                word-wrap: break-word; /* Allow text to wrap */
                font-size: 13px; /* Slightly smaller font */
            }
            td {
                min-width: 80px; /* Smaller minimum width */
            }
            th {
                background-color: #2c3e50;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .receipt-status {
                padding: 3px 5px;
                border-radius: 3px;
                font-size: 11px;
                display: inline-block;
                margin-bottom: 3px;
            }
            .status-yes {
                background-color: #e1f5e1;
                color: #2ecc71;
            }
            .status-no {
                background-color: #f9e7e7;
                color: #e74c3c;
            }
            .view-link {
                display: inline-block;
                padding: 3px 5px;
                color: white;
                text-decoration: none;
                border-radius: 3px;
                font-size: 11px;
                background-color: #3498db;
            }
            .view-link:hover {
                background-color: #2980b9;
            }
            .receipt-actions {
                display: inline-block;
            }
            .logout-link {
                display: block;
                text-align: right;
                margin-bottom: 20px;
            }
            .actions {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
            }
            .status-approved {
                display: inline-block;
                background-color: #27ae60;
                color: white;
                padding: 3px 8px;
                border-radius: 3px;
                font-weight: bold;
                font-size: 12px;
            }
            .status-rejected {
                display: inline-block;
                background-color: #e74c3c;
                color: white;
                padding: 3px 8px;
                border-radius: 3px;
                font-weight: bold;
                font-size: 12px;
            }
            .date-display {
                color: #7f8c8d;
                font-size: 10px;
                margin-top: 3px;
            }
            .empty-message {
                text-align: center;
                padding: 40px;
                background-color: #f8f9fa;
                border-radius: 5px;
                margin: 20px 0;
                color: #7f8c8d;
            }
            /* Modal styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 999;
                padding-top: 100px;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.7);
            }
            .modal-content {
                margin: auto;
                display: block;
                max-width: 80%;
                max-height: 80%;
            }
            .modal-caption {
                margin: auto;
                display: block;
                width: 80%;
                text-align: center;
                color: white;
                padding: 10px 0;
            }
            .close {
                position: absolute;
                top: 15px;
                right: 35px;
                color: #f1f1f1;
                font-size: 40px;
                font-weight: bold;
                transition: 0.3s;
                cursor: pointer;
            }
            .close:hover, .close:focus {
                color: #bbb;
                text-decoration: none;
            }
            
            /* Delete button styling */
            .delete-btn {
                background-color: #e74c3c;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: background-color 0.3s;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                margin-bottom: 3px;
            }
            .delete-btn:hover {
                background-color: #c0392b;
            }
            
            /* View Receipt button styling */
            .view-btn {
                background-color: #3498db;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: background-color 0.3s;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                margin-bottom: 3px;
            }
            .view-btn:hover {
                background-color: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Approved/Rejected Students</h1>
            
            <?php if (!$authenticated): ?>
                <!-- Login Form -->
                <div class="login-form">
                    <h2>Admin Login</h2>
                    <?php if (isset($error_message)): ?>
                        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit">Login</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Admin Dashboard -->
                <a href="?logout=1" class="logout-link">Logout</a>
                
                <!-- Success/Error Messages -->
                <?php if (isset($delete_success)): ?>
                    <div class="success"><?php echo htmlspecialchars($delete_success); ?></div>
                <?php endif; ?>
                
                <?php if (isset($delete_error)): ?>
                    <div class="error"><?php echo htmlspecialchars($delete_error); ?></div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <form class="search-form" method="GET" action="">
                    <div class="search-group">
                        <label for="reg_no">Registration Number:</label>
                        <input type="text" id="reg_no" name="reg_no" value="<?php echo htmlspecialchars($search_reg_no); ?>">
                    </div>
                    <div class="search-group">
                        <label for="type">Registration Type:</label>
                        <select id="type" name="type">
                            <option value="">All</option>
                            <option value="Aided" <?php echo $search_type === 'Aided' ? 'selected' : ''; ?>>Aided</option>
                            <option value="Unaided" <?php echo $search_type === 'Unaided' ? 'selected' : ''; ?>>Unaided</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="division">Division:</label>
                        <select id="division" name="division">
                            <option value="">All</option>
                            <option value="A" <?php echo $search_division === 'A' ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo $search_division === 'B' ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo $search_division === 'C' ? 'selected' : ''; ?>>C</option>
                            <option value="D" <?php echo $search_division === 'D' ? 'selected' : ''; ?>>D</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="">All</option>
                            <option value="approved" <?php echo $search_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $search_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="search-group" style="align-self: flex-end;">
                        <button type="submit">Search</button>
                        <a href="view_approved_students.php" class="btn">Clear</a>
                    </div>
                </form>
                
                <!-- Results Table -->
                <div class="results">
                    <h2>Student Records</h2>
                    
                    <?php if (isset($table_missing)): ?>
                        <div class="empty-message">
                            <p>No approved/rejected students found. The approvedstudents table does not exist yet.</p>
                            <p>Students need to be approved or rejected from the <a href="view_receipts.php">receipts view page</a> first.</p>
                        </div>
                    <?php elseif (empty($students)): ?>
                        <div class="empty-message">
                            <p>No records found matching your search criteria.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 7%;">Reg #</th>
                                    <th style="width: 13%;">Name</th>
                                    <th style="width: 6%;">Type</th>
                                    <th style="width: 5%;">Division</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 8%;">Tuition</th>
                                    <th style="width: 8%;">Stationary</th>
                                    <th style="width: 8%;">CS</th>
                                    <th style="width: 8%;">IT</th>
                                    <th style="width: 8%;">PTA</th>
                                    <th style="width: 19%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name_of_student']); ?></td>
                                        <td><?php echo htmlspecialchars($student['registration_type']); ?></td>
                                        <td><?php echo htmlspecialchars($student['division']); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($student['status']); ?>"><?php echo ucfirst($student['status']); ?></span>
                                            <div class="date-display">
                                                <?php 
                                                if ($approval_date_column_exists && $student['approval_date']) {
                                                    echo date('M d, y', strtotime($student['approval_date']));
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Tuition Receipt -->
                                        <td>
                                            <span class="receipt-status status-<?php echo strtolower($student['has_tuition_receipt']); ?>">
                                                <?php echo $student['has_tuition_receipt']; ?>
                                            </span>
                                            <?php if ($student['has_tuition_receipt'] === 'Yes'): ?>
                                                <div class="receipt-actions">
                                                    <a href="javascript:void(0);" onclick="showReceipt(<?php echo $student['id']; ?>, 'tuition')" 
                                                    class="view-link">View</a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Stationary Receipt -->
                                        <td>
                                            <span class="receipt-status status-<?php echo strtolower($student['has_stationary_receipt']); ?>">
                                                <?php echo $student['has_stationary_receipt']; ?>
                                            </span>
                                            <?php if ($student['has_stationary_receipt'] === 'Yes'): ?>
                                                <div class="receipt-actions">
                                                    <a href="javascript:void(0);" onclick="showReceipt(<?php echo $student['id']; ?>, 'stationary')" 
                                                    class="view-link">View</a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- CS Receipt -->
                                        <td>
                                            <span class="receipt-status status-<?php echo strtolower($student['has_cs_receipt']); ?>">
                                                <?php echo $student['has_cs_receipt']; ?>
                                            </span>
                                            <?php if ($student['has_cs_receipt'] === 'Yes'): ?>
                                                <div class="receipt-actions">
                                                    <a href="javascript:void(0);" onclick="showReceipt(<?php echo $student['id']; ?>, 'cs')" 
                                                    class="view-link">View</a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- IT Receipt -->
                                        <td>
                                            <span class="receipt-status status-<?php echo strtolower($student['has_it_receipt']); ?>">
                                                <?php echo $student['has_it_receipt']; ?>
                                            </span>
                                            <?php if ($student['has_it_receipt'] === 'Yes'): ?>
                                                <div class="receipt-actions">
                                                    <a href="javascript:void(0);" onclick="showReceipt(<?php echo $student['id']; ?>, 'it')" 
                                                    class="view-link">View</a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- PTA Receipt -->
                                        <td>
                                            <span class="receipt-status status-<?php echo strtolower($student['has_pta_receipt']); ?>">
                                                <?php echo $student['has_pta_receipt']; ?>
                                            </span>
                                            <?php if ($student['has_pta_receipt'] === 'Yes'): ?>
                                                <div class="receipt-actions">
                                                    <a href="javascript:void(0);" onclick="showReceipt(<?php echo $student['id']; ?>, 'pta')" 
                                                    class="view-link">View</a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../recipt.php?id=<?php echo $student['id']; ?>" target="_blank" class="view-btn" style="width: auto; display: inline-block; margin-right: 5px;">View Receipt</a>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $student['id']; ?>)" class="delete-btn" style="width: auto; display: inline-block;">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="actions">
                    <a href="view_receipts.php" class="btn">View Receipts</a>
                    <a href="index.php" class="btn">Return to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Image Viewing Modal -->
        <div id="receiptModal" class="modal">
            <span class="close">&times;</span>
            <img class="modal-content" id="receiptImage">
            <div id="modalCaption" class="modal-caption"></div>
        </div>
        
        <script>
            // Variables for receipt viewing
            var modal = document.getElementById('receiptModal');
            var modalImg = document.getElementById('receiptImage');
            var modalCaption = document.getElementById('modalCaption');
            var closeBtn = document.getElementsByClassName('close')[0];
            
            // Function to show receipt in modal
            function showReceipt(studentId, receiptType) {
                // Set the image source to the view_receipt endpoint
                modalImg.src = "?view_receipt=1&student_id=" + studentId + "&receipt_type=" + receiptType;
                
                // Set caption based on receipt type
                var receiptNames = {
                    'tuition': 'Tuition Fee',
                    'stationary': 'Stationary Fee',
                    'cs': 'CS Fee',
                    'it': 'IT Fee',
                    'pta': 'PTA Fee'
                };
                
                modalCaption.innerHTML = receiptNames[receiptType] + " Receipt";
                
                // Show the modal
                modal.style.display = "block";
            }
            
            // Close modal when clicking the × button
            closeBtn.onclick = function() {
                modal.style.display = "none";
            }
            
            // Close modal when clicking outside the image
            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }
            
            // Function to confirm student deletion
            function confirmDelete(studentId) {
                if (confirm("Are you sure you want to delete this student from the records? This action cannot be undone.")) {
                    window.location.href = "?delete_student=1&student_id=" + studentId + 
                        "&csrf_token=<?php echo $_SESSION['csrf_token']; ?>";
                }
            }
        </script>
    </body>
    </html> 