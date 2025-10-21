import pandas as pd
import sys
import mysql.connector
from mysql.connector import Error

# --- 1. Cek dan ambil argumen nama file ---
if len(sys.argv) < 2:
    print("Error: Nama file CSV tidak diberikan sebagai argumen.")
    sys.exit(1) # Keluar jika tidak ada argumen

file_name = sys.argv[1]  # Contoh: "gempa.csv"
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

    # --- 4. Siapkan query SQL dengan kolom 'sumber_file' ---
    sql = "INSERT INTO trending (waktu, isi_twit, sumber_file) VALUES (%s, %s, %s)"

    # --- 5. Siapkan data untuk di-insert ---
    rows_to_insert = []
    for index, row in df.iterrows():
        # Menggunakan .get() untuk menghindari error jika kolom tidak ada
        waktu = row.get('created_at', None)
        isi_twit = row.get('full_text', None)
        
        # Hanya impor jika ada teks twit
        if isi_twit:
            # Tambahkan nama file ke dalam data yang akan di-insert
            rows_to_insert.append((waktu, isi_twit, file_name))

    # --- 6. Eksekusi query dengan 'executemany' untuk performa cepat ---
    if rows_to_insert:
        mycursor.executemany(sql, rows_to_insert)
        mydb.commit() # Commit HANYA SEKALI setelah semua data siap
        print(f"\n[SUKSES] {mycursor.rowcount} baris data dari '{file_name}' telah berhasil diimpor ke database.")
    else:
        print("[WARN] Tidak ada baris data yang valid untuk diimpor dari file.")

except FileNotFoundError:
    print(f"[ERROR] File tidak ditemukan pada path '{file_path}'. Pastikan file ada di dalam folder 'tweets-data'.")
except Error as e:
    print(f"[ERROR] Terjadi kesalahan pada database MySQL: {e}")
except Exception as e:
    print(f"[ERROR] Terjadi kesalahan tak terduga: {e}")
finally:
    # --- 7. Selalu tutup koneksi ---
    if 'mydb' in locals() and mydb.is_connected():
        mycursor.close()
        mydb.close()
        print("[INFO] Koneksi database ditutup.")