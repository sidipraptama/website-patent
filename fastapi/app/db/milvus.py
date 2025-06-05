from pymilvus import connections, Collection, FieldSchema, CollectionSchema, DataType, utility
import logging
import numpy as np
import os

MILVUS_HOST = os.getenv("MILVUS_HOST", "localhost")
MILVUS_PORT = os.getenv("MILVUS_PORT", "19530")
MILVUS_COLLECTION = os.getenv("MILVUS_COLLECTION", "default")
# MILVUS_COLLECTION_64 = "patent_vectors_64"

logging.basicConfig(level=logging.INFO)

print(MILVUS_HOST, MILVUS_PORT)

milvus_available = False

def connect_milvus():
    global milvus_available
    try:
        if not connections.has_connection("default"):
            connections.connect(alias="default", host=MILVUS_HOST, port=MILVUS_PORT)
        # Test connection
        _ = utility.list_collections(using="default")
        milvus_available = True
        logging.info("Milvus connected successfully.")
        logging.info(f"Existing collections: {utility.list_collections(using='default')}")
    except Exception as e:
        logging.warning(f"Milvus not available: {e}")
        milvus_available = False

# Hapus koleksi kalau sudah ada
def reset_collection():
    if utility.has_collection(MILVUS_COLLECTION):
        utility.drop_collection(MILVUS_COLLECTION)
        print(f"Koleksi '{MILVUS_COLLECTION}' dihapus.")

def reset_collection_all(collection_name):
    """Menghapus koleksi dan isinya di Milvus."""
    try:
        # Cek apakah koleksi ada
        if utility.has_collection(collection_name):
            # Menghapus koleksi
            utility.drop_collection(collection_name)
            print(f"Koleksi '{collection_name}' dihapus.")
        else:
            print(f"Koleksi '{collection_name}' tidak ditemukan.")
    except Exception as e:
        print(f"❌ Gagal menghapus koleksi '{collection_name}': {str(e)}")

# Buat koleksi (kalau belum ada)
def create_collection():
    if utility.has_collection(MILVUS_COLLECTION):
        print(f"Koleksi '{MILVUS_COLLECTION}' sudah ada.")
        return

    fields = [
        FieldSchema(name="id", dtype=DataType.VARCHAR, max_length=20, is_primary=True),  # Ubah ke VARCHAR
        FieldSchema(name="embeddings", dtype=DataType.FLOAT_VECTOR, dim=1024)  # Sesuai dimensi embedding
    ]
    schema = CollectionSchema(fields, description="Patent Embeddings Collection")
    collection = Collection(name=MILVUS_COLLECTION, schema=schema)
    print(f"Koleksi '{MILVUS_COLLECTION}' berhasil dibuat!")

def create_collection_sberta():
    if utility.has_collection("patent_vectors_sberta"):
        print(f"Koleksi patent_vectors_sberta sudah ada.")
        return

    fields = [
        FieldSchema(name="id", dtype=DataType.VARCHAR, max_length=20, is_primary=True),  # Ubah ke VARCHAR
        FieldSchema(name="embeddings", dtype=DataType.FLOAT_VECTOR, dim=768)  # Sesuai dimensi embedding
    ]
    schema = CollectionSchema(fields, description="Patent Embeddings Collection")
    collection = Collection(name="patent_vectors_sberta", schema=schema)
    print(f"Koleksi patent_vectors_sberta berhasil dibuat!")

def create_collection_tfidf():
    if utility.has_collection("patent_vectors_tfidf"):
        print(f"Koleksi patent_vectors_tfidf sudah ada.")
        return

    fields = [
        FieldSchema(name="id", dtype=DataType.VARCHAR, max_length=20, is_primary=True),
        FieldSchema(name="embeddings", dtype=DataType.FLOAT_VECTOR, dim=300)
    ]
    schema = CollectionSchema(fields, description="Patent Embeddings Collection")
    collection = Collection(name="patent_vectors_tfidf", schema=schema)
    print(f"Koleksi patent_vectors_tfidf berhasil dibuat!")

def check_index():
    collection = get_collection()
    indexes = collection.indexes
    if indexes:
        print(f"Index ditemukan di collection patent_vectors: {indexes}")
    else:
        print(f"Tidak ada index di collection patent_vectors")

