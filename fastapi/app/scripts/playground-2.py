from elasticsearch import Elasticsearch, helpers

# Koneksi ke Elasticsearch
es = Elasticsearch("http://localhost:9200")

# Indeks yang digunakan
index_name = "patents"

def remove_duplicates(es, index_name):
    """Menghapus dokumen duplikat berdasarkan `patent_id`, hanya menyimpan satu dokumen unik."""
    
    # Langkah 1: Ambil semua dokumen
    query = {"query": {"match_all": {}}}
    all_docs = helpers.scan(es, index=index_name, query=query, _source_includes=["patent_id"])

    # Langkah 2: Identifikasi duplikasi
    patent_map = {}  # Menyimpan {patent_id: _id}
    duplicate_ids = []  # Menyimpan _id yang harus dihapus

    for doc in all_docs:
        patent_id = doc["_source"].get("patent_id")
        doc_id = doc["_id"]

        if patent_id in patent_map:
            duplicate_ids.append(doc_id)  # Tandai untuk dihapus
        else:
            patent_map[patent_id] = doc_id  # Simpan yang pertama ditemukan

    # Langkah 3: Hapus dokumen duplikat
    if duplicate_ids:
        delete_actions = [{"_op_type": "delete", "_index": index_name, "_id": doc_id} for doc_id in duplicate_ids]
        helpers.bulk(es, delete_actions)
        print(f"✅ {len(duplicate_ids)} duplicate documents deleted.")
    else:
        print("✅ No duplicates found.")

# Jalankan fungsi
remove_duplicates(es, index_name)