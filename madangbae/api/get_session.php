<?php
// madangbae/api/get_session.php
session_start(); // Mulai sesi
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = ['isLoggedIn' => false];

if (isset($_SESSION['user_id'])) {
    $response['isLoggedIn'] = true;
    $response['user'] = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'] ?? null // full_name bisa null jika tidak diatur
    ];
}

echo json_encode($response);
?>