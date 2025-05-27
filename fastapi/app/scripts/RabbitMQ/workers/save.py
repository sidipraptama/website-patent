import os
import numpy as np
import logging
import pandas as pd
from app.db.elastic import bulk_insert_patents
from app.db.milvus import connect_milvus, insert_vectors_batch
from pymilvus import utility
from elasticsearch import Elasticsearch
from datetime import datetime

from app.config.constants import UpdateHistoryStatus
from app.db.crud import update_latest_update_history, get_latest_update_history, add_log, update_latest_updated_at

# Setup logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')

# Path constants
CHECKPOINT_FILE = "./storage/save/check_point.txt"
VECTORIZE_FOLDER = "./storage/vectorize/embeddings"
ELASTIC_DATA_FILE = "./storage/clean/cleaned_patent.tsv"
MILVUS_COLLECTION_NAME = "patent_vectors_sberta"
BATCH_SIZE = 1000
FILES_PER_BATCH = 10
EMBEDDING_DIM = 768
LATEST_MILVUS_FILE = "./storage/save/latest_milvus.txt"
LATEST_ELASTIC_FILE = "./storage/save/latest_elastic.txt"
LATEST_VECTORIZE_FILE = "./storage/vectorize/latest_vectorize.txt"

elasticsearch_host = os.getenv('ELASTICSEARCH_HOST', 'http://localhost:9200')
es = Elasticsearch(elasticsearch_host)

# Function to read checkpoint
def read_checkpoint():
    """Membaca checkpoint terakhir."""
    if os.path.exists(CHECKPOINT_FILE):
        with open(CHECKPOINT_FILE, "r") as f:
            return f.read().strip()
    return None

def reset_checkpoint():
    if os.path.exists(CHECKPOINT_FILE):
        os.remove(CHECKPOINT_FILE)
        logging.info("♻️ Checkpoint direset.")
        add_log("Checkpoint direset sebelum insert ke Milvus.")

# Function to write checkpoint
def write_checkpoint(filename):
    """Menyimpan checkpoint ke file."""
    with open(CHECKPOINT_FILE, "w") as f:
        f.write(filename)

def write_version_file(filepath, version, latest_history_id=None):
    try:
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        with open(filepath, "w") as f:
            f.write(version)
        logging.info(f"[💾] File {os.path.basename(filepath)} diperbarui: {version}")
        add_log(f"File {os.path.basename(filepath)} diperbarui: {version}")
    except Exception as e:
        logging.error(f"[❌] Gagal menulis file versi: {e}")
        add_log(f"Gagal menulis file versi: {e}")

# Function to read file content
def read_file_content(path, default="", latest_history_id=None):
    if not os.path.exists(path):
        logging.warning(f"{path} tidak ditemukan, menggunakan default: {default}")
        add_log(f"{path} tidak ditemukan, menggunakan default: {default}")
        return default
    with open(path, "r") as f:
        return f.read().strip()

# Function to process NPZ file
def process_npz_file(filepath, latest_history_id=None):
    """Membaca dan memproses file NPZ."""
    try:
        data = np.load(filepath, mmap_mode="r")
        if "ids" not in data or "embeddings" not in data:
            logging.warning(f"⚠️ Format salah di {filepath}, dilewati.")
            add_log(f"Format salah di {filepath}, dilewati.")
            return None, None, filepath

        patent_ids = data["ids"].astype(str)
        embeddings = data["embeddings"]

        if embeddings.shape[1] != EMBEDDING_DIM:
            logging.warning(f"⚠️ Dimensi salah di {filepath}, dilewati.")
            add_log(f"Dimensi salah di {filepath}, dilewati.")
            return None, None, filepath

        logging.info(f"📂 Berhasil memuat {filepath} ({len(patent_ids)} data)")
        return patent_ids, embeddings, filepath
    except Exception as e:
        logging.error(f"❌ Gagal memproses {filepath}: {e}")
        add_log(f"Gagal memproses {filepath}: {e}")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description=f"Gagal memproses {filepath}: {e}", completed_at=datetime.now())
        return None, None, filepath

