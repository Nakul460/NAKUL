<?php
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "intruder_detection";

// Establish the database connection
$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

// Check if the connection was successful
if (!$con) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Return the MySQLi connection object
return $con;
?>
