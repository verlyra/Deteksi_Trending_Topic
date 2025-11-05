import mysql.connector
from mysql.connector import Error
import pandas as pd
import numpy as np
from collections import Counter
import spacy

# --- Konfigurasi Database ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'twitter'
}

# --- Konfigurasi Analisis ---
BOOST_VALUE = 1.5
TARGET_ENTITIES = {'PERSON', 'ORG', 'GPE'}
# Daftar manual untuk memastikan kata kunci penting selalu di-boost
MANUAL_BOOST_KEYWORDS = ['jokowi', 'prabowo', 'ganjar', 'anies', 'kpk', 'polri', 'natuna', 'jakarta']

def setup_database(connection):
    """Memastikan skema DB memiliki kolom TS dan sumber_file."""
    cursor = connection.cursor()
    try:
        # Memastikan nama kolom counter adalah 'TS'
        print("[SETUP] Memastikan nama kolom adalah 'TS'...")
        cursor.execute("ALTER TABLE trending_bigrams CHANGE COLUMN time_slot_counter TS INT NOT NULL")
        print(" -> Kolom berhasil diubah/dikonfirmasi sebagai 'TS'.")
    except Error as e:
        if "Unknown column 'time_slot_counter'" in str(e) or "Duplicate column name" in str(e):
            print(" -> Nama kolom 'TS' sudah sesuai. OK.")
        else:
            print(f"[ERROR] Gagal mengubah skema kolom TS: {e}")
            raise e
            
    try:
        # Memastikan kolom sumber_file ada
        print("[SETUP] Memastikan kolom 'sumber_file' ada...")
        cursor.execute("ALTER TABLE trending_bigrams ADD COLUMN sumber_file VARCHAR(255) NULL AFTER DFIDF")
        print(" -> Kolom 'sumber_file' berhasil ditambahkan.")
    except Error as e:
        if "Duplicate column name" in str(e):
            print(" -> Kolom 'sumber_file' sudah ada. OK.")
        else:
            print(f"[ERROR] Gagal menambahkan kolom sumber_file: {e}")
            raise e

    connection.commit()
    cursor.close()

