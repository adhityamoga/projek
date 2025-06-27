<?php
// api/get_orders.php

require_once 'db_connect.php';

header('Content-Type: application/json');
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk melihat pesanan.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Query untuk mengambil semua pesanan utama pengguna
$sql_orders = "SELECT id, status, total_amount, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt_orders = $conn->prepare($sql_orders);

if ($stmt_orders === false) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan statement pesanan: ' . $conn->error]);
    exit();
}

$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

$orders = [];
while ($order_row = $result_orders->fetch_assoc()) {
    $order_id = $order_row['id'];
    $order_row['items'] = []; // Inisialisasi array untuk item pesanan

    // Query untuk mengambil item-item spesifik untuk pesanan ini
    $sql_items = "
        SELECT oi.quantity, oi.price_at_order, p.name AS product_name, p.image_url
        FROM order_items AS oi
        JOIN products AS p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ";
    $stmt_items = $conn->prepare($sql_items);

    if ($stmt_items === false) {
        error_log("Gagal mempersiapkan statement item pesanan untuk order_id {$order_id}: " . $conn->error);
        continue;
    }

    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    while ($item_row = $result_items->fetch_assoc()) {
        $order_row['items'][] = $item_row;
    }
    $stmt_items->close();

    $orders[] = $order_row;
}

$stmt_orders->close();
$conn->close();

echo json_encode($orders);
?>