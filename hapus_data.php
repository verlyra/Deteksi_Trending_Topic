<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'twitter');
define('DATA_TABLE', 'trending');
define('SOURCE_COLUMN', 'sumber_file');

$message = ''; // Variabel untuk menyimpan pesan notifikasi

// --- Blok untuk memproses penghapusan data ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sumber_hapus'])) {
    
    $sumber_to_delete = $_POST['sumber_hapus'];
    
    // Koneksi ke database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi Gagal: " . $conn->connect_error);
    }

    // Menggunakan prepared statement untuk keamanan dari SQL Injection
    $sql_delete = "DELETE FROM " . DATA_TABLE . " WHERE " . SOURCE_COLUMN . " = ?";
    $stmt = $conn->prepare($sql_delete);
    
    if ($stmt) {
        $stmt->bind_param("s", $sumber_to_delete);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $message = "<div class='info-box success'>Berhasil! Sebanyak {$affected_rows} baris data dari sumber '<strong>" . htmlspecialchars($sumber_to_delete) . "</strong>' telah dihapus.</div>";
        } else {
            $message = "<div class='info-box error'>Gagal menghapus data: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='info-box error'>Gagal mempersiapkan statement: " . htmlspecialchars($conn->error) . "</div>";
    }
    
    $conn->close();
}

// --- Ambil daftar sumber file untuk dropdown (selalu dijalankan) ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneosi Gagal: " . $conn->connect_error);
}

$available_sources = [];
$sql_get_sources = "SELECT DISTINCT " . SOURCE_COLUMN . " FROM " . DATA_TABLE . " WHERE " . SOURCE_COLUMN . " IS NOT NULL AND " . SOURCE_COLUMN . " != '' ORDER BY " . SOURCE_COLUMN . " ASC";
$result_sources = $conn->query($sql_get_sources);
if ($result_sources && $result_sources->num_rows > 0) {
    while ($row = $result_sources->fetch_assoc()) {
        $available_sources[] = $row[SOURCE_COLUMN];
    }
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Data Berdasarkan Sumber File</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        h1 { text-align: center; color: white; background-color: #dc3545; padding: 20px 0; margin: 0; }
        .container { width: 95%; max-width: 800px; margin: 30px auto; background-color: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 30px; }
        .form-container { margin-top: 20px; padding: 25px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        .form-container label { font-weight: bold; margin-right: 15px; font-size: 1.1em; }
        .form-container select, .form-container button { padding: 12px 18px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; vertical-align: middle; }
        .info-box { padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid transparent; font-weight: 500;}
        .info-box.success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .info-box.error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info-box.neutral { background-color: #fff3cd; border-color: #ffeeba; color: #856404;}
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            cursor: pointer;
            border-color: #c82333;
            font-weight: bold;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }

        .page-actions { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .btn-back { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 5px; text-decoration: none; font-weight: bold; transition: background-color 0.3s; }
        .btn-back:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <h1>Hapus Data Tweet</h1>

    <div class="container">
        
        <div class="page-actions">
            <a href="dasboard.php" class="btn-back">Kembali ke Dashboard</a>
        </div>
        
        <!-- Menampilkan pesan notifikasi dari proses hapus -->
        <?php if (!empty($message)) echo $message; ?>

        <div class="info-box neutral">
            <strong>Perhatian:</strong> Tindakan ini akan menghapus semua data tweet yang berasal dari sumber file yang Anda pilih. Tindakan ini tidak dapat dibatalkan.
        </div>
        
        <div class="form-container">
            <!-- Form untuk memilih dan menghapus data -->
            <form method="POST" action="hapus_data.php" onsubmit="return confirm('APAKAH ANDA YAKIN? Semua data dari sumber file ini akan dihapus secara permanen.');">
                <label for="sumber_hapus">Pilih Sumber Data untuk Dihapus:</label>
                <select name="sumber_hapus" id="sumber_hapus" required>
                    <option value="">-- Pilih Sumber File --</option>
                    <?php
                    // Tampilkan daftar sumber file yang ada di database
                    if (!empty($available_sources)) {
                        foreach ($available_sources as $source) {
                            echo "<option value='" . htmlspecialchars($source) . "'>" . htmlspecialchars($source) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Tidak ada data untuk dihapus</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn-delete" <?= empty($available_sources) ? 'disabled' : '' ?>>Hapus Data</button>
            </form>
        </div>
    </div>
</body>
</html>