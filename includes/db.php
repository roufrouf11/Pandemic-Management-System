<?php

//automata enswmatwnw thn database
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "database_zx1";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Σφάλμα σύνδεσης: " . $conn->connect_error);
}
?>