<?php
// api/update_order_status.php

require_once 'db_connect.php'; // Koneksi database Anda

header('Content-Type: application/json');
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk melakukan tindakan ini.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data JSON dari body request
$input = json_decode(file_get_contents('php://input'), true);

$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$action = isset($input['action']) ? $input['action'] : '';

if ($order_id === 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit();
}

$new_status = '';
// Tentukan status baru berdasarkan aksi
switch ($action) {
    case 'cancel':
        // Hanya bisa membatalkan pesanan jika statusnya 'pending' atau 'preparing'
        $current_order_status_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
        $stmt_check = $conn->prepare($current_order_status_sql);
        if ($stmt_check === false) {
            echo json_encode(['success' => false, 'message' => 'Gagal memeriksa status pesanan.']);
            exit();
        }
        $stmt_check->bind_param("ii", $order_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $current_status_row = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($current_status_row) {
            $current_status = $current_status_row['status'];
            if ($current_status === 'pending' || $current_status === 'preparing') {
                $new_status = 'cancelled';
            } else {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan pada status saat ini (' . htmlspecialchars($current_status) . ').']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.']);
            exit();
        }
        break;
    case 'complete':
        // Hanya bisa menyelesaikan pesanan jika statusnya 'on_delivery'
        $current_order_status_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
        $stmt_check = $conn->prepare($current_order_status_sql);
        if ($stmt_check === false) {
            echo json_encode(['success' => false, 'message' => 'Gagal memeriksa status pesanan.']);
            exit();
        }
        $stmt_check->bind_param("ii", $order_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $current_status_row = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($current_status_row) {
            $current_status = $current_status_row['status'];
            if ($current_status === 'on_delivery') {
                $new_status = 'delivered';
            } else {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak dapat diselesaikan pada status saat ini (' . htmlspecialchars($current_status) . ').']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.']);
            exit();
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        exit();
}

// Lakukan update status di database
$sql_update = "UPDATE orders SET status = ? WHERE id = ? AND user_id = ?";
$stmt_update = $conn->prepare($sql_update);

if ($stmt_update === false) {
    echo json_encode(['success' => false, 'message' => 'Gagal mempersiapkan statement update: ' . $conn->error]);
    exit();
}

$stmt_update->bind_param("sii", $new_status, $order_id, $user_id);

if ($stmt_update->execute()) {
    if ($stmt_update->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status pesanan berhasil diperbarui menjadi ' . htmlspecialchars($new_status) . '.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan status. Pesanan mungkin sudah dalam status tersebut atau tidak ditemukan/bukan milik Anda.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status pesanan: ' . $stmt_update->error]);
}

$stmt_update->close();
$conn->close();
?>