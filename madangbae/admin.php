<?php
// madangbae/admin.php

// Sertakan file koneksi database
include 'api/db_connect.php';

$message = ''; // Untuk menampilkan pesan sukses atau error

// --- FUNGSI UNTUK MENAMBAH/EDIT PRODUK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $image_url = $_POST['image_url'];
    $category = $_POST['category'];
    $stock = intval($_POST['stock']);

    if ($id > 0) {
        // Mode Edit Produk
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, image_url=?, category=?, stock=? WHERE id=?");
        $stmt->bind_param("ssdsisi", $name, $description, $price, $image_url, $category, $stock, $id);
        if ($stmt->execute()) {
            $message = "<div class='alert success'>Produk berhasil diperbarui!</div>";
        } else {
            $message = "<div class='alert error'>Error memperbarui produk: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        // Mode Tambah Produk Baru
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, category, stock) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsis", $name, $description, $price, $image_url, $category, $stock);
        if ($stmt->execute()) {
            $message = "<div class='alert success'>Produk baru berhasil ditambahkan!</div>";
        } else {
            $message = "<div class='alert error'>Error menambahkan produk: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// --- FUNGSI UNTUK MENGHAPUS PRODUK ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "<div class='alert success'>Produk berhasil dihapus!</div>";
    } else {
        $message = "<div class='alert error'>Error menghapus produk: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// --- MENGAMBIL DATA PRODUK UNTUK DITAMPILKAN ---
$products = [];
$sql = "SELECT id, name, description, price, image_url, category, stock FROM products ORDER BY id DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close(); // Tutup koneksi setelah semua operasi database selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Produk</title>
    <link rel="stylesheet" href="style.css"> <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* CSS Tambahan untuk Admin Panel */
        body { background-color: #f4f7f6; }
        .admin-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .admin-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-section {
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }
        .form-section h2 {
            margin-top: 0;
            color: #555;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group textarea {
            resize: vertical;
        }
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 17px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #e67e22; /* sedikit lebih gelap */
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .product-table th, .product-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }
        .product-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        .product-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .product-table tr:hover {
            background-color: #e0e0e0;
        }
        .product-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-table .actions a {
            display: inline-block;
            margin-right: 10px;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .product-table .actions .edit-btn {
            background-color: #3498db;
            color: white;
        }
        .product-table .actions .edit-btn:hover {
            background-color: #2980b9;
        }
        .product-table .actions .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .product-table .actions .delete-btn:hover {
            background-color: #c0392b;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Manajemen Produk madangBae</h1>

        <?php echo $message; // Menampilkan pesan sukses/error ?>

        <div class="form-section">
            <h2>Tambah/Edit Produk</h2>
            <form action="admin.php" method="POST">
                <input type="hidden" name="id" id="product_id" value="0">
                
                <div class="form-group">
                    <label for="name">Nama Produk:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi:</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Harga (Rp):</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="image_url">URL Gambar:</label>
                    <input type="text" id="image_url" name="image_url" placeholder="Contoh: https://i.imgur.com/vHq1kS3.png">
                </div>

                <div class="form-group">
                    <label for="category">Kategori:</label>
                    <input type="text" id="category" name="category" placeholder="Contoh: Makanan Utama, Minuman" required>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stok:</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                
                <button type="submit" class="btn-submit">Simpan Produk</button>
                <button type="button" class="btn-submit" onclick="resetForm()">Batal/Tambah Baru</button>
            </form>
        </div>

        <div class="product-list-section">
            <h2>Daftar Produk</h2>
            <?php if (empty($products)): ?>
                <p>Belum ada produk. Tambahkan produk baru menggunakan formulir di atas.</p>
            <?php else: ?>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/80'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td class="actions">
                                    <a href="#" class="edit-btn" 
                                       data-id="<?php echo $product['id']; ?>" 
                                       data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                       data-description="<?php echo htmlspecialchars($product['description']); ?>" 
                                       data-price="<?php echo htmlspecialchars($product['price']); ?>" 
                                       data-image_url="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                       data-category="<?php echo htmlspecialchars($product['category']); ?>" 
                                       data-stock="<?php echo htmlspecialchars($product['stock']); ?>">Edit</a>
                                    <a href="admin.php?action=delete&id=<?php echo $product['id']; ?>" class="delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fungsi untuk mengisi form saat tombol "Edit" diklik
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('product_id').value = this.dataset.id;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('description').value = this.dataset.description;
                document.getElementById('price').value = this.dataset.price;
                document.getElementById('image_url').value = this.dataset.imageUrl; // Perhatikan: data-image-url jadi dataset.imageUrl
                document.getElementById('category').value = this.dataset.category;
                document.getElementById('stock').value = this.dataset.stock;
                
                // Gulir ke atas form
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        // Fungsi untuk mereset form
        function resetForm() {
            document.getElementById('product_id').value = "0";
            document.getElementById('name').value = "";
            document.getElementById('description').value = "";
            document.getElementById('price').value = "";
            document.getElementById('image_url').value = "";
            document.getElementById('category').value = "";
            document.getElementById('stock').value = "";
            // Jika ada pesan sukses/error, sembunyikan
            const alertDiv = document.querySelector('.alert');
            if (alertDiv) {
                alertDiv.remove();
            }
        }
    </script>
</body>
</html>