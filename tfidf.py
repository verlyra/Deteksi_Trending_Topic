import mysql.connector
import math

db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",          
    database="searchengine"  
)
#TF
cursor = db.cursor()
cursor.execute("SELECT * FROM dokumen")

myresult = cursor.fetchall()

kata_dicari = 'kucing'
for x in myresult:
    doc_id = x[0]
    kalimat = x[2]
    frekuensi = kalimat.split().count(kata_dicari)
    jumlah_kata = len(kalimat.split())
    TF = frekuensi / jumlah_kata
    print(f"{kalimat} | {frekuensi}/{jumlah_kata} | TF: {TF:.4f}")

    sql = "UPDATE dokumen SET TF = %s WHERE DocID = %s"
    val = (TF, doc_id)
    cursor.execute(sql, val)
    db.commit()

#IDF
cursor.execute("SELECT isi_doc_clean FROM dokumen")
rows = cursor.fetchall()

docs = [row[0] for row in rows]

total_dokumen = len(docs)
dokumen_mengandung = sum(kata_dicari in doc.split() for doc in docs)
if dokumen_mengandung > 0:
    IDF = math.log10(total_dokumen / dokumen_mengandung)
else:
    IDF = 0

print(f"Total Dokumen: {total_dokumen}, Dokumen Mengandung '{kata_dicari}': {dokumen_mengandung}, IDF: {IDF:.4f}")
sql = "UPDATE dokumen SET IDF = %s"
val = (IDF,)
cursor.execute(sql, val)
db.commit()

#TFIDF
for x in myresult:
    doc_id = x[0]
    TF= x[3]
    IDF= x[4]
    TFIDF = TF * IDF
    print(f"DocID: {doc_id} | TF-IDF: {TFIDF:.4f}")
    sql = "UPDATE dokumen SET `TF-IDF` = %s WHERE DocID = %s"
    val = (TFIDF, doc_id)
    cursor.execute(sql, val)
    db.commit()
