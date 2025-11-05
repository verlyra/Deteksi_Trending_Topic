<?php
// --- Menambahkan logika untuk mengambil data ringkasan ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'twitter');
define('DATA_TABLE', 'trending');

// Inisialisasi variabel
$total_tweets = 0;
$total_sumber = 0;
$belum_diproses = 0;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    // Biarkan nilai default
} else {
    // Query 1: Total semua tweet
    $result_total = $conn->query("SELECT COUNT(*) as total FROM " . DATA_TABLE);
    if ($result_total) $total_tweets = $result_total->fetch_assoc()['total'];

    // Query 2: Jumlah sumber file unik
    $result_sumber = $conn->query("SELECT COUNT(DISTINCT sumber_file) as total FROM " . DATA_TABLE . " WHERE sumber_file IS NOT NULL AND sumber_file != ''");
    if ($result_sumber) $total_sumber = $result_sumber->fetch_assoc()['total'];
    
    // Query 3: Jumlah data yang belum diproses
    $result_unprocessed = $conn->query("SELECT COUNT(*) as total FROM " . DATA_TABLE . " WHERE final_processed_text IS NULL OR final_processed_text = ''");
    if ($result_unprocessed) $belum_diproses = $result_unprocessed->fetch_assoc()['total'];

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Analisis Trending Topic</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
    body { margin: 0; font-family: "Poppins", "Segoe UI", Arial, sans-serif; background-color: #f8f9fa; color: #333; }
    header { background: linear-gradient(90deg, #f5a941, #f8c146); color: white; padding: 25px; text-align: center; box-shadow: 0 3px 8px rgba(0,0,0,0.15); border-bottom: 3px solid #e49b3a; }
    h1 { margin: 0; font-size: 28px; font-weight: 600; }
    main { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
    h2 { text-align: center; color: #343a40; margin-bottom: 35px; font-size: 22px; font-weight: 600; }
    .summary-container { display: flex; justify-content: center; gap: 20px; margin-bottom: 50px; flex-wrap: wrap; }
    .summary-box { background-color: #ffffff; padding: 20px 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); text-align: center; flex: 1; min-width: 200px; border-left: 5px solid #007bff; }
    .summary-box .count { font-size: 2.5em; font-weight: 700; color: #007bff; display: block; }
    .summary-box .label { font-size: 1em; color: #555; font-weight: 500; }
    .summary-box.orange { border-color: #fd7e14; }
    .summary-box.orange .count { color: #fd7e14; }
    .summary-box.red { border-color: #dc3545; }
    .summary-box.red .count { color: #dc3545; }
    .card-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
    .card { background-color: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; overflow: hidden; text-decoration: none; color: inherit; display: flex; flex-direction: column; }
    .card:hover { transform: translateY(-8px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
    .card-header { padding: 25px; font-size: 24px; font-weight: 600; color: #333; border-bottom: 1px solid #eee; }
    .card-header.tarik { background-color: #d1ecf1; color: #0c5460; }
    .card-header.proses { background-color: #fff3cd; color: #856404; }
    .card-header.hapus { background-color: #f8d7da; color: #721c24; }
    .card-header.ringkas { background-color: #d4edda; color: #155724; }
    /* BARU: CSS untuk kartu Bigram */
    .card-header.bigram { background-color: #e2d9f3; color: #493267; }
    .card-body { padding: 20px 25px; font-size: 15px; color: #555; line-height: 1.6; flex-grow: 1; }
    footer { text-align: center; margin-top: 60px; padding: 20px; font-size: 14px; color: #777; }
  </style>
</head>
<body>
  <header><h1>Dashboard Analisis Trending Topic</h1></header>
  <main>
    <div class="summary-container">
        <div class="summary-box"><span class="count"><?= number_format($total_tweets) ?></span><span class="label">Total Tweet di Database</span></div>
        <div class="summary-box orange"><span class="count"><?= number_format($total_sumber) ?></span><span class="label">Jumlah Sumber Data</span></div>
        <div class="summary-box red"><span class="count"><?= number_format($belum_diproses) ?></span><span class="label">Data Belum Diproses</span></div>
    </div>
    <h2>Pilih Menu Operasi</h2>
    <div class="card-container">
      <a href="formTarikData.php" class="card">
        <div class="card-header tarik">📥 Tarik Data Baru</div>
        <div class="card-body">Mengambil data tweet terbaru berdasarkan kata kunci dan menyimpannya ke database.</div>
      </a>
      <a href="preprocessing.php" class="card">
        <div class="card-header proses">⚙️ Preprocessing Data</div>
        <div class="card-body">Membersihkan, menormalisasi, dan melakukan stemming pada teks agar siap untuk dianalisis.</div>
      </a>
      
      <!-- BARU: Kartu untuk menu Analisis Bigram -->
      <!-- Ubah kartu bigram menjadi seperti ini -->
      <a href="bigram.php" class="card">
        <div class="card-header bigram">📈 Analisis Trending</div>
        <div class="card-body">Menganalisis bigram yang sedang tren berdasarkan waktu (DF-IDFt) untuk menemukan topik yang sedang naik daun.</div>
      </a>
      
      <a href="data_counter.php" class="card">
        <div class="card-header ringkas">📊 Ringkasan Data</div>
        <div class="card-body">Melihat statistik dan rincian jumlah data per sumber file yang ada di database.</div>
      </a>
      <a href="hapus_data.php" class="card">
        <div class="card-header hapus">🗑️ Hapus Data</div>
        <div class="card-body">Menghapus data berdasarkan sumber file untuk membersihkan dataset yang tidak diperlukan.</div>
      </a>
    </div>
  </main>
  <footer>&copy; <?php echo date('Y'); ?> | Deteksi Trending Topic 💻</footer>
</body>
</html>