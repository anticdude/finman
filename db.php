<?php
// // Database configuration
// define('DB_HOST', 'localhost');
// define('DB_USER', 'u831088057_finman');
// define('DB_PASS', 'Anakaya@05');
// define('DB_NAME', 'u831088057_finman');

// // Connect to database
// $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
// if ($mysqli->connect_error) {
//     die("Connection failed: " . $mysqli->connect_error);
// }
// ?>


<?php
// Detect environment
$env = 'production'; 

if ($env === 'local') {
    // Local Development Database
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'finman');
} else if($env === 'production'){
    // Production Database
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u831088057_finman');
    define('DB_PASS', 'Anakaya@05');
    define('DB_NAME', 'u831088057_finman');
}else{
    die("Connection failed: " . $mysqli->connect_error);
}

// Connect to database
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
