import pandas as pd
import sys
import mysql.connector

mydb = mysql.connector.connect(
  host="localhost",
  user="root",
  password="",
  database="twitter"
)

mycursor = mydb.cursor()

# Specify the path to your CSV file
file_path = f"tweets-data/"+sys.argv[1]  # e.g., "gempa.csv"

# Read the CSV file into a pandas DataFrame
df = pd.read_csv(file_path, delimiter=",")

# Insert data from the DataFrame into MySQL table
for index, row in df.iterrows():
    Vwaktu    = row['created_at']
    Visi_twit = row['full_text']
    sql = "INSERT INTO trending (waktu, isi_twit) VALUES (%s, %s)"
    val = (Vwaktu, Visi_twit)
    mycursor.execute(sql, val)
    mydb.commit()
