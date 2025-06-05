import os
import numpy as np
from concurrent.futures import ThreadPoolExecutor, as_completed
from app.db.milvus import (
    connect_milvus,
    insert_vectors_batch,
    check_index_sberta
)
from pymilvus import utility

# Konfigurasi
NPZ_FOLDER = "./data/SBERTa_2021-2023"
CHECKPOINT_FILE = "./checkpoint_SBERTa.txt"
COLLECTION_NAME = "patent_vectors_sberta"
FILES_PER_BATCH = 10
BATCH_SIZE = 1000
EMBEDDING_DIM = 768

def prepare_milvus():
    """Menyiapkan koneksi dan koleksi di Milvus."""
    try:
        connect_milvus()
        check_index_sberta()
        print("✅ Milvus siap digunakan.")
    except Exception as e:
        print(f"❌ Gagal menyiapkan Milvus: {e}")
        exit(1)

def read_checkpoint():
    """Membaca checkpoint terakhir."""
    if os.path.exists(CHECKPOINT_FILE):
        with open(CHECKPOINT_FILE, "r") as f:
            return f.read().strip()
    return None

def write_checkpoint(filename):
    """Menyimpan checkpoint ke file."""
    with open(CHECKPOINT_FILE, "w") as f:
        f.write(filename)

def process_npz_file(filepath):
    """Membaca dan memproses file NPZ."""
    try:
        data = np.load(filepath, mmap_mode="r")
        if "ids" not in data or "embeddings" not in data:
            print(f"⚠️ Format salah di {filepath}, dilewati.")
            return None, None, filepath

        patent_ids = data["ids"]
        embeddings = data["embeddings"]

        # Tangani embedding 1D (768,) menjadi (1, 768)
        if embeddings.ndim == 1:
            if embeddings.shape[0] != EMBEDDING_DIM:
                print(f"⚠️ Dimensi salah di {filepath} (expected {EMBEDDING_DIM}), dilewati.")
                return None, None, filepath
            embeddings = embeddings.reshape(1, -1)
            patent_ids = np.array([patent_ids.item() if patent_ids.shape == () else str(patent_ids[0])])

        # Tangani array normal (n, 768)
        elif embeddings.shape[1] != EMBEDDING_DIM:
            print(f"⚠️ Dimensi salah di {filepath}, dilewati.")
            return None, None, filepath
        else:
            patent_ids = patent_ids.astype(str)

        print(f"📂 Berhasil memuat {filepath} ({len(patent_ids)} data)")
        return patent_ids, embeddings, filepath
    except Exception as e:
        print(f"❌ Gagal memproses {filepath}: {e}")
        return None, None, filepath
    
def normalize_embeddings(embeddings):
    """Normalisasi vektor embeddings."""
    norms = np.linalg.norm(embeddings, axis=1, keepdims=True)
    return embeddings / norms

def convert_to_float32(embeddings):
    """Konversi embeddings ke float32 jika perlu."""
    return embeddings.astype(np.float32) if embeddings.dtype != np.float32 else embeddings

def insert_batch(all_ids, all_embeddings, file_path, total_inserted):
    """Memasukkan batch data ke Milvus."""
    try:
        if not all_ids or not all_embeddings:
            return None, total_inserted

        all_embeddings = np.vstack(all_embeddings)
        # all_embeddings = normalize_embeddings(all_embeddings)
        all_embeddings = convert_to_float32(all_embeddings)

        if len(all_ids) != all_embeddings.shape[0]:
            raise ValueError(f"❌ Misalignment: {len(all_ids)} vs {all_embeddings.shape[0]}")

        insert_vectors_batch(COLLECTION_NAME, all_ids, all_embeddings, BATCH_SIZE)
        print(f"✅ {len(all_ids)} data di-insert dari file terakhir '{file_path}'")

        total_inserted += len(all_ids)
        return file_path, total_inserted
    except Exception as e:
        print(f"❌ Gagal insert ke Milvus pada file terakhir '{file_path}': {e}")
        return None, total_inserted

def main():
    prepare_milvus()

    files = sorted(os.listdir(NPZ_FOLDER), key=lambda x: int(x.split('_')[-1].split('.')[0]))
    last_processed = read_checkpoint()

    if last_processed and last_processed in files:
        files = files[files.index(last_processed) + 1:]

    all_ids, all_embeddings = [], []
    total_inserted = 0
    failed_files = []

    for f in files:
        file_path = os.path.join(NPZ_FOLDER, f)
        patent_ids, embeddings, _ = process_npz_file(file_path)

        if patent_ids is None or embeddings is None:
            failed_files.append(file_path)
            continue

        if len(patent_ids) != embeddings.shape[0]:
            print(f"⚠️ Jumlah ID vs embeddings tidak cocok di {file_path} ({len(patent_ids)} vs {embeddings.shape[0]}), dilewati.")
            failed_files.append(file_path)
            continue

        all_ids.extend(patent_ids)
        all_embeddings.append(embeddings)

        if len(all_ids) >= BATCH_SIZE or len(all_embeddings) >= FILES_PER_BATCH:
            success_file, total_inserted = insert_batch(all_ids, all_embeddings, f, total_inserted)
            if success_file:
                write_checkpoint(success_file)
                all_ids, all_embeddings = [], []

    if all_ids:
        success_file, total_inserted = insert_batch(all_ids, all_embeddings, file_path, total_inserted)
        if success_file:
            write_checkpoint(f)
            all_ids, all_embeddings = [], []

    if failed_files:
        print("⚠️ File yang gagal diproses:")
        for f in failed_files:
            print(f" - {f}")

if __name__ == "__main__":
    main()
