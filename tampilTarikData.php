<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Tarik Data</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background-color: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .output-box { border: 1px solid #333; padding: 15px; border-radius: 5px; }
        .success { color: #4CAF50; font-weight: bold; }
        a button { margin-top: 20px; padding: 10px 15px; background-color: #007ACC; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

<div class="output-box">
<pre>
<?php
// Set batas waktu eksekusi agar tidak timeout untuk proses yang lama
set_time_limit(600); // 10 menit
// Mengatur agar output langsung dikirim ke browser
ob_implicit_flush(true);

echo "<strong>Memulai Proses...</strong>\n\n";

// --- Ambil dan amankan input dari form ---
$filename = $_POST["filename"];
$keyword = $_POST["keyword"];
$limit = $_POST["limit"];

// Validasi sederhana: pastikan filename diakhiri dengan .csv
if (substr($filename, -4) !== '.csv') {
    die("ERROR: Nama file harus diakhiri dengan .csv");
}

// Gunakan escapeshellarg untuk keamanan
$safe_filename = escapeshellarg($filename);
$safe_keyword = escapeshellarg($keyword);
$safe_limit = escapeshellarg($limit);


// --- LANGKAH 1: MENARIK DATA DARI TWITTER ---
echo "<strong>[1. Menjalankan skrip tarik data...]</strong>\n";
$argumen1 = 'python Tarik_Data.py ' . $safe_filename . ' ' . $safe_keyword . ' ' . $safe_limit;
passthru($argumen1);
echo "\n<strong>[1. Selesai menarik data.]</strong>\n\n";


// --- LANGKAH 2: MENGIMPOR DATA KE DATABASE ---
echo "<strong>[2. Menjalankan skrip impor ke database...]</strong>\n";
// Perbaikan: Tanda kutip penutup yang hilang sudah ditambahkan dan menggunakan escapeshellarg
$argumen2 = 'python csv_to_mysql.py ' . $safe_filename;
passthru($argumen2);
echo "\n<strong>[2. Selesai mengimpor data.]</strong>\n\n";

echo "<span class='success'>Semua Proses Selesai!</span>\n";
?>
</pre>
</div>

<a href="formTarikData.php">
    <button type="button">Kembali ke Form</button>
</a>

</body>
</html>