# Function to insert batch to Milvus
def insert_batch(all_ids, all_embeddings, last_success_file, total_inserted, latest_history_id=None):
    """Memasukkan batch data ke Milvus."""
    try:
        if not all_ids or not all_embeddings:
            return last_success_file, total_inserted

        all_embeddings = np.vstack(all_embeddings)
        all_embeddings = all_embeddings.astype(np.float32)

        if len(all_ids) != all_embeddings.shape[0]:
            add_log(f"❌ Misalignment: {len(all_ids)} vs {all_embeddings.shape[0]}")
            raise ValueError(f"❌ Misalignment: {len(all_ids)} vs {all_embeddings.shape[0]}")

        insert_vectors_batch(MILVUS_COLLECTION_NAME, all_ids, all_embeddings, BATCH_SIZE)
        logging.info(f"✅ {len(all_ids)} data di-insert.")

        total_inserted += len(all_ids)

        return last_success_file, total_inserted
    except Exception as e:
        logging.error(f"❌ Gagal insert ke Milvus pada file terakhir '{last_success_file}': {e}")
        add_log(f"Gagal insert ke Milvus pada file terakhir '{last_success_file}': {e}")
        return last_success_file, total_inserted

# Function to insert data into Elasticsearch
def insert_elasticsearch(latest_history_id=None):
    """Memasukkan metadata paten ke Elasticsearch."""
    try:
        # Read and insert the cleaned patent data
        data = read_tsv(ELASTIC_DATA_FILE, latest_history_id=latest_history_id)
        if data:
            bulk_insert_patents(es, data)
            logging.info("✅ Data berhasil dimasukkan ke Elasticsearch!")
            add_log("Data berhasil dimasukkan ke Elasticsearch!")
        else:
            logging.warning("⚠️ Tidak ada data untuk dimasukkan.")
            add_log("Tidak ada data untuk dimasukkan.")
    except Exception as e:
        logging.error(f"❌ Gagal memasukkan data ke Elasticsearch: {e}")
        add_log(f"Gagal memasukkan data ke Elasticsearch: {e}")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description=f"Gagal memasukkan data ke Elasticsearch: {e}", completed_at=datetime.now())

# Function to read TSV file for Elasticsearch insertion
def read_tsv(file_path, latest_history_id=None):
    """Membaca file TSV dan mengembalikannya dalam format dict."""
    try:
        df = pd.read_csv(file_path, sep='\t')
        return df.to_dict(orient='records')
    except Exception as e:
        logging.error(f"❌ Gagal membaca file TSV: {e}")
        add_log(f"Gagal membaca file TSV: {e}")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description=f"Gagal membaca file TSV: {e}", completed_at=datetime.now())
        return []