def check_index_sberta():
    collection = get_collection_sberta()
    indexes = collection.indexes
    if indexes:
        print(f"Index ditemukan di collection patent_vectors_sberta: {indexes}")
    else:
        print(f"Tidak ada index di collection patent_vectors_sberta")

def check_index_tfidf():
    collection = get_collection_tfidf()
    indexes = collection.indexes
    if indexes:
        print(f"Index ditemukan di collection patent_vectors_tfidf: {indexes}")
    else:
        print(f"Tidak ada index di collection patent_vectors_tfidf")

def create_index():
    collection = get_collection()

    if not collection:
        print("❌ Koleksi 'patent_vectors' tidak ditemukan, tidak bisa membuat index.")
        return

    try:
        # Cek apakah index sudah ada
        if collection.has_index():
            print("✅ Index sudah ditemukan untuk koleksi 'patent_vectors'.")
            return

        # Konfigurasi index HNSW
        index_params = {
            "metric_type": "COSINE",    # Gunakan Cosine Similarity
            "index_type": "HNSW",
            "params": {
                "M": 32,                # Maksimum koneksi antar node
                "efConstruction": 200   # Jumlah kandidat saat membangun index
            }
        }

        print("⚙️ Membuat index HNSW untuk koleksi 'patent_vectors'...")
        collection.create_index(field_name="embeddings", index_params=index_params)
        print("✅ Index HNSW berhasil dibuat untuk koleksi 'patent_vectors'.")

        # Tampilkan informasi index setelah dibuat
        index_info = collection.index()
        print(f"🔍 Detail Index:\n{index_info}")

        # Load collection ke memori
        collection.load()
        print("✅ Koleksi 'patent_vectors' berhasil dimuat ke memori.")

    except Exception as e:
        print(f"❌ Terjadi error saat membuat atau memuat index: {e}")

def create_index_sberta():
    collection = get_collection_sberta()

    if not collection:
        print("❌ Koleksi 'patent_vectors_sberta' tidak ditemukan, tidak bisa membuat index.")
        return

    try:
        # Cek apakah index sudah ada
        if collection.has_index():
            print("✅ Index sudah ditemukan untuk koleksi 'patent_vectors_sberta'.")
            return

        # Konfigurasi index HNSW
        index_params = {
            "metric_type": "COSINE",    # Gunakan Cosine Similarity
            "index_type": "HNSW",
            "params": {
                "M": 16,                # Maksimum koneksi antar node
                "efConstruction": 40   # Jumlah kandidat saat membangun index
            }
        }

        print("⚙️ Membuat index HNSW untuk koleksi 'patent_vectors_sberta'...")
        collection.create_index(field_name="embeddings", index_params=index_params)
        print("✅ Index HNSW berhasil dibuat untuk koleksi 'patent_vectors_sberta'.")

        # Tampilkan informasi index setelah dibuat
        index_info = collection.index()
        print(f"🔍 Detail Index:\n{index_info}")

        # Load collection ke memori
        collection.load()
        print("✅ Koleksi 'patent_vectors_sberta' berhasil dimuat ke memori.")

    except Exception as e:
        print(f"❌ Terjadi error saat membuat atau memuat index: {e}")

def create_index_tfidf():
    collection = get_collection_tfidf()

    if not collection:
        print("❌ Koleksi 'patent_vectors_tfidf' tidak ditemukan, tidak bisa membuat index.")
        return

    try:
        # Cek apakah index sudah ada
        if collection.has_index():
            print("✅ Index sudah ditemukan untuk koleksi 'patent_vectors_tfidf'.")
            return

        # Konfigurasi index HNSW
        index_params = {
            "metric_type": "COSINE",    # Gunakan Cosine Similarity
            "index_type": "HNSW",
            "params": {
                "M": 32,                # Maksimum koneksi antar node
                "efConstruction": 200   # Jumlah kandidat saat membangun index
            }
        }

        print("⚙️ Membuat index HNSW untuk koleksi 'patent_vectors_tfidf'...")
        collection.create_index(field_name="embeddings", index_params=index_params)
        print("✅ Index HNSW berhasil dibuat untuk koleksi 'patent_vectors_tfidf'.")

        # Tampilkan informasi index setelah dibuat
        index_info = collection.index()
        print(f"🔍 Detail Index:\n{index_info}")

        # Load collection ke memori
        collection.load()
        print("✅ Koleksi 'patent_vectors_tfidf' berhasil dimuat ke memori.")

    except Exception as e:
        print(f"❌ Terjadi error saat membuat atau memuat index: {e}")

