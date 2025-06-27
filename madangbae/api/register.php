<?php
// madangbae/api/register.php
header('Content-Type: application/json'); // Beritahu browser bahwa respons adalah JSON
header('Access-Control-Allow-Origin: *'); // Izinkan akses dari domain manapun (untuk development)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); // Izinkan metode POST, GET, OPTIONS
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'); // Izinkan header tertentu

// Sertakan file koneksi database
include 'db_connect.php';

$response = array(); // Array untuk menyimpan respons JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari input POST
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';

    // Validasi input sederhana
    if (empty($username) || empty($email) || empty($password)) {
        $response['status'] = 'error';
        $response['message'] = 'Username, email, dan password tidak boleh kosong.';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    // Enkripsi password menggunakan password_hash() untuk keamanan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Siapkan query untuk memeriksa apakah username atau email sudah ada
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $response['status'] = 'error';
        $response['message'] = 'Username atau email sudah terdaftar.';
    } else {
        // Siapkan query untuk memasukkan pengguna baru
        // Jangan sertakan created_at karena kita pakai CURRENT_TIMESTAMP di MySQL
        $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $full_name);

        if ($stmt_insert->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Registrasi berhasil! Silakan login.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Terjadi kesalahan saat registrasi: ' . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Metode request tidak valid.';
}

echo json_encode($response);
$conn->close();
?>