# Main function to process and save data
def run(payload):
    latest_history = payload.get("latest_history", {})

    logging.info("🔄 Memulai proses save data...")
    add_log("Proses save data dimulai.")

    latest_history_new = get_latest_update_history()

    if latest_history_new["status"] == 3:
        logging.info("[⛔] Proses dibatalkan karena berstatus canceled.")
        print("[⛔] Proses dibatalkan karena berstatus canceled.")
        add_log("Proses dibatalkan karena berstatus canceled.")
        update_latest_update_history(status=UpdateHistoryStatus.CANCELED.value, description="Proses dibatalkan", completed_at=datetime.now())
        return
    
    try:
        # Membaca latest_milvus, latest_elastic, dan latest_vectorize
        latest_milvus = read_file_content(LATEST_MILVUS_FILE, latest_history_id=latest_history["update_history_id"])
        latest_elastic = read_file_content(LATEST_ELASTIC_FILE, latest_history_id=latest_history["update_history_id"])
        latest_vectorize = read_file_content(LATEST_VECTORIZE_FILE, latest_history_id=latest_history["update_history_id"])

        # Proses ke Milvus jika latest_milvus lebih tua dari latest_vectorize
        if latest_milvus < latest_vectorize:
            logging.info("🚀 Proses insert ke Milvus dimulai...")
            add_log("Proses insert ke Milvus dimulai...")
            try:
                # Prepare Milvus
                connect_milvus()
                logging.info("✅ Milvus siap digunakan.")
                add_log("Milvus siap digunakan.")
            except Exception as e:
                logging.error(f"❌ Gagal menyiapkan Milvus: {e}")
                add_log(f"Gagal menyiapkan Milvus: {e}")
                return
            
            reset_checkpoint()

            # Read checkpoint to determine where to start
            checkpoint = read_checkpoint()
            files = sorted(os.listdir(VECTORIZE_FOLDER), key=lambda x: int(x.split('_')[-1].split('.')[0]))

            if checkpoint and checkpoint in files:
                files = files[files.index(checkpoint) + 1:]

            all_ids, all_embeddings = [], []
            last_success_file = None
            total_inserted = 0
            failed_files = []

            for file in files:
                file_path = os.path.join(VECTORIZE_FOLDER, file)
                patent_ids, embeddings, _ = process_npz_file(file_path, latest_history["update_history_id"])

                if patent_ids is None or embeddings is None:
                    failed_files.append(file_path)
                    continue

                all_ids.extend(patent_ids)
                all_embeddings.append(embeddings)

                if len(all_ids) >= BATCH_SIZE or len(all_embeddings) >= FILES_PER_BATCH:
                    last_success_file, total_inserted = insert_batch(all_ids, all_embeddings, file_path, total_inserted, latest_history["update_history_id"])
                    if last_success_file:
                        write_checkpoint(last_success_file)
                        all_ids, all_embeddings = [], []

            # Insert remaining data if any
            if all_ids:
                last_success_file, total_inserted = insert_batch(all_ids, all_embeddings, last_success_file, total_inserted, latest_history["update_history_id"])
                if last_success_file:
                    write_checkpoint(last_success_file)
            
            add_log(f"✅ {total_inserted} data berhasil di-insert ke Milvus.")

            # Update Milvus timestamp after successful insertion
            write_version_file(LATEST_MILVUS_FILE, latest_vectorize)
            latest_elastic = read_file_content(LATEST_ELASTIC_FILE, latest_history_id=latest_history["update_history_id"])

            # Log if there were any failed files during Milvus insertion
            if failed_files:
                logging.warning("⚠️ File yang gagal diproses ke Milvus:")
                add_log("File yang gagal diproses ke Milvus:")
                for f in failed_files:
                    logging.warning(f" - {f}")
            else:
                logging.info("✅ Semua data berhasil diproses ke Milvus.")
                add_log("Semua data berhasil diproses ke Milvus.")
        else:
            logging.info("✅ Data di Milvus sudah up-to-date.")
            add_log("Data di Milvus sudah up-to-date.")

        # Membaca latest_milvus, latest_elastic, dan latest_vectorize
        latest_milvus = read_file_content(LATEST_MILVUS_FILE, latest_history_id=latest_history["update_history_id"])
        latest_elastic = read_file_content(LATEST_ELASTIC_FILE, latest_history_id=latest_history["update_history_id"])
        latest_vectorize = read_file_content(LATEST_VECTORIZE_FILE, latest_history_id=latest_history["update_history_id"])

        # Proses ke Elasticsearch jika latest_elastic lebih tua dari latest_milvus
        if latest_elastic < latest_milvus:
            logging.info("🚀 Proses insert ke Elasticsearch dimulai...")
            add_log("Proses insert ke Elasticsearch dimulai...")
            insert_elasticsearch(latest_history["update_history_id"])

            # Update Elasticsearch timestamp after successful insertion
            write_version_file(LATEST_ELASTIC_FILE, latest_milvus)
        else:
            logging.info("✅ Data di Elasticsearch sudah up-to-date.")
            add_log("Data di Elasticsearch sudah up-to-date.")

        # Membaca latest_milvus, latest_elastic, dan latest_vectorize
        latest_milvus = read_file_content(LATEST_MILVUS_FILE, latest_history_id=latest_history["update_history_id"])
        latest_elastic = read_file_content(LATEST_ELASTIC_FILE, latest_history_id=latest_history["update_history_id"])
        latest_vectorize = read_file_content(LATEST_VECTORIZE_FILE, latest_history_id=latest_history["update_history_id"])

        if latest_elastic >= latest_milvus and latest_milvus >= latest_vectorize:
            add_log("✅ Proses save selesai.")
            update_latest_update_history(
                status=UpdateHistoryStatus.SUCCESS.value,
                description="Proses save berhasil.",
                completed_at=datetime.now()
            )
            update_latest_updated_at()

    except Exception as e:
        logging.error(f"❌ Terjadi kesalahan saat save data: {e}")
        add_log(f"Terjadi kesalahan saat save data: {e}")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="Gagal menyimpan data.", completed_at=datetime.now())