-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Jun 2025 pada 18.56
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `madangbae_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL COMMENT 'ID unik pesanan',
  `user_id` int(11) NOT NULL COMMENT 'ID pengguna yang memesan',
  `order_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Tanggal dan waktu pesanan',
  `total_amount` decimal(10,2) NOT NULL COMMENT 'Total harga pesanan',
  `delivery_address` text NOT NULL COMMENT 'Alamat pengiriman lengkap',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'Status pesanan (pending, preparing, delivered)',
  `notes` text DEFAULT NULL COMMENT 'Catatan tambahan dari pelanggan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `delivery_address`, `status`, `notes`) VALUES
(12, 4, '2025-06-26 23:30:55', 33000.00, 'QWERTYUJNBV', 'pending', NULL),
(13, 4, '2025-06-26 23:33:30', 33000.00, 'bghccgvugvuj', 'pending', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL COMMENT 'ID unik item pesanan',
  `order_id` int(11) NOT NULL COMMENT 'ID pesanan terkait',
  `product_id` int(11) NOT NULL COMMENT 'ID produk yang dipesan',
  `quantity` int(11) NOT NULL COMMENT 'Jumlah produk yang dipesan',
  `price_at_order` decimal(10,2) NOT NULL COMMENT 'Harga produk saat pesanan dibuat (penting!)',
  `product_name` varchar(255) NOT NULL COMMENT 'Nama produk saat dipesan (untuk riwayat)',
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_order`, `product_name`, `image_url`) VALUES
(15, 12, 13, 1, 25000.00, '0', 'http://localhost/madangbae/assets/image/Nasi-Goreng-Spesial.jpg'),
(16, 13, 13, 1, 25000.00, '0', 'http://localhost/madangbae/assets/image/Nasi-Goreng-Spesial.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_url`, `category`, `stock`, `created_at`) VALUES
(13, 'Nasi Goreng Spesial', 'Nasi goreng dengan telur, ayam suwir, sosis, dan kerupuk.', 25000.00, 'http://localhost/madangbae/assets/image/Nasi-Goreng-Spesial.jpg', 'Makanan', 48, '2025-06-26 08:13:19'),
(14, 'Mie Ayam Bakso', 'Mie ayam dengan topping ayam cincang, bakso, dan sawi hijau.', 22000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Mie+Ayam', 'Makanan', 43, '2025-06-26 08:13:19'),
(15, 'Ayam Geprek Sambal Matah', 'Ayam goreng crispy dengan sambal matah pedas segar.', 20000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Ayam+Geprek', 'Makanan', 60, '2025-06-26 08:13:19'),
(16, 'Sate Ayam Madura', '10 tusuk sate ayam dengan bumbu kacang khas Madura.', 30000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Sate+Ayam', 'Makanan', 35, '2025-06-26 08:13:19'),
(17, 'Soto Ayam Lamongan', 'Soto ayam kuah kuning dengan koya gurih dan telur rebus.', 28000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Soto+Ayam', 'Makanan', 40, '2025-06-26 08:13:19'),
(18, 'Gado-Gado Komplit', 'Aneka sayuran rebus dengan lontong, telur, dan bumbu kacang.', 23000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Gado-Gado', 'Makanan', 30, '2025-06-26 08:13:19'),
(19, 'Capcay Kuah Seafood', 'Capcay dengan udang, cumi, bakso ikan, dan sayuran segar.', 35000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Capcay', 'Makanan', 25, '2025-06-26 08:13:19'),
(20, 'Pempek Kapal Selam', 'Pempek kapal selam ukuran besar dengan kuah cuko pedas-manis.', 15000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Pempek', 'Makanan', 55, '2025-06-26 08:13:19'),
(21, 'Rendang Daging Sapi', 'Potongan daging sapi empuk dimasak dengan bumbu rendang kaya rasa.', 40000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Rendang', 'Makanan', 20, '2025-06-26 08:13:19'),
(22, 'Nasi Uduk Betawi', 'Nasi uduk lengkap dengan bihun goreng, telur balado, dan kerupuk.', 27000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Nasi+Uduk', 'Makanan', 30, '2025-06-26 08:13:19'),
(23, 'Es Teh Manis', 'Teh pilihan disajikan dingin dengan gula.', 8000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Es+Teh', 'Minuman', 100, '2025-06-26 08:13:19'),
(24, 'Es Jeruk Peras', 'Jeruk segar diperas, disajikan dingin.', 10000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Es+Jeruk', 'Minuman', 80, '2025-06-26 08:13:19'),
(25, 'Kopi Susu Dingin', 'Campuran kopi dan susu, disajikan dingin dengan es.', 15000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Kopi+Susu', 'Minuman', 70, '2025-06-26 08:13:19'),
(26, 'Jus Alpukat', 'Jus alpukat creamy dengan sentuhan cokelat.', 18000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Jus+Alpukat', 'Minuman', 65, '2025-06-26 08:13:19'),
(27, 'Es Cincau Hijau', 'Cincau hijau dengan santan dan sirup gula merah.', 12000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Es+Cincau', 'Minuman', 50, '2025-06-26 08:13:19'),
(28, 'Wedang Jahe Hangat', 'Minuman jahe hangat dengan gula merah dan serai.', 10000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Wedang+Jahe', 'Minuman', 40, '2025-06-26 08:13:19'),
(29, 'Es Campur', 'Aneka buah, cincau, kolang-kaling dengan sirup dan susu kental manis.', 17000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Es+Campur', 'Minuman', 30, '2025-06-26 08:13:19'),
(30, 'Teh Tarik', 'Teh susu kental manis yang ditarik hingga berbusa.', 14000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Teh+Tarik', 'Minuman', 55, '2025-06-26 08:13:19'),
(31, 'Mojito Non-Alkohol', 'Minuman segar dengan mint, lime, dan soda.', 16000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Mojito', 'Minuman', 60, '2025-06-26 08:13:19'),
(32, 'Air Mineral', 'Air mineral kemasan 600ml.', 5000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Air+Mineral', 'Minuman', 120, '2025-06-26 08:13:19'),
(33, 'Bakso Beranak', 'Bakso ukuran jumbo berisi bakso kecil, telur puyuh, dan tetelan.', 35000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Bakso+Beranak', 'Makanan', 30, '2025-06-26 08:14:40'),
(34, 'Nasi Bakar Ayam Suwir', 'Nasi bakar gurih dengan ayam suwir pedas dan kemangi, dibakar aroma daun pisang.', 26000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Nasi+Bakar', 'Makanan', 40, '2025-06-26 08:14:40'),
(35, 'Martabak Telor Spesial', 'Martabak telur krispi dengan daging cincang, disajikan dengan kuah cuka.', 28000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Martabak+Telor', 'Makanan', 25, '2025-06-26 08:14:40'),
(36, 'Bubur Ayam Jakarta', 'Bubur ayam lembut dengan topping lengkap: cakwe, kerupuk, kacang, dan suwiran ayam.', 18000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Bubur+Ayam', 'Makanan', 50, '2025-06-26 08:14:40'),
(37, 'Kwetiau Goreng Seafood', 'Kwetiau goreng dengan udang, cumi, telur, dan sayuran.', 29000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Kwetiau+Goreng', 'Makanan', 35, '2025-06-26 08:14:40'),
(38, 'Nasi Goreng Kampung', 'Nasi goreng sederhana ala rumahan dengan telur ceplok dan acar.', 24000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Nasi+Goreng+Kampung', 'Makanan', 45, '2025-06-26 08:14:40'),
(39, 'Tahu Gejrot', 'Tahu goreng dengan bumbu kuah asam pedas manis khas Cirebon.', 12000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Tahu+Gejrot', 'Makanan', 60, '2025-06-26 08:14:40'),
(40, 'Lontong Sayur Medan', 'Lontong disiram kuah sayur nangka khas Medan, lengkap dengan telur dan kerupuk.', 25000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Lontong+Sayur', 'Makanan', 30, '2025-06-26 08:14:40'),
(41, 'Sop Buntut', 'Sup buntut sapi empuk dengan kuah kaldu bening, kentang, wortel, dan emping.', 45000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Sop+Buntut', 'Makanan', 20, '2025-06-26 08:14:40'),
(42, 'Ikan Bakar Sambal Kecap', 'Ikan gurame bakar dengan bumbu kecap pedas dan irisan bawang merah.', 55000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Ikan+Bakar', 'Makanan', 15, '2025-06-26 08:14:40'),
(43, 'Es Kopi Susu Aren', 'Kopi susu dengan gula aren khas Indonesia, disajikan dingin.', 18000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Kopi+Susu+Aren', 'Minuman', 90, '2025-06-26 08:14:41'),
(44, 'Jus Mangga', 'Jus mangga segar dan manis.', 20000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Jus+Mangga', 'Minuman', 75, '2025-06-26 08:14:41'),
(45, 'Lemon Tea Dingin', 'Teh dengan perasan lemon segar, disajikan dingin.', 13000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Lemon+Tea', 'Minuman', 85, '2025-06-26 08:14:41'),
(46, 'Es Dawet Ayu', 'Minuman tradisional dawet dengan santan kelapa dan gula merah cair.', 15000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Es+Dawet', 'Minuman', 60, '2025-06-26 08:14:41'),
(47, 'Thai Tea', 'Teh Thailand dengan susu kental manis, disajikan dingin.', 16000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Thai+Tea', 'Minuman', 70, '2025-06-26 08:14:41'),
(48, 'Smoothie Strawberry Banana', 'Campuran buah strawberry dan pisang segar yang diblender.', 22000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Smoothie', 'Minuman', 40, '2025-06-26 08:14:41'),
(49, 'Es Doger', 'Es serut dengan tape ketan, alpukat, nangka, dan sirup merah.', 19000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Es+Doger', 'Minuman', 35, '2025-06-26 08:14:41'),
(50, 'Bandrek Hangat', 'Minuman rempah hangat khas Sunda dengan jahe, gula merah, dan santan.', 11000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Bandrek', 'Minuman', 45, '2025-06-26 08:14:41'),
(51, 'Air Kelapa Muda', 'Air kelapa murni dari kelapa muda segar.', 20000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Air+Kelapa', 'Minuman', 50, '2025-06-26 08:14:41'),
(52, 'Matcha Latte Dingin', 'Matcha premium dicampur susu, disajikan dingin.', 23000.00, 'https://placehold.co/180x180/EAEAEA/555555?text=Matcha+Latte', 'Minuman', 50, '2025-06-26 08:14:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'ID unik pengguna',
  `username` varchar(50) NOT NULL COMMENT 'Nama pengguna untuk login',
  `email` varchar(100) NOT NULL COMMENT 'Email pengguna (juga bisa untuk login)',
  `password` varchar(255) NOT NULL COMMENT 'Hash password yang aman',
  `full_name` varchar(100) DEFAULT NULL COMMENT 'Nama lengkap pengguna (opsional)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu pendaftaran'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `created_at`) VALUES
(4, 'mbuh', 'mbuh@gmail.com', '$2y$10$jCstSZ1Iijje0YrSqhCf0./AopkFm6KSynX1rR3FskOwMkcKIMtJi', 'mbuh', '2025-06-25 17:03:45'),
(5, '111', '111@gmail.com', '$2y$10$3bvFh..Hj5hZGjAY.1tknebTOGgL17THGJbx28fx.HDIhS2dTRqwq', '111', '2025-06-25 17:53:34');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik pesanan', AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik item pesanan', AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik pengguna', AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
