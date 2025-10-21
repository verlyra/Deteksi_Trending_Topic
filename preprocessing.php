<?php
// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_time_limit(600); // Naikkan batas waktu ke 10 menit untuk data besar

// 1. SETUP DAN KONFIGURASI
require_once __DIR__ . '/vendor/autoload.php';
use Sastrawi\Stemmer\StemmerInterface;

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'twitter');
define('DATA_TABLE', 'trending');
define('ID_COLUMN', 'id');
define('TEXT_COLUMN', 'isi_twit');
define('SOURCE_COLUMN', 'sumber_file');
define('SLANG_TABLE_NAME', 'slangword');
define('SLANG_COLUMN', 'kata_tbaku');
define('FORMAL_COLUMN', 'kata_baku');


// 2. FUNGSI-FUNGSI BANTUAN
function loadSlangDictionary(mysqli $conn): array
{
    $slangDict = [];
    $sql = "SELECT " . SLANG_COLUMN . ", " . FORMAL_COLUMN . " FROM " . SLANG_TABLE_NAME;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $slang = trim($row[SLANG_COLUMN]);
            $formal = trim($row[FORMAL_COLUMN]);
            if (!empty($slang)) {
                $slangDict[$slang] = $formal;
            }
        }
    }
    return $slangDict;
}

function loadStopwords(): array
{
    $stopwords = include 'stopword.php';
    return is_array($stopwords) ? $stopwords : [];
}

function preprocessText(string $text, array $slangDict, array $stopwords, \Sastrawi\Stemmer\StemmerInterface $stemmer): array
{
    $results = [];
    $results['lowercase'] = strtolower($text);
    $results['no_punctuation'] = preg_replace('/[^a-z0-9\s]/', '', $results['lowercase']);
    $tokens = array_filter(explode(' ', $results['no_punctuation']));
    $results['tokenized'] = json_encode(array_values($tokens));
    $noSlangTokens = array_map(fn($token) => $slangDict[$token] ?? $token, $tokens);
    $results['no_slang'] = json_encode($noSlangTokens);
    $noStopwordsTokens = array_filter($noSlangTokens, fn($token) => !in_array($token, $stopwords));
    $results['no_stopwords'] = json_encode(array_values($noStopwordsTokens));
    $textToStem = implode(' ', $noStopwordsTokens);
    $stemmedText = $stemmer->stem($textToStem);
    $results['stemmed'] = json_encode(array_filter(explode(' ', $stemmedText)));
    $results['final_processed_text'] = $stemmedText;
    return $results;
}

