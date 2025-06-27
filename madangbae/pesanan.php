<?php
// 1. Menggunakan file koneksi database Anda yang sudah ada
require_once 'api/db_connect.php'; //

// Memulai session untuk mendapatkan user_id yang sedang login.
// Pastikan Anda sudah memiliki sistem login yang menyimpan 'user_id' di dalam session.
// Untuk tujuan pengujian, kita akan menggunakan user_id = 4 sesuai dengan data di database Anda.
session_start();
// Baris berikut bisa diaktifkan untuk simulasi jika sistem login belum ada
// $_SESSION['user_id'] = 4;
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 4; // Gunakan ID 4 jika session tidak ada

/**
 * Fungsi untuk mengambil data pesanan dari database berdasarkan status.
 * @param mysqli $db - Objek koneksi database ($conn dari db_connect.php)
 * @param int $userId - ID pengguna yang sedang login
 * @param array $statuses - Array berisi status pesanan yang ingin ditampilkan
 * @return array - Array data pesanan
 */
function getOrdersByStatus($db, $userId, $statuses)
{
    // Jika array status kosong, kembalikan array kosong untuk menghindari error SQL
    if (empty($statuses)) {
        return [];
    }

    // Mengubah array status menjadi string untuk klausa IN di SQL
    // Contoh: ['pending', 'preparing'] menjadi "'pending','preparing'"
    $statusList = "'" . implode("','", array_map([$db, 'real_escape_string'], $statuses)) . "'";

    // Query untuk mengambil pesanan dan item-itemnya
    $sql = "SELECT 
                o.id AS order_id, 
                o.order_date, 
                o.total_amount, 
                o.status, 
                o.delivery_address, 
                o.notes,
                GROUP_CONCAT(
                    CONCAT(oi.product_name, ' (', oi.quantity, 'x) - Rp ', oi.price_at_order)
                    ORDER BY oi.product_name SEPARATOR '; '
                ) AS items_list,
                GROUP_CONCAT(
                    CONCAT(oi.product_id, '::', oi.product_name, '::', oi.quantity, '::', oi.price_at_order, '::', p.image_url)
                    ORDER BY oi.product_name SEPARATOR '||'
                ) AS items_data_for_frontend
            FROM 
                orders o
            JOIN 
                order_items oi ON o.id = oi.order_id
            LEFT JOIN
                products p ON oi.product_id = p.id
            WHERE 
                o.user_id = ? AND o.status IN ($statusList)
            GROUP BY 
                o.id
            ORDER BY 
                o.order_date DESC";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: (" . $db->errno . ") " . $db->error);
        return [];
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Parse items_data_for_frontend ke dalam array PHP
        $items_data_raw = explode('||', $row['items_data_for_frontend']);
        $items_parsed = [];
        foreach ($items_data_raw as $item_str) {
            $parts = explode('::', $item_str);
            if (count($parts) === 5) { // Pastikan semua bagian ada
                $items_parsed[] = [
                    'product_id' => $parts[0],
                    'product_name' => $parts[1],
                    'quantity' => (int)$parts[2],
                    'price_at_order' => (float)$parts[3],
                    'image_url' => $parts[4]
                ];
            }
        }
        $row['items'] = $items_parsed; // Tambahkan array item yang sudah di-parse
        unset($row['items_data_for_frontend']); // Hapus kolom mentah
        $orders[] = $row;
    }

    $stmt->close();
    return $orders;
}

// Ambil koneksi database
$db = $conn; // Menggunakan $conn dari db_connect.php

// Ambil pesanan yang sedang berjalan (current)
$current_statuses = ['pending', 'preparing', 'on_delivery'];
$current_orders = getOrdersByStatus($db, $current_user_id, $current_statuses);

// Ambil pesanan yang sudah lewat (past)
$past_statuses = ['delivered', 'cancelled'];
$past_orders = getOrdersByStatus($db, $current_user_id, $past_statuses);

