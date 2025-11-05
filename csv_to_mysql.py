import pandas as pd
import sys
import mysql.connector
from mysql.connector import Error
from datetime import datetime # BARU: Impor library datetime

# --- 1. Cek dan ambil argumen nama file ---
if len(sys.argv) < 2:
    print("Error: Nama file CSV tidak diberikan sebagai argumen.")
    sys.exit(1)

file_name = sys.argv[1]
file_path = f"tweets-data/{file_name}"

print(f"\n[INFO] Memulai proses impor database untuk file: {file_name}")

try:
    # --- 2. Koneksi ke database ---
    mydb = mysql.connector.connect(
      host="localhost",
      user="root",
      password="",
      database="twitter"
    )
    mycursor = mydb.cursor()

    print(f"[INFO] Membaca file dari: {file_path}")
    # --- 3. Baca file CSV ---
    df = pd.read_csv(file_path, delimiter=",")
    print(f"[INFO] Ditemukan {len(df)} baris data untuk diimpor.")

    # --- 4. Siapkan query SQL ---
    # Asumsi Anda punya kolom 'counter' di tabel. Jika tidak, hapus dari query.
    sql = "INSERT INTO trending (waktu, isi_twit, sumber_file) VALUES (%s, %s, %s)"

    rows_to_insert = []
    for index, row in df.iterrows():
        # Ambil teks tweet dan pastikan tidak kosong
        isi_twit = row.get('full_text', None)
        if not isi_twit:
            continue # Lewati baris jika tidak ada teks

        # ======================= PERBAIKAN UTAMA DI SINI =======================
        # Ambil string tanggal dari CSV
        twitter_date_str = row.get('created_at', None)
        
        mysql_date_str = None
        if twitter_date_str:
            try:
                # 1. Ubah string format Twitter menjadi objek datetime Python
                #    Format: 'Day Mon Day HH:MM:SS +0000 YYYY'
                dt_object = datetime.strptime(twitter_date_str, '%a %b %d %H:%M:%S +0000 %Y')
                
                # 2. Ubah objek datetime menjadi string format yang dimengerti MySQL
                mysql_date_str = dt_object.strftime('%Y-%m-%d %H:%M:%S')
            except (ValueError, TypeError):
                # Jika ada error konversi, biarkan tanggalnya kosong (NULL)
                print(f"[WARN] Format tanggal tidak valid ditemukan: {twitter_date_str}")
                mysql_date_str = None
        # =======================================================================

        # Tambahkan data yang sudah bersih ke daftar
        rows_to_insert.append((mysql_date_str, isi_twit, file_name))

    # --- 6. Eksekusi query dengan 'executemany' untuk performa cepat ---
    if rows_to_insert:
        mycursor.executemany(sql, rows_to_insert)
        mydb.commit() # Commit HANYA SEKALI setelah semua data siap
        print(f"\n[SUKSES] {mycursor.rowcount} baris data dari '{file_name}' telah berhasil diimpor.")
    else:
        print("[WARN] Tidak ada baris data yang valid untuk diimpor dari file.")

except FileNotFoundError:
    print(f"[ERROR] File tidak ditemukan pada path '{file_path}'.")
except Error as e:
    print(f"[ERROR] Terjadi kesalahan pada database MySQL: {e}")
except Exception as e:
    print(f"[ERROR] Terjadi kesalahan tak terduga: {e}")
finally:
    if 'mydb' in locals() and mydb.is_connected():
        mycursor.close()
        mydb.close()
        print("[INFO] Koneksi database ditutup.")