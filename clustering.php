<?php
// Proses jika form disubmit
$hasil_clustering = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_slots = isset($_POST['time_slots']) ? intval($_POST['time_slots']) : 2;
    $max_iterations = isset($_POST['max_iterations']) ? intval($_POST['max_iterations']) : 15;
    $use_entity_boost = isset($_POST['use_entity_boost']) ? 1 : 0;
    
    // Panggil script Python
    $python_script = __DIR__ . '/clusteringBE.py';
    
    // Cek apakah file Python ada
    if (!file_exists($python_script)) {
        $error_message = "File clusteringBE.py tidak ditemukan di: " . $python_script;
    } else {
        $command = sprintf(
            'python "%s" %d %d %d 2>&1',
            $python_script,
            $time_slots,
            $max_iterations,
            $use_entity_boost
        );
        
        $output = shell_exec($command);
        
        // Parse JSON output dari Python
        if ($output) {
            $hasil_clustering = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error_message = "Error parsing hasil: " . htmlspecialchars($output);
            }
        } else {
            $error_message = "Gagal menjalankan script Python. Pastikan Python terinstall dan path sudah benar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analisis Clustering Hierarchical</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
    body { margin: 0; font-family: "Poppins", "Segoe UI", Arial, sans-serif; background-color: #f8f9fa; color: #333; }
    header { background: linear-gradient(90deg, #6f42c1, #8e6fc7); color: white; padding: 25px; text-align: center; box-shadow: 0 3px 8px rgba(0,0,0,0.15); border-bottom: 3px solid #5a32a3; }
    h1 { margin: 0; font-size: 28px; font-weight: 600; }
    .back-btn { display: inline-block; margin: 20px 0 0 20px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.3s; }
    .back-btn:hover { background-color: #5a6268; transform: translateY(-2px); }
    main { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
    .info-box { background: white; border-radius: 10px; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .info-box h2 { margin-top: 0; color: #6f42c1; font-size: 22px; border-bottom: 2px solid #6f42c1; padding-bottom: 10px; }
    .info-box p { line-height: 1.7; color: #555; }
    .form-box { background: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .form-box h2 { margin-top: 0; color: #6f42c1; font-size: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #495057; }
    .form-group input[type="number"] { width: 100%; max-width: 200px; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 15px; }
    .form-group input[type="checkbox"] { width: auto; margin-right: 8px; }
    .btn-submit { background: linear-gradient(90deg, #6f42c1, #8e6fc7); color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(111,66,193,0.3); }
    .result-box { background: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .result-box h2, .result-box h3 { color: #6f42c1; }
    .result-box h2 { margin-top: 0; font-size: 22px; border-bottom: 2px solid #6f42c1; padding-bottom: 10px; }
    .result-box h3 { font-size: 18px; margin-top: 30px; margin-bottom: 15px; }
    .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; overflow-x: auto; display: block; }
    table thead { background-color: #6f42c1; color: white; }
    table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; white-space: nowrap; }
    table th { font-weight: 600; position: sticky; top: 0; }
    table tr:hover { background-color: #f8f9fa; }
    .tweet-card { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #6f42c1; }
    .tweet-card .tweet-id { font-weight: 600; color: #6f42c1; margin-bottom: 5px; }
    .tweet-card .tweet-text { color: #333; margin-bottom: 5px; }
    .tweet-card .tweet-meta { font-size: 0.85em; color: #666; }
    .iteration-block { background: #f8f9fa; padding: 20px; margin: 25px 0; border-radius: 8px; border-left: 5px solid #6f42c1; }
    .iteration-block h4 { margin: 0 0 15px 0; color: #6f42c1; font-size: 18px; }
    .cluster-name { background: #e2d9f3; padding: 6px 12px; border-radius: 4px; display: inline-block; margin: 5px; font-family: monospace; font-size: 14px; }
    .matrix-wrapper { overflow-x: auto; margin: 20px 0; }
    .matrix-table { min-width: 800px; }
    .matrix-table th:first-child, .matrix-table td:first-child { position: sticky; left: 0; background: #6f42c1; color: white; z-index: 1; }
    .matrix-table td:first-child { background: #f8f9fa; color: #333; font-weight: 600; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; }
    .badge-entity { background: #d4edda; color: #155724; }
    .badge-normal { background: #e2e3e5; color: #383d41; }
    pre { background: #263238; color: #aed581; padding: 15px; border-radius: 6px; overflow-x: auto; }
    .highlight-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin: 15px 0; }
    footer { text-align: center; margin-top: 60px; padding: 20px; font-size: 14px; color: #777; }
  </style>
</head>
<body>
  <header>
    <h1>📊 Analisis Clustering Hierarchical</h1>
    <p style="margin: 10px 0 0 0; opacity: 0.9;">Metode Average Linkage untuk Pengelompokan N-Grams</p>
  </header>
  
  <div style="display: flex; gap: 10px; margin: 20px 0 0 20px;">
    <a href="dasboard.php" class="back-btn">← Kembali ke Dashboard</a>
    <a href="dendrogram.php" class="back-btn" style="background: linear-gradient(90deg, #9c27b0, #ba68c8);">🌳 Lihat Dendrogram</a>
  </div>
  
  <main>
    <div class="info-box">
      <h2>ℹ️ Tentang Analisis Clustering</h2>
      <p>Analisis ini menggunakan <strong>data contoh dari penelitian</strong> tentang trending topic Twitter yang membahas topik <strong>Ahok, Jokowi, dan Natuna</strong>. Data terdiri dari 5 tweet yang dibagi dalam 2 time slot untuk mendemonstrasikan cara kerja algoritma.</p>
      <p><strong>Metode yang Digunakan:</strong></p>
      <ul style="line-height: 1.8;">
        <li><strong>DF-IDF Temporal:</strong> Menghitung bobot term berdasarkan frekuensi kemunculan pada time slot tertentu</li>
        <li><strong>Binary Matrix:</strong> Merepresentasikan keberadaan n-gram dalam setiap tweet (1 = ada, 0 = tidak ada)</li>
        <li><strong>Distance Calculation:</strong> Menghitung jarak antar n-gram dengan formula: <code>1 - (A / min(B,C))</code></li>
        <li><strong>Hierarchical Clustering:</strong> Menggabungkan n-gram secara bertahap menggunakan Average Linkage</li>
      </ul>
    </div>

    <div class="form-box">
      <h2>⚙️ Konfigurasi Analisis</h2>
      <form method="POST">
        <div class="form-group">
          <label for="time_slots">Jumlah Time Slots:</label>
          <input type="number" id="time_slots" name="time_slots" value="2" min="1" max="5" readonly>
          <small style="color: #6c757d; display: block; margin-top: 5px;">Data contoh menggunakan 2 time slots (fixed)</small>
        </div>
        
        <div class="form-group">
          <label for="max_iterations">Maksimal Iterasi Clustering:</label>
          <input type="number" id="max_iterations" name="max_iterations" value="15" min="5" max="50">
          <small style="color: #6c757d; display: block; margin-top: 5px;">Jumlah maksimal iterasi penggabungan cluster</small>
        </div>
        
        <div class="form-group">
          <label>
            <input type="checkbox" name="use_entity_boost" checked>
            Gunakan Entity Boost (1.5x untuk Person/Location/Organization)
          </label>
          <small style="color: #6c757d; display: block; margin-top: 5px;">Memberikan bobot lebih pada kata yang merupakan entitas bernama</small>
        </div>
        
        <button type="submit" class="btn-submit">🚀 Jalankan Analisis</button>
      </form>
    </div>

    <?php if ($error_message): ?>
    <div class="error-box">
      <strong>⚠️ Error:</strong> <?= $error_message ?>
    </div>
    <?php endif; ?>

    <?php if ($hasil_clustering && isset($hasil_clustering['status']) && $hasil_clustering['status'] === 'success'): ?>
    
    <!-- Data Tweet Asli -->
    <div class="result-box">
      <h2>📝 Data Tweet Contoh</h2>
      <p>Total: <strong><?= $hasil_clustering['total_tweets'] ?> tweet</strong> dalam <strong><?= $hasil_clustering['time_slots'] ?> time slots</strong></p>
      
      <?php if (isset($hasil_clustering['tweets_data'])): ?>
        <?php foreach ($hasil_clustering['tweets_data'] as $tweet): ?>
        <div class="tweet-card">
          <div class="tweet-id"><?= htmlspecialchars($tweet['id']) ?> - Slot <?= $tweet['time_slot'] ?></div>
          <div class="tweet-text">"<?= htmlspecialchars($tweet['text']) ?>"</div>
          <div class="tweet-meta">Tokens: <?= implode(', ', $tweet['tokens']) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Binary Matrix -->
    <?php if (isset($hasil_clustering['binary_matrix'])): ?>
    <div class="result-box">
      <h2>🔢 Binary Matrix (Pemetaan N-Grams)</h2>
      <p>Matriks yang menunjukkan keberadaan setiap n-gram dalam tweet (1 = ada, 0 = tidak ada)</p>
      
      <div class="matrix-wrapper">
        <table class="matrix-table">
          <thead>
            <tr>
              <th>Tweet ID</th>
              <?php foreach ($hasil_clustering['target_ngrams'] as $ngram): ?>
              <th><?= htmlspecialchars($ngram) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php 
            $binary_matrix = $hasil_clustering['binary_matrix'];
            $tweet_ids = ['T1', 'T2', 'T3', 'T4', 'T5'];
            foreach ($tweet_ids as $tid):
            ?>
            <tr>
              <td><?= $tid ?></td>
              <?php foreach ($hasil_clustering['target_ngrams'] as $ngram): ?>
              <td style="text-align: center; <?= $binary_matrix[$ngram][$tid] == 1 ? 'background: #d4edda; font-weight: 600;' : '' ?>">
                <?= $binary_matrix[$ngram][$tid] ?>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Distance Matrix -->
    <?php if (isset($hasil_clustering['initial_distance_matrix'])): ?>
    <div class="result-box">
      <h2>📏 Distance Matrix (Matriks Jarak Awal)</h2>
      <p>Jarak antar n-gram dihitung dengan formula: <code>1 - (A / min(B,C))</code></p>
      <p style="color: #666; font-size: 14px;">Dimana A = jumlah tweet yang mengandung kedua n-gram, B = jumlah tweet yang mengandung n-gram pertama, C = jumlah tweet yang mengandung n-gram kedua</p>
      
      <div class="matrix-wrapper">
        <table class="matrix-table">
          <thead>
            <tr>
              <th style="min-width: 120px;">N-Gram</th>
              <?php foreach ($hasil_clustering['initial_distance_matrix']['columns'] as $col): ?>
              <th style="min-width: 100px;"><?= htmlspecialchars($col) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hasil_clustering['initial_distance_matrix']['data'] as $row): ?>
            <tr>
              <td style="font-weight: 600;"><?= htmlspecialchars($row['row_name']) ?></td>
              <?php foreach ($hasil_clustering['initial_distance_matrix']['columns'] as $col): ?>
              <?php 
                $value = $row['values'][$col];
                $is_zero = ($value == 0);
              ?>
              <td style="text-align: center; <?= $is_zero ? 'background: #e2e3e5;' : '' ?>">
                <?= number_format($value, 4) ?>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- DF-IDF Scores -->
    <?php if (isset($hasil_clustering['df_idf_scores'])): ?>
    <div class="result-box">
      <h2>📈 Skor DF-IDF Temporal</h2>
      <p>Skor bobot term berdasarkan frekuensi kemunculan pada time slot tertentu</p>
      
      <table>
        <thead>
          <tr>
            <th>Term</th>
            <th>Slot 1 Freq</th>
            <th>Slot 2 Freq</th>
            <th>Skor DF-IDF</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hasil_clustering['df_idf_scores'] as $score): ?>
          <tr>
            <td><strong><?= htmlspecialchars($score['term']) ?></strong></td>
            <td style="text-align: center;"><?= $score['slot1_freq'] ?></td>
            <td style="text-align: center;"><?= $score['slot2_freq'] ?></td>
            <td style="text-align: center;"><strong><?= number_format($score['score'], 4) ?></strong></td>
            <td>
              <?php if ($score['is_entity']): ?>
                <span class="badge badge-entity">Entity (1.5x boost)</span>
              <?php else: ?>
                <span class="badge badge-normal">Normal</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Clustering Iterations -->
    <?php if (isset($hasil_clustering['clustering_iterations'])): ?>
    <div class="result-box">
      <h2>🔄 Proses Iterasi Clustering</h2>
      <p>Penggabungan n-gram secara bertahap menggunakan metode Average Linkage</p>
      
      <?php foreach ($hasil_clustering['clustering_iterations'] as $iter): ?>
      <div class="iteration-block">
        <?php if ($iter['iteration'] === 'final'): ?>
          <h4>✅ Matriks Akhir</h4>
          <p>Clustering selesai dengan cluster tunggal: <span class="cluster-name"><?= htmlspecialchars($iter['new_cluster']) ?></span></p>
        <?php else: ?>
          <h4>Iterasi <?= $iter['iteration'] ?>: Jarak Minimum = <?= number_format($iter['distance'], 4) ?></h4>
          <p>
            Menggabungkan: 
            <span class="cluster-name"><?= htmlspecialchars($iter['merged_items'][0]) ?></span>
            +
            <span class="cluster-name"><?= htmlspecialchars($iter['merged_items'][1]) ?></span>
            →
            <span class="cluster-name"><?= htmlspecialchars($iter['new_cluster']) ?></span>
          </p>
        <?php endif; ?>
        
        <?php if (isset($iter['matrix_before'])): ?>
        <div class="matrix-wrapper" style="margin-top: 15px;">
          <p style="margin-bottom: 10px; font-weight: 500; color: #6f42c1;">
            📊 Matriks Jarak (Ukuran: <?= $iter['matrix_size'] ?>×<?= $iter['matrix_size'] ?>)
          </p>
          <table class="matrix-table" style="font-size: 13px;">
            <thead>
              <tr>
                <th style="min-width: 150px;">N-Gram</th>
                <?php foreach ($iter['matrix_before']['columns'] as $col): ?>
                <th style="min-width: 80px;"><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($iter['matrix_before']['data'] as $row): ?>
              <tr>
                <td style="font-weight: 600;"><?= htmlspecialchars($row['row_name']) ?></td>
                <?php foreach ($iter['matrix_before']['columns'] as $col): ?>
                <?php 
                  $value = $row['values'][$col];
                  $is_inf = ($value === 'inf');
                  $is_zero = (!$is_inf && $value == 0);
                  $is_min = (!$is_inf && !$is_zero && $iter['iteration'] !== 'final' && 
                            $value == $iter['distance'] && 
                            (($row['row_name'] == $iter['merged_items'][0] && $col == $iter['merged_items'][1]) ||
                             ($row['row_name'] == $iter['merged_items'][1] && $col == $iter['merged_items'][0])));
                  
                  $style = '';
                  $display_value = '';
                  
                  if ($is_inf) {
                    $style = 'background: #e9ecef; color: #999;';
                    $display_value = '∞';
                  } elseif ($is_zero) {
                    $style = 'background: #e2e3e5;';
                    $display_value = '0.0000';
                  } elseif ($is_min) {
                    $style = 'background: #fff3cd; font-weight: 700; color: #856404;';
                    $display_value = number_format($value, 4);
                  } else {
                    $display_value = number_format($value, 4);
                  }
                ?>
                <td style="text-align: center; <?= $style ?>"><?= $display_value ?></td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        
        <?php if ($iter['iteration'] !== 'final'): ?>
        <small style="color: #666; display: block; margin-top: 10px;">
          Setelah penggabungan, ukuran matriks menjadi: <?= $iter['matrix_size'] - 1 ?> cluster
        </small>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Final Cluster -->
    <?php if (isset($hasil_clustering['final_cluster'])): ?>
    <div class="result-box">
      <h2>🎯 Hasil Akhir Clustering</h2>
      <div class="highlight-box">
        <p style="margin: 0 0 10px 0;"><strong>Semua n-gram telah digabung menjadi satu cluster utama:</strong></p>
        <pre style="background: transparent; color: #6f42c1; margin: 0; font-size: 14px;"><?= htmlspecialchars($hasil_clustering['final_cluster']) ?></pre>
      </div>
      <p style="margin-top: 15px; color: #666;">Struktur cluster menunjukkan hierarki penggabungan dari yang paling mirip hingga membentuk satu kelompok utama.</p>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </main>

  <footer>&copy; <?php echo date('Y'); ?> | Analisis Clustering Hierarchical 💻</footer>
</body>
</html>