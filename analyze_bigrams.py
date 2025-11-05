import mysql.connector
from mysql.connector import Error
import nltk
from nltk.tokenize import word_tokenize
from nltk.util import bigrams
from nltk.probability import FreqDist
import pandas as pd

# --- Konfigurasi Database ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'twitter'
}

# --- Download resource NLTK yang dibutuhkan (hanya sekali) ---
try:
    nltk.data.find('tokenizers/punkt')
except nltk.downloader.DownloadError:
    print("[INFO] Mendownload resource NLTK 'punkt'...")
    nltk.download('punkt')
    print("[INFO] Download selesai.")

def analyze_and_store_bigrams():
    """
    Fungsi utama untuk mengambil data, menganalisis frekuensi bigram,
    dan menyimpannya ke database.
    """
    db_connection = None
    try:
        # 1. Terhubung ke database
        db_connection = mysql.connector.connect(**DB_CONFIG)
        cursor = db_connection.cursor()
        print("[INFO] Berhasil terhubung ke database.")

        # 2. Ambil semua teks yang sudah diproses dari tabel 'trending'
        query_select = "SELECT final_processed_text FROM trending WHERE final_processed_text IS NOT NULL AND final_processed_text != ''"
        cursor.execute(query_select)
        results = cursor.fetchall()

        if not results:
            print("[WARN] Tidak ada data yang sudah diproses ditemukan. Proses dihentikan.")
            return

        print(f"[INFO] Ditemukan {len(results)} baris data untuk dianalisis.")

        # 3. Gabungkan semua teks menjadi satu corpus besar dan lakukan tokenisasi
        corpus = ' '.join([row[0] for row in results])
        tokens = word_tokenize(corpus)
        print(f"[INFO] Total token (kata) yang ditemukan: {len(tokens)}")

        # 4. Generate bigram menggunakan NLTK
        generated_bigrams = list(bigrams(tokens))
        
        # 5. Hitung frekuensi setiap bigram menggunakan NLTK FreqDist
        freq_dist = FreqDist(generated_bigrams)
        print(f"[INFO] Ditemukan {len(freq_dist)} bigram unik.")

        if not freq_dist:
            print("[WARN] Tidak ada bigram yang dapat dihasilkan. Proses selesai.")
            return
            
        # 6. Kosongkan tabel bigram yang lama (TRUNCATE)
        print("[INFO] Mengosongkan tabel 'bigrams' lama...")
        cursor.execute("TRUNCATE TABLE bigrams")

        # 7. Siapkan data untuk dimasukkan ke database
        # Ubah format bigram dari ('kata1', 'kata2') menjadi "kata1 kata2"
        data_to_insert = [(' '.join(bigram), freq) for bigram, freq in freq_dist.items()]
        
        # 8. Masukkan semua data baru ke tabel 'bigrams' menggunakan executemany
        query_insert = "INSERT INTO bigrams (bigram, count) VALUES (%s, %s)"
        cursor.executemany(query_insert, data_to_insert)
        db_connection.commit() # Simpan perubahan ke database

        print(f"\n[SUKSES] {cursor.rowcount} baris data bigram telah berhasil disimpan ke database.")

    except Error as e:
        print(f"[ERROR] Terjadi kesalahan pada database: {e}")
    except Exception as e:
        print(f"[ERROR] Terjadi kesalahan tak terduga: {e}")
    finally:
        if db_connection and db_connection.is_connected():
            cursor.close()
            db_connection.close()
            print("[INFO] Koneksi database ditutup.")

# --- Jalankan fungsi utama ---
if __name__ == "__main__":
    analyze_and_store_bigrams()