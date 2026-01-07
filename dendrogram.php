<?php
// Proses jika form disubmit
$hasil_clustering = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $max_iterations = isset($_POST['max_iterations']) ? intval($_POST['max_iterations']) : 15;
    
    // Panggil script Python untuk dendrogram
    $python_script = __DIR__ . '/dendrogramBE.py';
    
    // Cek apakah file Python ada
    if (!file_exists($python_script)) {
        $error_message = "File dendrogramBE.py tidak ditemukan di: " . $python_script;
    } else {
        $command = sprintf(
            'python "%s" %d 2>&1',
            $python_script,
            $max_iterations
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
  <title>Dendrogram - Visualisasi Clustering</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
    body { margin: 0; font-family: "Poppins", "Segoe UI", Arial, sans-serif; background-color: #f8f9fa; color: #333; }
    header { background: linear-gradient(90deg, #9c27b0, #ba68c8); color: white; padding: 25px; text-align: center; box-shadow: 0 3px 8px rgba(0,0,0,0.15); border-bottom: 3px solid #7b1fa2; }
    h1 { margin: 0; font-size: 28px; font-weight: 600; }
    .back-btn { display: inline-block; margin: 20px 0 0 20px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.3s; }
    .back-btn:hover { background-color: #5a6268; transform: translateY(-2px); }
    main { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
    .info-box { background: white; border-radius: 10px; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .info-box h2 { margin-top: 0; color: #9c27b0; font-size: 22px; border-bottom: 2px solid #9c27b0; padding-bottom: 10px; }
    .form-box { background: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .form-box h2 { margin-top: 0; color: #9c27b0; font-size: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #495057; }
    .form-group input[type="number"] { width: 100%; max-width: 200px; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 15px; }
    .form-group input[type="checkbox"] { width: auto; margin-right: 8px; }
    .btn-submit { background: linear-gradient(90deg, #9c27b0, #ba68c8); color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(156,39,176,0.3); }
    .result-box { background: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .result-box h2 { margin-top: 0; color: #9c27b0; font-size: 22px; border-bottom: 2px solid #9c27b0; padding-bottom: 10px; }
    .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    #dendrogram-container { width: 100%; height: 600px; overflow: auto; background: #fafafa; border: 2px solid #e0e0e0; border-radius: 8px; position: relative; }
    #dendrogram-svg { display: block; margin: 20px auto; }
    .node-label { font-family: "Poppins", sans-serif; font-size: 12px; font-weight: 500; }
    .branch-line { stroke: #9c27b0; stroke-width: 2; fill: none; }
    .node-circle { fill: #9c27b0; stroke: white; stroke-width: 2; }
    .legend { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0; margin-top: 20px; }
    .legend-item { display: inline-block; margin-right: 20px; font-size: 14px; }
    .legend-color { display: inline-block; width: 20px; height: 20px; border-radius: 3px; margin-right: 8px; vertical-align: middle; }
    footer { text-align: center; margin-top: 60px; padding: 20px; font-size: 14px; color: #777; }
  </style>
</head>
<body>
  <header>
    <h1>🌳 Dendrogram - Visualisasi Hierarchical Clustering</h1>
    <p style="margin: 10px 0 0 0; opacity: 0.9;">Representasi Visual Pohon Clustering</p>
  </header>
  
  <div style="display: flex; gap: 10px; margin: 20px 0 0 20px;">
    <a href="dasboard.php" class="back-btn">← Kembali ke Dashboard</a>
    <a href="clustering.php" class="back-btn" style="background: linear-gradient(90deg, #6f42c1, #8e6fc7);">📊 Lihat Detail Clustering</a>
  </div>
  
  <main>
    <div class="info-box">
      <h2>ℹ️ Tentang Dendrogram</h2>
      <p><strong>Dendrogram</strong> adalah diagram pohon yang menunjukkan hasil clustering hierarchical. Setiap cabang menunjukkan penggabungan dua cluster, dan tinggi cabang menunjukkan jarak (dissimilarity) antara cluster yang digabungkan.</p>
      <ul style="line-height: 1.8;">
        <li><strong>Sumbu Horizontal:</strong> Menunjukkan n-gram atau cluster yang terbentuk</li>
        <li><strong>Sumbu Vertikal:</strong> Menunjukkan jarak penggabungan (semakin tinggi = semakin berbeda)</li>
        <li><strong>Interpretasi:</strong> Cluster yang bergabung di ketinggian rendah lebih mirip, yang bergabung tinggi lebih berbeda</li>
      </ul>
    </div>

    <div class="form-box">
      <h2>⚙️ Generate Dendrogram</h2>
      <form method="POST">
        <div class="form-group">
          <label for="max_iterations">Maksimal Iterasi Clustering:</label>
          <input type="number" id="max_iterations" name="max_iterations" value="15" min="5" max="50">
          <small style="color: #6c757d; display: block; margin-top: 5px;">Jumlah maksimal iterasi penggabungan cluster</small>
        </div>
        
        <button type="submit" class="btn-submit">🚀 Generate Dendrogram</button>
      </form>
    </div>

    <?php if ($error_message): ?>
    <div class="error-box">
      <strong>⚠️ Error:</strong> <?= $error_message ?>
    </div>
    <?php endif; ?>

    <?php if ($hasil_clustering && isset($hasil_clustering['status']) && $hasil_clustering['status'] === 'success'): ?>
    
    <div class="result-box">
      <h2>🌳 Visualisasi Dendrogram</h2>
      <p>Diagram pohon clustering hierarchical dengan metode Average Linkage</p>
      <p style="color: #666; margin-bottom: 20px;">
        Total N-Grams: <strong><?= $hasil_clustering['total_ngrams'] ?></strong> | 
        Total Iterasi: <strong><?= $hasil_clustering['total_iterations'] ?></strong> | 
        Max Height: <strong><?= number_format($hasil_clustering['max_height'], 4) ?></strong>
      </p>
      
      <div id="dendrogram-container">
        <svg id="dendrogram-svg"></svg>
      </div>
      
      <div class="legend">
        <div class="legend-item">
          <span class="legend-color" style="background: #9c27b0;"></span>
          <span>Cabang Cluster</span>
        </div>
        <div class="legend-item">
          <span class="legend-color" style="background: #4caf50;"></span>
          <span>N-Gram Dasar</span>
        </div>
        <div class="legend-item">
          <span class="legend-color" style="background: #ff9800;"></span>
          <span>Cluster Gabungan</span>
        </div>
      </div>
    </div>

    <script>
    // Data dari PHP
    const dendrogramData = <?= json_encode($hasil_clustering['dendrogram_data'] ?? []) ?>;
    const targetNgrams = <?= json_encode($hasil_clustering['target_ngrams'] ?? []) ?>;
    const maxHeight = <?= $hasil_clustering['max_height'] ?? 1 ?>;
    
    // Konfigurasi visualisasi
    const config = {
      width: 1200,
      height: 600,
      margin: { top: 40, right: 40, bottom: 100, left: 60 },
      nodeRadius: 6,
      labelOffset: 15
    };
    
    // Hitung area plot
    const plotWidth = config.width - config.margin.left - config.margin.right;
    const plotHeight = config.height - config.margin.top - config.margin.bottom;
    
    // Setup SVG
    const svg = document.getElementById('dendrogram-svg');
    svg.setAttribute('width', config.width);
    svg.setAttribute('height', config.height);
    
    // Buat group utama
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('transform', `translate(${config.margin.left},${config.margin.top})`);
    svg.appendChild(g);
    
    // Fungsi helper untuk membuat elemen SVG
    function createSVGElement(type, attrs) {
      const el = document.createElementNS('http://www.w3.org/2000/svg', type);
      for (let key in attrs) {
        el.setAttribute(key, attrs[key]);
      }
      return el;
    }
    
    // Scale untuk height
    function scaleHeight(h) {
      return plotHeight - (h / maxHeight * plotHeight * 0.85);
    }
    
    // Scale untuk posisi X
    function scaleX(pos) {
      return (pos / (targetNgrams.length - 1)) * plotWidth;
    }
    
    // Draw dendrogram branches
    dendrogramData.forEach(node => {
      const leftX = scaleX(node.left_pos);
      const rightX = scaleX(node.right_pos);
      const mergeX = scaleX(node.merge_pos);
      const leftY = scaleHeight(node.left_height);
      const rightY = scaleHeight(node.right_height);
      const mergeY = scaleHeight(node.merge_height);
      
      // Vertical line from left child to merge height
      const leftVertical = createSVGElement('line', {
        x1: leftX,
        y1: leftY,
        x2: leftX,
        y2: mergeY,
        class: 'branch-line'
      });
      g.appendChild(leftVertical);
      
      // Vertical line from right child to merge height
      const rightVertical = createSVGElement('line', {
        x1: rightX,
        y1: rightY,
        x2: rightX,
        y2: mergeY,
        class: 'branch-line'
      });
      g.appendChild(rightVertical);
      
      // Horizontal line at merge height
      const horizontal = createSVGElement('line', {
        x1: leftX,
        y1: mergeY,
        x2: rightX,
        y2: mergeY,
        class: 'branch-line'
      });
      g.appendChild(horizontal);
      
      // Merge point circle
      const mergeCircle = createSVGElement('circle', {
        cx: mergeX,
        cy: mergeY,
        r: 4,
        fill: '#ff9800',
        stroke: 'white',
        'stroke-width': 2
      });
      g.appendChild(mergeCircle);
      
      // Height label
      const heightText = createSVGElement('text', {
        x: mergeX + 8,
        y: mergeY - 5,
        class: 'node-label',
        fill: '#9c27b0',
        'font-size': '10px'
      });
      heightText.textContent = node.merge_height.toFixed(3);
      g.appendChild(heightText);
    });
    
    // Draw leaf nodes and labels
    targetNgrams.forEach((ngram, i) => {
      const x = scaleX(i);
      const y = plotHeight;
      
      // Leaf circle
      const circle = createSVGElement('circle', {
        cx: x,
        cy: y,
        r: config.nodeRadius,
        fill: '#4caf50',
        stroke: 'white',
        'stroke-width': 2
      });
      g.appendChild(circle);
      
      // Label
      const text = createSVGElement('text', {
        x: x,
        y: y + config.labelOffset,
        'text-anchor': 'end',
        transform: `rotate(-45, ${x}, ${y + config.labelOffset})`,
        class: 'node-label',
        fill: '#333'
      });
      text.textContent = ngram;
      g.appendChild(text);
    });
    
    // Add axes labels
    const yAxisLabel = createSVGElement('text', {
      x: -plotHeight / 2,
      y: -40,
      transform: `rotate(-90, -${plotHeight/2}, -40)`,
      'text-anchor': 'middle',
      'font-weight': '600',
      fill: '#333'
    });
    yAxisLabel.textContent = 'Jarak (Distance)';
    g.appendChild(yAxisLabel);
    
    const xAxisLabel = createSVGElement('text', {
      x: plotWidth / 2,
      y: plotHeight + 80,
      'text-anchor': 'middle',
      'font-weight': '600',
      fill: '#333'
    });
    xAxisLabel.textContent = 'N-Grams';
    g.appendChild(xAxisLabel);
    </script>

    <?php endif; ?>
  </main>

  <footer>&copy; <?php echo date('Y'); ?> | Visualisasi Dendrogram 💻</footer>
</body>
</html>