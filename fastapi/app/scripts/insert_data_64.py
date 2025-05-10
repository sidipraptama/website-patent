import os
import numpy as np
from concurrent.futures import ThreadPoolExecutor, as_completed
from app.db.milvus import (
    connect_milvus,
    create_collection_64,
    insert_vectors_batch,
    reset_collection_64,
    create_index_64
)
from pymilvus import utility

# Konfigurasi
NPZ_FOLDER = "./data/embeddings_2023_64/"
CHECKPOINT_FILE = "./checkpoint_64.txt"
COLLECTION_NAME = "patent_vectors_64"
FILES_PER_BATCH = 10
BATCH_SIZE = 1000
EMBEDDING_DIM = 64
COMPACT_THRESHOLD = 50000  # Compact setiap 50rb data

def compact_collection(collection_name):
    try:
        utility.compact(collection_name)
        print(f"🧐 Storage Milvus untuk koleksi '{collection_name}' telah dikompresi.")
    except Exception as e:
        print(f"❌ Gagal mengompresi koleksi '{collection_name}': {str(e)}")

def prepare_milvus():
    try:
        connect_milvus()
        # reset_collection_64()
        # create_collection_64()
        # create_index_64()
        print("✅ Milvus siap digunakan.")
    except Exception as e:
        print(f"❌ Gagal menyiapkan Milvus: {e}")
        exit(1)

def read_checkpoint():
    return open(CHECKPOINT_FILE).read().strip() if os.path.exists(CHECKPOINT_FILE) else None

def write_checkpoint(filename):
    with open(CHECKPOINT_FILE, "w") as f:
        f.write(filename)

def process_npz_file(filepath):
    try:
        data = np.load(filepath, mmap_mode="r")
        if "ids" not in data or "embeddings" not in data:
            print(f"⚠️ Format salah di {filepath}, dilewati.")
            return None, None

        patent_ids = data["ids"].astype(str)
        embeddings = data["embeddings"]

        if embeddings.shape[1] != EMBEDDING_DIM:
            print(f"⚠️ Dimensi salah di {filepath}, dilewati.")
            return None, None

        print(f"📂 Berhasil memuat {filepath} ({len(patent_ids)} data)")
        return patent_ids, embeddings
    except Exception as e:
        print(f"❌ Gagal memproses {filepath}: {e}")
        return None, None

def normalize_embeddings(embeddings):
    norms = np.linalg.norm(embeddings, axis=1, keepdims=True)
    return embeddings / norms

def convert_to_float32(embeddings):
    if embeddings.dtype != np.float32:
        return embeddings.astype(np.float32)
    return embeddings

def insert_batch(all_ids, all_embeddings, last_success_file, total_inserted):
    try:
        if not all_ids or not all_embeddings:
            return last_success_file, total_inserted

        all_embeddings = np.vstack(all_embeddings)
        all_embeddings = normalize_embeddings(all_embeddings)
        all_embeddings = convert_to_float32(all_embeddings)

        if len(all_ids) != all_embeddings.shape[0]:
            raise ValueError(f"❌ Misalignment: {len(all_ids)} vs {all_embeddings.shape[0]}")

        insert_vectors_batch(COLLECTION_NAME, all_ids, all_embeddings, BATCH_SIZE)
        print(f"✅ {len(all_ids)} data di-insert.")

        total_inserted += len(all_ids)

        if total_inserted >= COMPACT_THRESHOLD:
            compact_collection(COLLECTION_NAME)
            total_inserted = 0

        return last_success_file, total_inserted
    except Exception as e:
        print(f"❌ Gagal insert ke Milvus: {e}")
        return None, total_inserted

def main():
    prepare_milvus()

    files = sorted(os.listdir(NPZ_FOLDER), key=lambda x: int(x.split('_')[-1].split('.')[0]))
    last_processed = read_checkpoint()
    if last_processed:
        files = files[files.index(last_processed) + 1:] if last_processed in files else files

    all_ids, all_embeddings = [], []
    last_success_file = None
    total_inserted = 0

    with ThreadPoolExecutor(max_workers=8) as executor:
        futures = {executor.submit(process_npz_file, os.path.join(NPZ_FOLDER, f)): f for f in files}
        for future in as_completed(futures):
            patent_ids, embeddings = future.result()
            if patent_ids is None or embeddings is None:
                continue

            all_ids.extend(patent_ids)
            all_embeddings.append(embeddings)

            if len(all_ids) >= BATCH_SIZE or len(all_embeddings) >= FILES_PER_BATCH:
                success_file, total_inserted = insert_batch(all_ids, all_embeddings, futures[future], total_inserted)
                if success_file:
                    last_success_file = success_file
                    write_checkpoint(last_success_file)
                    all_ids, all_embeddings = [], []

    if all_ids:
        success_file, total_inserted = insert_batch(all_ids, all_embeddings, last_success_file, total_inserted)
        if success_file:
            write_checkpoint(success_file)
        print(f"✅ Sisa {len(all_ids)} data berhasil di-insert.")

    compact_collection(COLLECTION_NAME)

if __name__ == "__main__":
    main()