# Load koleksi
def get_collection():
    print(f"Nama koleksi: {MILVUS_COLLECTION}")
    collection = Collection(MILVUS_COLLECTION, using="default")
    # collection.load()
    return collection

def get_collection_sberta():
    print(f"Nama koleksi: patent_vectors_sberta")
    collection = Collection('patent_vectors_sberta', using="default")
    collection.load()
    return collection

def get_collection_tfidf():
    print(f"Nama koleksi: patent_vectors_tfidf")
    collection = Collection('patent_vectors_tfidf', using="default")
    collection.load()
    return collection

# Search similarity
def search_vectors(embedding, limit=10):
    collection = get_collection()

    # IVF
    # search_params = {"metric_type": "COSINE", "params": {"nprobe": 10}}

    # HNSW
    search_params = {
        "metric_type": "COSINE",
        "params": {
            "ef": 20000 # efConstruction: Jumlah kandidat yang dipertimbangkan saat membangun index.
        }
    }

    results = collection.search(
        data=[embedding],
        anns_field="embeddings",
        param=search_params,
        limit=limit,
        output_fields=["id"]
    )
    return results

def search_vectors_sberta(embedding, limit=10):
    collection = get_collection_sberta()

    # IVF
    # search_params = {"metric_type": "COSINE", "params": {"nprobe": 10}}

    # HNSW
    search_params = {
        "metric_type": "COSINE",
        "params": {
            "ef": 500
        }
    }

    results = collection.search(
        data=[embedding],
        anns_field="embeddings",
        param=search_params,
        limit=limit,
        output_fields=["id"]
    )
    return results

def search_vectors_tfidf(embedding, limit=10):
    collection = get_collection_tfidf()

    # IVF
    # search_params = {"metric_type": "COSINE", "params": {"nprobe": 10}}

    # HNSW
    search_params = {
        "metric_type": "COSINE",
        "params": {
            "ef": 20000 # efConstruction: Jumlah kandidat yang dipertimbangkan saat membangun index.
        }
    }

    results = collection.search(
        data=[embedding],
        anns_field="embeddings",
        param=search_params,
        limit=limit,
        output_fields=["id"]
    )
    return results

# Insert data
def insert_vectors(embeddings, abstracts):
    collection = get_collection()
    data = [
        embeddings.tolist(),
        abstracts.tolist()
    ]
    collection.insert(data)
    collection.flush()
    print("Data inserted successfully!")

# Batch insert data
def insert_vectors_batch(collection_name, all_ids, all_embeddings, batch_size=1000):
    """Insert data ke Milvus dalam batch."""
    try:
        # Pastikan koleksi ada sebelum insert
        if not utility.has_collection(collection_name):
            print(f"❌ Koleksi '{collection_name}' tidak ditemukan.")
            return

        collection = Collection(collection_name)
        total_data = len(all_ids)

        print(f"🔍 Total data untuk di-insert: {total_data}")

        # Bagi data ke dalam batch
        for i in range(0, total_data, batch_size):
            batch_ids = all_ids[i:i + batch_size]
            batch_embeddings = all_embeddings[i:i + batch_size]

            print(f"📦 Meng-insert batch {i // batch_size + 1} dengan {len(batch_ids)} data...")

            # Pastikan data dalam bentuk list
            data = [
                list(batch_ids),  # Pastikan IDs dalam bentuk list
                batch_embeddings.tolist()  # Konversi numpy array ke list
            ]

            # Cetak contoh data untuk debugging (opsional)
            # print(f"📌 Contoh data yang akan di-insert: {data[0][:5]}, {data[1][:5]}")

            # Insert ke Milvus
            collection.insert(data)

            print(f"✅ Batch {i // batch_size + 1} berhasil di-insert.")

    except Exception as e:
        print(f"❌ Gagal meng-insert batch: {str(e)}")
