<?php
// Initialize database and create user table

include 'db.php';

// Create database if not exists
$mysqli->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db(DB_NAME);

// Create users table
$usersTable = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($mysqli->query($usersTable) === FALSE) {
    die("Error creating users table: " . $mysqli->error);
}

// Create default user if not exists
$email = 'patelankit.pa05@gmail.com';
$password = 'Anakaya@05';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$checkUser = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$checkUser->bind_param('s', $email);
$checkUser->execute();
$checkUser->store_result();

if ($checkUser->num_rows == 0) {
    // Insert default user
    $insertUser = $mysqli->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $insertUser->bind_param('ss', $email, $hashed_password);
    
    if ($insertUser->execute()) {
        echo "Default user created successfully!<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
    } else {
        echo "Error creating user: " . $mysqli->error;
    }
    
    $insertUser->close();
} else {
    echo "User already exists!<br>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
}

$checkUser->close();
$mysqli->close();

echo "<br><a href='login.php'>Go to Login Page</a>";
?>