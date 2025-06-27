<?php
header('Content-Type: application/json');
session_start(); // Pastikan sesi dimulai untuk akses $_SESSION

// Debugging: Tampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); // Mulai output buffering

try {
    // Memuat file koneksi database. Path ini diasumsikan db_connect.php di folder 'api'
    require_once 'db_connect.php';

    // Periksa koneksi database
    if ($conn->connect_error) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
        exit();
    }

    // Pastikan pengguna sudah login
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk menempatkan pesanan.']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit();
    }

    $delivery_address = $data['delivery_address'] ?? null;
    $total_amount = $data['total_amount'] ?? null;
    $notes = $data['notes'] ?? null;
    $items = $data['items'] ?? []; // Array item pesanan

    if (!$delivery_address || !$total_amount || empty($items)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Data pesanan tidak lengkap.']);
        exit();
    }

    // Mulai transaksi
    $conn->begin_transaction();

    // Masukkan pesanan utama
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_date, delivery_address, total_amount, status, notes) VALUES (?, NOW(), ?, ?, 'pending', ?)");
    if ($stmt === FALSE) {
        throw new Exception('Prepare statement for orders failed: ' . $conn->error);
    }
    $stmt->bind_param("isds", $user_id, $delivery_address, $total_amount, $notes);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute statement for orders failed: ' . $stmt->error);
    }
    $order_id = $conn->insert_id;
    $stmt->close();

    // Masukkan item-item pesanan
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price_at_order, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    if ($item_stmt === FALSE) {
        throw new Exception('Prepare statement for order_items failed: ' . $conn->error);
    }

    foreach ($items as $item) {
        $product_id = $item['id'] ?? null;
        // PENTING: Pastikan 'name' ada dan ambil nilainya. Gunakan operator null coalescing.
        $product_name = $item['name'] ?? 'Unknown Product'; 
        $quantity = $item['quantity'] ?? null;
        $price_at_order = $item['price'] ?? null;
        $image_url = $item['image_url'] ?? null; 

        // Debugging: Log data item sebelum bind_param
        // error_log("Debug Item: ID=" . $product_id . ", Name=" . $product_name . ", Qty=" . $quantity . ", Price=" . $price_at_order . ", Image=" . $image_url);

        $item_stmt->bind_param("isisds", $order_id, $product_id, $product_name, $quantity, $price_at_order, $image_url);
        if (!$item_stmt->execute()) {
            throw new Exception('Execute statement for order_items failed: ' . $item_stmt->error);
        }
    }
    $item_stmt->close();

    // Komit transaksi jika semua berhasil
    $conn->commit();

    ob_end_clean();
    echo json_encode(['status' => 'success', 'message' => 'Pesanan berhasil ditempatkan!', 'order_id' => $order_id]);

} catch (Throwable $e) {
    // Rollback transaksi jika ada error
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    ob_end_clean();
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat menempatkan pesanan: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
