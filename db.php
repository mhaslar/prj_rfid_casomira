<?php

$servername = "127.0.0.1";
$username = "rfiduser";
$password = "tajneheslo";
$dbname = "zavodTEST";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Připojení k DB selhalo: " . $conn->connect_error);
}
?>