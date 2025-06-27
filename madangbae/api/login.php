<?php
// madangbae/api/login.php
session_start(); // Mulai sesi PHP
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include 'db_connect.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_or_email = $_POST['username_or_email'] ?? ''; // Bisa username atau email
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $response['status'] = 'error';
        $response['message'] = 'Username/Email dan password tidak boleh kosong.';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    // Cari pengguna berdasarkan username atau email
    $stmt = $conn->prepare("SELECT id, username, email, password, full_name FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verifikasi password yang diinput dengan hash password di database
        if (password_verify($password, $user['password'])) {
            // Password cocok, buat sesi pengguna
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            $response['status'] = 'success';
            $response['message'] = 'Login berhasil!';
            $response['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email']
            ];
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Password salah.';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Username atau email tidak terdaftar.';
    }
    $stmt->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
$conn->close();
?>