// Tutup koneksi database
$db->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>madangBae - Pesanan Saya</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<style>
    /* General body and container styling */
    body {
        background-color: #f4f4f4;
        color: #333;
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding-bottom: 80px;
        /* Space for footer */
    }

    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        box-sizing: border-box;
    }

    main {
        padding: 20px 0;
    }

    h1 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
        font-size: 2em;
    }

    /* Response Message (for success/error) */
    .response-message {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: 500;
        display: none;
        /* Hidden by default, shown by JS */
    }

    .response-message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .response-message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }


    /* Tabs styling */
    .tabs {
        display: flex;
        justify-content: center;
        margin-bottom: 25px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .tab-link {
        flex: 1;
        padding: 15px 0;
        text-align: center;
        text-decoration: none;
        color: #555;
        font-weight: 600;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }

    .tab-link:hover {
        color: #ff6f00;
    }

    .tab-link.active {
        color: #ff6f00;
        border-bottom: 3px solid #ff6f00;
        background-color: #fffaf5;
    }

    /* Order Sections */
    .order-sections {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .order-list {
        display: none;
        /* Hide all order lists by default */
    }

    .order-list.active-tab {
        display: block;
        /* Show only the active tab's content */
    }

    .order-list h2 {
        font-size: 1.5em;
        color: #333;
        margin-top: 0;
        margin-bottom: 20px;
        text-align: left;
    }

    .empty-orders-message {
        text-align: center;
        color: #666;
        padding: 30px;
        border: 1px dashed #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
        margin-bottom: 20px;
    }

    /* Order Card */
    .order-card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        margin-bottom: 20px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease-in-out;
    }

    .order-card:hover {
        transform: translateY(-5px);
    }

    .order-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .order-card-header h3 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .status {
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: 600;
        font-size: 0.85em;
        text-transform: capitalize;
    }

    /* Status specific colors */
    .status.pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status.preparing {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .status.on-delivery {
        background-color: #cce5ff;
        color: #004085;
    }

    .status.delivered {
        background-color: #d4edda;
        color: #155724;
    }

    .status.cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }

    .order-date {
        font-size: 0.9em;
        color: #777;
        margin-bottom: 15px;
    }

    .order-items-list-summary {
        margin-bottom: 15px;
    }

    .order-card-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        gap: 15px;
    }

    .order-card-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #eee;
        flex-shrink: 0;
    }

    .item-info {
        flex-grow: 1;
    }

    .item-name {
        margin: 0;
        font-weight: 500;
        color: #444;
        font-size: 0.95em;
    }

    .item-qty-price {
        margin: 0;
        font-size: 0.85em;
        color: #666;
    }

    .order-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #eee;
        padding-top: 15px;
        margin-top: 10px;
        flex-wrap: wrap;
        /* Allow buttons to wrap */
        gap: 10px;
        /* Space between items/buttons */
    }

    .total-price {
        font-size: 1.1em;
        font-weight: 700;
        color: #ff6f00;
        flex-grow: 1;
    }

    /* Action Buttons */
    .btn-action,
    .btn-reorder {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s ease, color 0.3s ease;
        white-space: nowrap;
        /* Prevent text wrapping */
        min-width: 100px;
        text-align: center;
    }

    .btn-reorder {
        background-color: #ff6f00;
        color: #fff;
    }

    .btn-reorder:hover {
        background-color: #e65c00;
    }

    .btn-cancel {
        background-color: #dc3545;
        /* Red for cancel */
        color: #fff;
    }

    .btn-cancel:hover {
        background-color: #c82333;
    }

    .btn-complete {
        background-color: #28a745;
        /* Green for complete */
        color: #fff;
    }

    .btn-complete:hover {
        background-color: #218838;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .tabs {
            flex-wrap: wrap;
            margin-left: 0;
            margin-right: 0;
        }

        .tab-link {
            flex-basis: 50%;
            /* Two tabs per line */
            border-bottom: none;
            /* Remove bottom border */
            border-right: 1px solid #eee;
            /* Add right border for separation */
        }

        .tab-link:last-child {
            border-right: none;
        }

        .tab-link.active {
            border-bottom: none;
            /* Keep active indicator for the active link */
        }

        .order-card {
            padding: 15px;
        }

        .order-card-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-action,
        .btn-reorder {
            width: 100%;
            /* Full width buttons */
            margin-bottom: 10px;
        }

        .total-price {
            text-align: center;
            margin-bottom: 10px;
        }
    }

    @media (max-width: 480px) {
        h1 {
            font-size: 1.8em;
        }

        .order-card-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .order-card-header h3,
        .status {
            width: 100%;
            text-align: left;
        }

        .status {
            font-size: 0.8em;
        }
    }
