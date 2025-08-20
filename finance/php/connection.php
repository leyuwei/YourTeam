<?php

$servername = "localhost";
$username = "www-data";
$password = "";
$dbname = "receipts";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("{'result': '0', 'content': 'connection failed'}");
}

$conn->set_charset("utf8");

?>