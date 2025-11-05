<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'twitter');
define('DATA_TABLE', 'trending');

// --- Logika untuk mengambil data statistik ---

// Koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// 1. Query untuk menghitung total semua data
$total_semua_data = 0;
$result_total = $conn->query("SELECT COUNT(*) as total FROM " . DATA_TABLE);
if ($result_total) {
    $total_semua_data = $result_total->fetch_assoc()['total'];
}

// 2. Query untuk mendapatkan rincian per sumber file, termasuk counter
$data_per_sumber = [];
// Query dimodifikasi untuk mengambil nilai MAX dari counter
$sql_details = "
    SELECT
        sumber_file,
        COUNT(*) as total_data,
        SUM(CASE WHEN final_processed_text IS NOT NULL AND final_processed_text != '' THEN 1 ELSE 0 END) as sudah_diproses,
        SUM(CASE WHEN final_processed_text IS NULL OR final_processed_text = '' THEN 1 ELSE 0 END) as belum_diproses,
        MAX(counter) as times_processed
    FROM
        " . DATA_TABLE . "
    WHERE
        sumber_file IS NOT NULL AND sumber_file != ''
    GROUP BY
        sumber_file
    ORDER BY
        sumber_file ASC
";

$result_details = $conn->query($sql_details);
if ($result_details && $result_details->num_rows > 0) {
    while ($row = $result_details->fetch_assoc()) {
        $data_per_sumber[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Data Tweet</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        h1 { text-align: center; color: white; background-color: #007bff; padding: 20px 0; margin: 0; }
        h2 { color: #343a40; margin-top: 40px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .container { width: 95%; max-width: 1200px; margin: 30px auto; background-color: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 30px; }
        
        /* Kartu Ringkasan */
        .summary-cards {
            display: flex;
            gap: 25px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .card {
            flex: 1;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            text-align: center;
            min-width: 250px;
        }
        .card .count {
            font-size: 3.5em;
            font-weight: 700;
            margin: 0;
        }
        .card .label {
            font-size: 1.1em;
            font-weight: 500;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .card.secondary {
             background: linear-gradient(135deg, #6c757d, #495057);
             box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        /* Tabel Rincian */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { border: 1px solid #ddd; text-align: left; padding: 14px; font-size: 15px; }
        th { background-color: #f2f2f2; font-weight: 600; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #e9ecef; }
        
        /* Badge Status */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }
        .status-ok { background-color: #28a745; }
        .status-pending { background-color: #ffc107; color: #333; }

        .page-actions { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .btn-back { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 5px; text-decoration: none; font-weight: bold; transition: background-color 0.3s; }
        .btn-back:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <h1>Ringkasan Data Tweet</h1>

    <div class="container">
        <div class="page-actions">
            <a href="dasboard.php" class="btn-back">Kembali ke Dashboard</a>
        </div>
        
        <h2>Ringkasan Umum</h2>
        <div class="summary-cards">
            <div class="card">
                <p class="count"><?= number_format($total_semua_data) ?></p>
                <p class="label">Total Data Tweet</p>
            </div>
            <div class="card secondary">
                <p class="count"><?= count($data_per_sumber) ?></p>
                <p class="label">Jumlah Sumber File</p>
            </div>
        </div>

        <h2>Rincian per Sumber File</h2>
        <?php if (!empty($data_per_sumber)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nama Sumber File</th>
                        <th>Total Data</th>
                        <th>Sudah Diproses</th>
                        <th>Kali Diproses</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_per_sumber as $sumber): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sumber['sumber_file']) ?></strong></td>
                            <td><?= number_format($sumber['total_data']) ?></td>
                            <td><?= number_format($sumber['sudah_diproses']) ?></td>
                            <td style="text-align: center; font-weight: bold;">
                                <?= ($sumber['times_processed'] > 0) ? $sumber['times_processed'] : 'Belum' ?>
                            </td>
                            <td>
                                <?php if ($sumber['belum_diproses'] == 0 && $sumber['total_data'] > 0): ?>
                                    <span class="status-badge status-ok">Lengkap</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; font-size: 1.1em; color: #6c757d; padding: 20px; border: 1px dashed #ccc; border-radius: 8px;">
                Tidak ada data ditemukan di dalam database.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>