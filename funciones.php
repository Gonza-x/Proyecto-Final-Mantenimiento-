<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_hou_panama';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Error de conexión');
}
mysqli_set_charset($conn, 'utf8mb4');

session_start();
?>
