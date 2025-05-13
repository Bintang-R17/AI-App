<?php 
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "unit_kesehatan";
// Simpan API key Groq di sini
define('GROQ_API_KEY', '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');

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>