// 3. EKSEKUSI UTAMA & TAMPILAN HTML
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses & Simpan Preprocessing Data</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        h1 { text-align: center; color: white; background-color: #f5a941; padding: 20px 0; margin: 0; }
        .container { width: 95%; max-width: 1400px; margin: 20px auto; background-color: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 25px; }
        
        /* DIUBAH: Styling untuk kontainer form */
        .form-container {
            margin-bottom: 30px;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        /* BARU: Menggunakan Flexbox untuk layout form yang rapi */
        .form-container form {
            display: flex;
            align-items: center; /* Menyejajarkan item secara vertikal */
            gap: 15px; /* Memberi jarak antar elemen */
            flex-wrap: wrap; /* Memungkinkan elemen turun baris pada layar kecil */
        }
        
        .form-container label {
            font-weight: bold;
            color: #333;
        }
        
        /* DIUBAH: Styling untuk dropdown <select> */
        .form-container select {
            flex-grow: 1; /* Membuat dropdown mengisi ruang yang tersedia */
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            background-color: #fff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        /* BARU: Efek focus pada dropdown */
        .form-container select:focus {
            outline: none;
            border-color: #f5a941;
            box-shadow: 0 0 0 4px rgba(245, 169, 65, 0.25);
        }

        /* DIUBAH: Styling untuk tombol */
        .form-container button {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            background-color: #f5a941;
            color: #333;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.2s;
        }

        /* BARU: Efek hover pada tombol */
        .form-container button:hover {
            background-color: #e49b3a; /* Warna sedikit lebih gelap */
            transform: translateY(-2px); /* Efek terangkat */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        .info-box { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .info-box.success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .info-box.error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info-box.neutral { background-color: #e2e3e5; border-color: #d6d8db; color: #383d41; }
        footer { text-align: center; padding: 20px; margin-top: 30px; color: #777; font-size: 14px; }
        
        .page-actions { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .btn-back { display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 5px; text-decoration: none; font-weight: bold; transition: background-color 0.3s; }
        .btn-back:hover { background-color: #5a6268; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th, td { border: 1px solid #ddd; text-align: left; padding: 10px; font-size: 13px; vertical-align: top; word-wrap: break-word; }
        th { background-color: #f8c146; color: #333; position: sticky; top: 0; z-index: 1; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #fff4d1; }
        th:nth-child(1), td:nth-child(1) { width: 4%; }
        th:nth-child(2), td:nth-child(2) { width: 15%; }
        th:nth-child(3), td:nth-child(3), th:nth-child(4), td:nth-child(4) { width: 10%; }
        th:nth-child(9), td:nth-child(9) { width: 12%; }
        th:nth-child(5), td:nth-child(5), th:nth-child(6), td:nth-child(6), th:nth-child(7), td:nth-child(7), th:nth-child(8), td:nth-child(8) { width: 12%; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Proses & Simpan Hasil Preprocessing</h1>
    <div class="container">
        <div class="page-actions">
            <a href="dasboard.php" class="btn-back">Kembali ke Dashboard</a>
        </div>

        <?php
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) { die("<div class='info-box error'>Koneksi Gagal: " . $conn->connect_error . "</div></div></body></html>"); }
        
        $available_sources = [];
        $sql_get_sources = "SELECT DISTINCT " . SOURCE_COLUMN . " FROM " . DATA_TABLE . " WHERE " . SOURCE_COLUMN . " IS NOT NULL AND " . SOURCE_COLUMN . " != '' ORDER BY " . SOURCE_COLUMN . " ASC";
        $result_sources = $conn->query($sql_get_sources);
        if ($result_sources && $result_sources->num_rows > 0) {
            while ($row = $result_sources->fetch_assoc()) {
                $available_sources[] = $row[SOURCE_COLUMN];
            }
        }
        
        $selected_source = null;
        if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['selected_source'])) {
            if (in_array($_POST['selected_source'], $available_sources)) {
                $selected_source = $_POST['selected_source'];
            }
        }
        ?>

        <div class="form-container">
            <form method="POST" action="">
                <label for="selected_source">Pilih Sumber Data:</label>
                <select name="selected_source" id="selected_source" required>
                    <option value="">-- Pilih Sumber Data --</option>
                    <?php
                    foreach ($available_sources as $source) {
                        $selected_attr = ($source === $selected_source) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($source) . "' $selected_attr>" . htmlspecialchars($source) . "</option>";
                    }
                    ?>
                </select>
                <button type="submit">Proses & Simpan Data</button>
            </form>
        </div>

        <?php
        if ($selected_source) {
            echo "<div class='info-box success'>Memproses dan menyimpan data dari sumber: <strong>" . htmlspecialchars($selected_source) . "</strong></div>";

            // Load resources
            $slangDict = loadSlangDictionary($conn);
            $stopwords = loadStopwords();
            $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
            $stemmer = $stemmerFactory->createStemmer();
            
            // Persiapkan statement UPDATE di luar loop
            $sql_update = "UPDATE " . DATA_TABLE . " SET 
                            `lowercase` = ?, `no_punctuation` = ?, `tokenized` = ?, 
                            `no_slang` = ?, `no_stopwords` = ?, `stemmed` = ?, 
                            `final_processed_text` = ? 
                           WHERE `id` = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            // Ambil data mentah
            $sql_process = "SELECT `" . ID_COLUMN . "`, `" . TEXT_COLUMN . "` FROM `" . DATA_TABLE . "` WHERE `" . SOURCE_COLUMN . "` = ?";
            $stmt_select = $conn->prepare($sql_process);
            $stmt_select->bind_param("s", $selected_source);
            $stmt_select->execute();
            $result_process = $stmt_select->get_result();
            
            $processed_count = 0;
            if ($result_process && $result_process->num_rows > 0) {
                echo "<p>Ditemukan " . $result_process->num_rows . " data. Memulai proses...</p>";
                echo '<div style="overflow-x:auto;"><table><thead>
                        <tr>
                            <th>ID</th><th>Teks Asli</th><th>Case Folding</th><th>Hapus Simbol</th>
                            <th>Tokenisasi</th><th>Filter Slang Word</th><th>Filter Stop Word</th>
                            <th>Stemming</th><th>Teks Final</th>
                        </tr>
                      </thead><tbody>';

                while ($row = $result_process->fetch_assoc()) {
                    $id = $row[ID_COLUMN];
                    $originalText = $row[TEXT_COLUMN] ?? '';
                    
                    $processed = preprocessText($originalText, $slangDict, $stopwords, $stemmer);

                    $stmt_update->bind_param("sssssssi", 
                        $processed['lowercase'], $processed['no_punctuation'], $processed['tokenized'], 
                        $processed['no_slang'], $processed['no_stopwords'], $processed['stemmed'], 
                        $processed['final_processed_text'], $id
                    );
                    
                    if ($stmt_update->execute()) {
                        $processed_count++;
                    }

                    // Tampilkan hasil di tabel HTML
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($id) . '</td>';
                    echo '<td>' . htmlspecialchars($originalText) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['lowercase']) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['no_punctuation']) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['tokenized']) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['no_slang']) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['no_stopwords']) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['stemmed']) . '</td>';
                    echo '<td>' . htmlspecialchars($processed['final_processed_text']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
                echo "<div class='info-box success' style='margin-top: 20px;'><strong>Proses Selesai!</strong> Sebanyak {$processed_count} baris data telah berhasil diproses dan disimpan ke database.</div>";

            } else {
                echo "<div class='info-box neutral'>Tidak ada data ditemukan untuk sumber '" . htmlspecialchars($selected_source) . "'.</div>";
            }
            $stmt_select->close();
            $stmt_update->close();
        } else {
            echo "<div class='info-box neutral'>Silakan pilih sumber data untuk memulai proses.</div>";
        }
        $conn->close();
        ?>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> Trending Topic Analyzer — by Verly
    </footer>
</body>
</html>