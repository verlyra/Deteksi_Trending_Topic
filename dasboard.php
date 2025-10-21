<?php
// Bisa ditambah koneksi database di sini kalau mau menampilkan data ringkasan
// include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Analisis Trending Topic</title>
  <style>
    /* ======= GLOBAL STYLE ======= */
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background-color: #f4f4f4;
      color: #333;
    }

    header {
      background-color: #f5a941;
      color: white;
      padding: 20px;
      text-align: center;
      box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }

    h1 {
      margin: 0;
      font-size: 26px;
    }

    main {
      max-width: 1100px;
      margin: 50px auto;
      padding: 0 20px;
      text-align: center;
    }

    h2 {
      color: #545454;
      margin-bottom: 30px;
      font-size: 20px;
    }

    /* ======= CARD CONTAINER ======= */
    .card-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 25px;
    }

    /* ======= CARD STYLE ======= */
    .card {
      background-color: white;
      width: 280px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      cursor: pointer;
      overflow: hidden;
      text-decoration: none;
      color: inherit;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }

    .card-header {
      background-color: #f8c146;
      padding: 20px;
      font-size: 22px;
      font-weight: bold;
      color: white;
    }

    .card-body {
      padding: 25px;
      font-size: 15px;
      color: #444;
    }

    .card-footer {
      background-color: #fafafa;
      padding: 10px;
      font-size: 13px;
      color: #777;
      border-top: 1px solid #eee;
    }

    footer {
      text-align: center;
      margin-top: 60px;
      padding: 15px;
      font-size: 13px;
      color: #777;
    }

  </style>
</head>
<body>

  <header>
    <h1>Dashboard Analisis Trending Topic</h1>
  </header>

  <main>
    <h2>Silakan pilih menu yang ingin dijalankan</h2>

    <div class="card-container">
      <a href="formTarikData.php" class="card">
        <div class="card-header">📥 Tarik Data</div>
        <div class="card-body">
          Mengambil data terbaru dari sumber (misalnya API Twitter atau file CSV)
          untuk diproses lebih lanjut.
        </div>
        <div class="card-footer">Klik untuk mulai menarik data</div>
      </a>

      <a href="preprocessing.php" class="card">
        <div class="card-header">⚙️ Preprocessing</div>
        <div class="card-body">
          Membersihkan, menormalisasi, dan melakukan stemming pada teks agar siap
          untuk analisis lebih lanjut.
        </div>
        <div class="card-footer">Klik untuk memulai preprocessing</div>
      </a>

      <a href="hapus_data.php" class="card">
        <div class="card-header">🗑️ Hapus Data</div>
        <div class="card-body">
          Menghapus data yang sudah tidak diperlukan dari database untuk menjaga
          performa sistem.
        </div>
        <div class="card-footer">Klik untuk menghapus data</div>
      </a>
    </div>
  </main>

  <footer>
    &copy; <?php echo date('Y'); ?> | Deteksi Trending Topic 💻
  </footer>

</body>
</html>
