<?php
// config.php
session_start();

// Define Base URL for absolute paths
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
// Normalize base_url to root of the project if we are in admin/manager
if (strpos($base_url, '/admin/') !== false)
    $base_url = str_replace('/admin/', '/', $base_url);
if (strpos($base_url, '/manager/') !== false)
    $base_url = str_replace('/manager/', '/', $base_url);
define('BASE_URL', rtrim($base_url, '/') . '/');

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'quicktable_db';

// Create connection using mysqli
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Role constants
define('ROLE_USER', 0);
define('ROLE_MANAGER', 1);
define('ROLE_ADMIN', 2);

// Helper functions
function redirect($path)
{
    header("Location: $path");
    exit();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getUserRole()
{
    return $_SESSION['role'] ?? ROLE_USER;
}

function isAdmin()
{
    return getUserRole() == ROLE_ADMIN;
}

function isManager()
{
    return getUserRole() == ROLE_MANAGER;
}
?>