<?php
// Menggunakan koneksi dari file terpisah
include 'koneksi.php';

// Definisi konstanta
define('TRENDING_BIGRAM_TABLE', 'trending_bigrams');

$python_output = '';
$trending_results = [];

// --- Logika untuk MENJALANKAN skrip Python ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['run_analysis'])) {
    set_time_limit(600); // Waktu eksekusi maksimal 600 detik
    ob_implicit_flush(true); 
    ob_end_flush();
    
    $command = 'python calculate_trending_bigrams.py 2>&1';
    $python_output .= "<div class='terminal-output'><strong>Menjalankan analisis DF-IDFt...</strong>\n\n";
    
    $handle = popen($command, 'r');
    while (!feof($handle)) {
        $line = fgets($handle);
        $python_output .= htmlspecialchars($line);
        echo str_pad('', 4096); 
    }
    pclose($handle);
    
    $python_output .= "\n<strong>Proses Python selesai.</strong></div>";
}

// --- Logika untuk MENAMPILKAN hasil dari database ---
$sql_show = "SELECT TS, bigram, DF, boost, DFIDF 
             FROM " . TRENDING_BIGRAM_TABLE . " 
             ORDER BY DFIDF DESC";

$result_show = $conn->query($sql_show);
if ($result_show && $result_show->num_rows > 0) {
    while ($row = $result_show->fetch_assoc()) {
        $trending_results[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Trending Bigram (DF-IDFt)</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        body { font-family: 'Poppins', 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; color: #333; }
        /* Header disesuaikan dengan warna kartu di dashboard */
        h1 { text-align: center; color: #493267; background-color: #e2d9f3; padding: 20px 0; margin: 0; font-size: 24px; font-weight: 600; border-bottom: 2px solid #d1c5e8;}
        h2 { color: #343a40; margin-top: 40px; border-bottom: 2px solid #e2d9f3; padding-bottom: 10px; }
        .container { width: 95%; max-width: 1200px; margin: 30px auto; background-color: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 30px; position: relative; }
        /* Tombol Kembali */
        .btn-back { display: inline-block; position: absolute; top: 25px; left: 30px; background-color: #6c757d; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: background-color 0.3s; }
        .btn-back:hover { background-color: #5a6268; }
        .process-container { text-align: center; padding: 30px; border: 1px dashed #ccc; border-radius: 8px; background-color: #f9f9f9; margin-bottom: 30px;}
        /* Tombol utama disesuaikan warnanya */
        .process-container button { padding: 15px 30px; border-radius: 8px; border: none; font-size: 18px; font-weight: bold; background-color: #493267; color: white; cursor: pointer; transition: background-color 0.3s; }
        .process-container button:hover { background-color: #3a2852; }
        .terminal-output { background-color: #222; color: #eee; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; white-space: pre-wrap; margin-top: 20px; max-height: 400px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; text-align: left; padding: 12px; }
        th { background-color: #f2f2f2; font-weight: 600; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dasboard.php" class="btn-back">&laquo; Kembali ke Dashboard</a>
        <h1>Analisis Trending Bigram (DF-IDFt)</h1>
        
        <div class="process-container">
            <p>Klik tombol di bawah untuk menjalankan ulang analisis trending bigram dari awal.</p>
            <form method="POST" action=""><button type="submit" name="run_analysis">Jalankan Analisis</button></form>
        </div>
        
        <?php if (!empty($python_output)) echo $python_output; ?>

        <h2>Hasil Perangkingan Bigram Teratas</h2>
        <?php if (!empty($trending_results)):
            ?>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>TS</th>
                        <th>Bigrams</th>
                        <th>DF</th>
                        <th>Boost</th>
                        <th>DF-IDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($trending_results as $data): ?>
                        <tr>
                            <td style="width:5%; text-align:center;"><?= $no++ ?></td>
                            <td style="width:5%; text-align:center;"><?= htmlspecialchars($data['TS']) ?></td>
                            <td><?= htmlspecialchars($data['bigram']) ?></td>
                            <td style="width:8%; text-align:center;"><?= htmlspecialchars($data['DF']) ?></td>
                            <td style="width:8%; text-align:center;"><?= number_format($data['boost'], 1) ?></td>
                            <td style="width:12%; font-weight:bold;"><?= number_format($data['DFIDF'], 4) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada data untuk ditampilkan. Silakan jalankan analisis terlebih dahulu.</p>
        <?php endif; ?>
    </div>
</body>
</html>