def calculate_dfidft():
    db_connection = None
    try:
        # 1. Hubungkan ke DB
        print("[INFO] Menghubungkan ke database...")
        db_connection = mysql.connector.connect(**DB_CONFIG)

        setup_database(db_connection)

        # 2. Ambil data dari tabel TRENDING (DENGAN KOLOM SUMBER_FILE)
        query = "SELECT counter, final_processed_text, sumber_file FROM trending WHERE final_processed_text IS NOT NULL AND final_processed_text != '' ORDER BY counter ASC"
        df = pd.read_sql(query, db_connection)
        df.rename(columns={'counter': 'TS'}, inplace=True)

        if df.empty:
            print("[WARN] Tidak ada data yang sudah diproses di tabel 'trending'. Proses dihentikan.")
            return

        print(f"[INFO] Ditemukan {len(df)} dokumen dari 'trending' untuk dianalisis.")

        # 3. Ekstraksi Entitas (Otomatis) dengan SpaCy
        print("[INFO] Memuat model spaCy dan mengekstrak entitas...")
        try:
            nlp = spacy.load("xx_ent_wiki_sm")
        except OSError:
            print("[WARN] Model 'xx_ent_wiki_sm' tidak ditemukan. Menggunakan 'en_core_web_sm'.")
            nlp = spacy.load("en_core_web_sm")

        # Mengambil semua teks hanya untuk ekstraksi entitas, tidak mempengaruhi proses utama
        all_text_for_entities = ' '.join(df['final_processed_text'].dropna())
        doc = nlp(all_text_for_entities)
        boosted_keywords = {ent.text.lower() for ent in doc.ents if ent.label_ in TARGET_ENTITIES}
        print(f"[INFO] Ditemukan {len(boosted_keywords)} kata kunci entitas otomatis.")

        # 4. Gabungkan dengan daftar manual
        manual_set = {keyword.lower() for keyword in MANUAL_BOOST_KEYWORDS}
        original_auto_count = len(boosted_keywords)
        boosted_keywords.update(manual_set)
        print(f"[INFO] {len(boosted_keywords) - original_auto_count} kata kunci manual ditambahkan.")
        print(f"[INFO] Total kata kunci yang di-boost: {len(boosted_keywords)}.")

        all_results = []
        previous_slot_freq = Counter()

        # 5. Iterasi melalui setiap KELOMPOK (TS dan sumber_file)
        print("\n[INFO] Mengelompokkan data berdasarkan TS & Sumber File...")
        for (ts_val, source_file), group in df.groupby(['TS', 'sumber_file']):
            if ts_val == 0:
                continue

            print(f"\n[PROSES] Menganalisis TS: {ts_val} dari file: '{source_file}' ({len(group)} dokumen)")
            
            # Menghitung Document Frequency (dfi)
            current_slot_freq = Counter()
            for text in group['final_processed_text']:
                if not text.strip(): continue
                words = text.split()
                if len(words) < 2: continue
                bigrams_in_tweet = (' '.join(b) for b in zip(words, words[1:]))
                unique_bigrams = set(bigrams_in_tweet)
                current_slot_freq.update(unique_bigrams)

            if not current_slot_freq:
                print(" -> Tidak ada bigram yang valid di slot ini.")
                previous_slot_freq = Counter()
                continue
            
            # 6. Hitung skor DF-IDFt
            for bigram, df_i in current_slot_freq.items():
                df_i_minus_1 = previous_slot_freq.get(bigram, 0)
                
                numerator = df_i + df_i_minus_1
                log_inner_value = (df_i / ts_val) + 1
                denominator = np.log10(log_inner_value) + 1
                
                boost_factor = 1.0
                try:
                    word1, word2 = bigram.split()
                    if word1 in boosted_keywords or word2 in boosted_keywords:
                        boost_factor = BOOST_VALUE
                except ValueError: pass
                
                score = (numerator / denominator) * boost_factor
                
                # Tambahkan source_file ke hasil yang akan disimpan
                # Baris kode baru (solusi)
                # Ubah ts_val dan df_i menjadi tipe int standar Python
                ts_val_int = int(ts_val)
                df_i_int = int(df_i)

                all_results.append((ts_val_int, bigram, df_i_int, boost_factor, score, source_file))
                    
            print(f" -> Ditemukan {len(current_slot_freq)} bigram unik. dfi tertinggi: {current_slot_freq.most_common(1)}")
            previous_slot_freq = current_slot_freq

        # 7. Simpan hasil ke database
        if all_results:
            cursor = db_connection.cursor()
            print("\n[INFO] Mengosongkan tabel 'trending_bigrams' lama...")
            cursor.execute("TRUNCATE TABLE trending_bigrams")
            
            print(f"[INFO] Menyimpan {len(all_results)} hasil analisis ke database...")
            # Update query INSERT untuk menyertakan kolom sumber_file
            query_insert = "INSERT INTO trending_bigrams (TS, bigram, DF, boost, DFIDF, sumber_file) VALUES (%s, %s, %s, %s, %s, %s)"
            cursor.executemany(query_insert, all_results)
            db_connection.commit()
            
            print(f"\n[SUKSES] {cursor.rowcount} baris data analisis trending telah berhasil disimpan.")
            cursor.close()

    except Error as e:
        print(f"[ERROR] Terjadi kesalahan pada database: {e}")
    except ImportError:
        print("[ERROR] Pustaka 'spacy' tidak ditemukan. Silakan install dengan 'pip install spacy'.")
    except Exception as e:
        print(f"[ERROR] Terjadi kesalahan tidak terduga: {e}")
    finally:
        if db_connection and db_connection.is_connected():
            db_connection.close()
            print("[INFO] Koneksi database ditutup.")

if __name__ == "__main__":
    calculate_dfidft()