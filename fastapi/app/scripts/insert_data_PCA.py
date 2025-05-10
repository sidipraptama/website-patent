import os
import numpy as np
from sklearn.decomposition import IncrementalPCA
from concurrent.futures import ThreadPoolExecutor, as_completed
from app.db.milvus import (
    connect_milvus,
    create_collection,
    insert_vectors_batch,
    reset_collection
)
from app.scripts.create_index import create_index
from pymilvus import utility
import joblib

# Konfigurasi
NPZ_FOLDER = "./embeddings/"
CHECKPOINT_FILE = "./checkpoint.txt"
COLLECTION_NAME = "patent_vectors"
FILES_PER_BATCH = 10
BATCH_SIZE = 1000
EMBEDDING_DIM = 1024
REDUCED_DIM = 128
PCA_FILE = "./pca_model.joblib"
COMPACT_THRESHOLD = 50000  # Compact setiap 50rb data

# TRAIN_PCA = True -> Train PCA dari awal
# TRAIN_PCA = False -> Gunakan PCA yang sudah disimpan
TRAIN_PCA = True  # <-- Ubah ini kalau mau skip training PCA

def compact_collection(collection_name):
    try:
        utility.compact(collection_name)
        print(f"🗜️ Storage Milvus untuk koleksi '{collection_name}' telah dikompresi.")
    except Exception as e:
        print(f"❌ Gagal mengompresi koleksi '{collection_name}': {str(e)}")

def prepare_milvus():
    try:
        connect_milvus()
        reset_collection()
        create_collection()
        create_index()
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

def load_pca(train_new=False, initial_data=None):
    if not train_new and os.path.exists(PCA_FILE):
        print("🔍 PCA model ditemukan, memuat...")
        return joblib.load(PCA_FILE)

    print("⚠️ PCA model belum ada atau dipaksa train ulang, membuat model baru...")
    pca = IncrementalPCA(n_components=REDUCED_DIM)

    if train_new and initial_data is not None:
        print(f"🔧 Training PCA dengan {len(initial_data)} data awal...")
        pca.fit(initial_data)
        save_pca(pca)
        print("✅ PCA training selesai dan disimpan.")

    return pca

def save_pca(pca):
    print("💾 Menyimpan PCA model...")
    joblib.dump(pca, PCA_FILE)

def apply_pca(pca, embeddings):
    if not hasattr(pca, 'components_'):
        print("⚠️ PCA belum terlatih, mulai training...")
        pca.fit(embeddings)
        save_pca(pca)
    return pca.transform(embeddings)

def convert_to_float32(embeddings):
    if embeddings.dtype != np.float32:
        return embeddings.astype(np.float32)
    return embeddings

def insert_batch(all_ids, all_embeddings, last_success_file, pca, total_inserted):
    try:
        if not all_ids or not all_embeddings:
            return last_success_file, total_inserted

        all_embeddings = np.vstack(all_embeddings)
        all_embeddings = apply_pca(pca, all_embeddings)
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

    # Ambil 10.000 data awal buat training PCA kalau TRAIN_PCA True
    initial_data = []
    if TRAIN_PCA:
        print("🛠️ Mengumpulkan 10.000 data awal untuk training PCA...")
        with ThreadPoolExecutor(max_workers=8) as executor:
            futures = {executor.submit(process_npz_file, os.path.join(NPZ_FOLDER, f)): f for f in files[:100]}
            for future in as_completed(futures):
                patent_ids, embeddings = future.result()
                if embeddings is not None:
                    initial_data.append(embeddings)

        if initial_data:
            initial_data = np.vstack(initial_data)
            print(f"📝 Data awal terkumpul: {initial_data.shape}")
        else:
            print("❌ Gagal mengumpulkan data awal untuk training PCA.")

    pca = load_pca(train_new=TRAIN_PCA, initial_data=initial_data if TRAIN_PCA and len(initial_data) > 0 else None)

    with ThreadPoolExecutor(max_workers=8) as executor:
        futures = {executor.submit(process_npz_file, os.path.join(NPZ_FOLDER, f)): f for f in files}
        for future in as_completed(futures):
            patent_ids, embeddings = future.result()
            if patent_ids is None or embeddings is None:
                continue

            all_ids.extend(patent_ids)
            all_embeddings.append(embeddings)

            if len(all_ids) >= BATCH_SIZE or len(all_embeddings) >= FILES_PER_BATCH:
                success_file, total_inserted = insert_batch(all_ids, all_embeddings, futures[future], pca, total_inserted)
                if success_file:
                    last_success_file = success_file
                    write_checkpoint(last_success_file)
                    all_ids, all_embeddings = [], []

    if all_ids:
        success_file, total_inserted = insert_batch(all_ids, all_embeddings, last_success_file, pca, total_inserted)
        if success_file:
            write_checkpoint(success_file)
        print(f"✅ Sisa {len(all_ids)} data berhasil di-insert.")

    compact_collection(COLLECTION_NAME)

if __name__ == "__main__":
    main()