<?php
// Mengatur header agar browser tahu bahwa respons adalah JSON
header('Content-Type: application/json');

// Pastikan error PHP ditampilkan untuk debugging (HANYA UNTUK PENGEMBANGAN)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mulai buffer output untuk menangkap output yang tidak diinginkan
ob_start();

try {
    // Memuat file koneksi database.
    // PATH YANG SANGAT BENAR jika db_connect.php ada di folder YANG SAMA dengan get_products.php (yaitu folder 'api')
    require_once 'db_connect.php'; // Cukup nama file karena di direktori yang sama

    $products = []; // Array untuk menyimpan data produk

    // Periksa apakah koneksi berhasil sebelum melanjutkan
    if ($conn->connect_error) {
        // Hapus output buffer jika ada error koneksi
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
        exit(); // Hentikan eksekusi script
    }

    // Query SQL untuk mengambil semua produk
    // PASTIKAN SEMUA KOLOM INI (id, name, description, price, category, image_url) ADA DI TABEL 'products'
    // DAN NAMA KOLOMNYA BENAR DI DATABASE ANDA
    $sql = "SELECT id, name, description, price, category, image_url FROM products"; 
    $result = $conn->query($sql);

    if ($result === FALSE) { // Periksa apakah query berhasil dieksekusi atau ada error SQL
        // Hapus output buffer jika ada error query
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Query database gagal: ' . $conn->error . ' SQL: ' . $sql]);
        $conn->close();
        exit();
    }

    if ($result->num_rows > 0) {
        // Ambil setiap baris hasil dan tambahkan ke array products
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    // Jika tidak ada baris, array products akan tetap kosong, yang merupakan JSON kosong []
    // Ini tidak akan menyebabkan error JSON, hanya berarti tidak ada menu untuk ditampilkan.

    // Mengambil semua output buffer yang tidak diinginkan sebelum output JSON
    $unwanted_output = ob_get_clean();
    if (!empty($unwanted_output)) {
        error_log("Output tidak terduga sebelum JSON di get_products.php: " . $unwanted_output);
        // Anda bisa memilih untuk mengembalikan error jika ada output tidak terduga
        // echo json_encode(['status' => 'error', 'message' => 'Output tidak terduga sebelum JSON.']);
        // exit();
    }

    // Mengembalikan data produk dalam format JSON
    echo json_encode($products);

    // Menutup koneksi database
    $conn->close();

} catch (Throwable $e) {
    // Tangani error fatal yang mungkin terlewat di atas
    ob_end_clean(); // Pastikan buffer dibersihkan
    http_response_code(500); // Set status kode HTTP
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan internal server: ' . $e->getMessage() . ' di baris ' . $e->getLine()]);
    exit();
}
?>
