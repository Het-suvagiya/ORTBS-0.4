<?php
require_once 'config.php';

// Enable error reporting
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

function generatePasswordHash($password)
{
    echo "Generated Hash for '$password':\n";
    echo password_hash($password, PASSWORD_DEFAULT) . "\n\n";
}

function addMaxGuestsColumn($conn)
{
    echo "Adding max_guests column...\n";
    $query = "ALTER TABLE restaurants ADD COLUMN max_guests INT DEFAULT 20 AFTER avg_price";
    if ($conn->query($query) === TRUE) {
        echo "Column max_guests added successfully\n\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n\n";
    }
}

function checkRestaurantColumns($conn)
{
    echo "Columns in 'restaurants' table:\n";
    $result = $conn->query("SHOW COLUMNS FROM restaurants");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}

// ---------------------------------------------------------
// UNCOMMENT THE FUNCTIONS BELOW TO EXECUTE THEM
// ---------------------------------------------------------

// generatePasswordHash('admin@123');
// addMaxGuestsColumn($conn);
// checkRestaurantColumns($conn);

echo "Utility script loaded. Uncomment the functions at the bottom of the file to run them.\n";
?>