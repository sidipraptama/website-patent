from app.db.elastic import create_index, bulk_insert_patents, delete_entire_index
from elasticsearch import Elasticsearch
import pandas as pd

# Path ke file TSV
DATA_FILE = "./data/g_patent_2010_2014.tsv"

# Buat koneksi ke Elasticsearch
es = Elasticsearch("http://localhost:9200")

# Baca file TSV
def read_tsv(file_path):
    try:
        df = pd.read_csv(file_path, sep='\t')
        return df.to_dict(orient='records')
    except Exception as e:
        print(f"❌ Error reading TSV file: {e}")
        return []

# Main function untuk insert data
if __name__ == "__main__":
    # delete_entire_index()
    # Buat index kalau belum ada
    # create_index()

    # Baca data dari TSV
    data = read_tsv(DATA_FILE)

    # Tampilkan semua index yang ada di Elasticsearch
    # indices = es.indices.get_alias("*")
    # print("📦 Daftar Index di Elasticsearch:")
    # for index_name in indices:
    #    print(f" - {index_name}")

    if data:
        # ✅ Perbaikan: Tambahkan `es` sebagai argumen pertama
        bulk_insert_patents(es, data)
        print("✅ Data inserted successfully!")
    else:
        print("⚠️ No data to insert.")
