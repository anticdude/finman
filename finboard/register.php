<?php
include 'db.php';

// Create user with hashed password
$email = 'patelankit.pa05@gmail.com';
$password = 'Anakaya@05';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
$stmt->bind_param('ss', $email, $hashed_password);

if ($stmt->execute()) {
    echo "User created successfully!";
} else {
    echo "Error: " . $mysqli->error;
}

$stmt->close();
$mysqli->close();