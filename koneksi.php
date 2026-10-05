<?php
$host = "localhost";
$user = "root";          // Username MySQL (default: root)
$pass = "";              // Password MySQL (kosongkan jika default)
$db   = "chinemax_db"; // Ganti dengan nama database kamu

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (mysqli_connect_errno()) {
    echo "Gagal terhubung ke MySQL: " . mysqli_connect_error();
    exit();
}
?>