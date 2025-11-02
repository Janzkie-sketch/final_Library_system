<?php
// Database connection settings
$host = "localhost";
$user = "root";  // default for XAMPP
$pass = "";
$db   = "library_db";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Optional: Uncomment to test connection
// echo "✅ Connected successfully";
?>
