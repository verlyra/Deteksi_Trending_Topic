import mysql.connector
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",          
    database="searchengine"  
)

cursor = db.cursor()
cursor1 = db.cursor()

factory = StemmerFactory()
stemmer = factory.create_stemmer()

factory1 = StopWordRemoverFactory()
stopword = factory1.create_stop_word_remover()


cursor.execute("SELECT * FROM dokumen")

myresult = cursor.fetchall()

for x in myresult:
    doc_ID = x[0]
    hasil = stemmer.stem(x[1])
    hasil = stopword.remove(hasil)

    print(hasil)

    sql = "UPDATE dokumen SET isi_doc_clean = %s WHERE DocID = %s"
    val = (hasil, doc_ID)
    cursor1.execute(sql, val)
    db.commit()

