<?php
// madangbae/api/logout.php
session_start(); // Mulai sesi
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Hapus semua variabel sesi
$_SESSION = array();

// Jika ingin menghapus cookie sesi, perhatikan bahwa ini akan menghancurkan sesi,
// dan bukan hanya data sesi.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

echo json_encode(['status' => 'success', 'message' => 'Anda telah berhasil logout.']);
exit();
?>