</style>

<body>

    <header>
        <div class="container">
            <div class="logo">
                <img src="https://i.imgur.com/gY5G115.png" alt="madangBae Logo">
                <span>madangBae</span>
            </div>
            <nav>
                <a href="index.html">Home</a>
                <a href="menu.html">Menu</a>
                <a href="pesanan.php" class="active">Pesanan</a>
                <a href="keranjang.html">Keranjang</a>
            </nav>
            <div class="user-actions">
                <span></span> <a href="profil.html" class="btn-signup">Profil Saya</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h1>Pesanan Saya</h1>
            <div id="responseMessage" class="response-message" style="display: none;"></div>

            <div class="tabs">
                <a href="#current-orders" class="tab-link active" data-tab="current-orders">Sedang Berjalan</a>
                <a href="#past-orders" class="tab-link" data-tab="past-orders">Riwayat</a>
            </div>

            <div class="order-sections">
                <section id="current-orders" class="order-list active-tab">
                    <h2>Pesanan Sedang Berjalan</h2>
                    <?php if (empty($current_orders)): ?>
                        <p class="empty-orders-message">Tidak ada pesanan yang sedang berjalan saat ini.</p>
                    <?php else: ?>
                        <?php foreach ($current_orders as $order): ?>
                            <div class="order-card" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <div class="order-card-header">
                                    <h3>Order #<?php echo htmlspecialchars($order['order_id']); ?></h3>
                                    <span class="status <?php echo htmlspecialchars(str_replace('_', '-', $order['status'])); ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))); ?>
                                    </span>
                                </div>
                                <p class="order-date">Tanggal: <?php echo (new DateTime($order['order_date']))->format('d M Y, H:i'); ?></p>
                                <div class="order-items-list-summary">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-card-item">
                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://placehold.co/60x60/EAEAEA/555555?text=No+Img'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            <div class="item-info">
                                                <p class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                                <p class="item-qty-price"><?php echo htmlspecialchars($item['quantity']); ?>x Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="order-card-footer">
                                    <span class="total-price">Total: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                                    <button class="btn-action btn-cancel" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">Batalkan</button>
                                    <?php if ($order['status'] === 'on_delivery'): ?>
                                        <button class="btn-action btn-complete" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">Selesai</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <section id="past-orders" class="order-list">
                    <h2>Riwayat Pesanan</h2>
                    <?php if (empty($past_orders)): ?>
                        <p class="empty-orders-message">Belum ada riwayat pesanan.</p>
                    <?php else: ?>
                        <?php foreach ($past_orders as $order): ?>
                            <div class="order-card" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <div class="order-card-header">
                                    <h3>Order #<?php echo htmlspecialchars($order['order_id']); ?></h3>
                                    <span class="status <?php echo htmlspecialchars(str_replace('_', '-', $order['status'])); ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))); ?>
                                    </span>
                                </div>
                                <p class="order-date">Tanggal: <?php echo (new DateTime($order['order_date']))->format('d M Y, H:i'); ?></p>
                                <div class="order-items-list-summary">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-card-item">
                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://placehold.co/60x60/EAEAEA/555555?text=No+Img'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            <div class="item-info">
                                                <p class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                                <p class="item-qty-price"><?php echo htmlspecialchars($item['quantity']); ?>x Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="order-card-footer">
                                    <span class="total-price">Total: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                                    <button class="btn-reorder" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">Pesan Lagi</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 madangBae. All rights reserved.</p>
        </div>
    </footer>
    </div>

    <script src="js/app.js"></script>
</body>

</html>