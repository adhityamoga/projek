<?php
// db_connect.php

// Detail koneksi database
$servername = "localhost"; // Biasanya 'localhost' untuk XAMPP
$username = "root";        // Username default XAMPP
$password = "";            // Password default XAMPP adalah kosong
$dbname = "madangbae_db";     // Nama database Anda (pastikan ini sama dengan nama database Anda)

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Mengatur charset menjadi UTF-8 (sangat direkomendasikan)
if (!$conn->set_charset("utf8")) {
    // Jika gagal mengatur charset (jarang terjadi tapi bisa jadi warning), log saja
    error_log("Gagal mengatur charset database: " . $conn->error);
}

// Catatan: Script ini tidak mengembalikan output JSON.
// Hanya membuat objek koneksi $conn yang akan digunakan oleh script lain.
?>
