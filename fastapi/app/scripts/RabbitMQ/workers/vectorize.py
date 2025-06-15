import os
import shutil
import logging
import pandas as pd
import numpy as np
import itertools
from tqdm import tqdm
from datetime import datetime
import nltk

from nltk.tokenize import sent_tokenize
from nltk.corpus import stopwords
from gensim.utils import simple_preprocess

from app.scripts.RabbitMQ.producer import send_message
from app.config.constants import UpdateHistoryStatus
from app.db.crud import update_latest_update_history, get_latest_update_history, add_log
from app.services.vectorizerSBERTa import get_text_embedding_sberta, get_batch_embeddings_sberta, preprocess_text_sberta

# Setup logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')

# NLTK setup
nltk.download('punkt')
nltk.download('stopwords')
stop_words = set(stopwords.words('english'))

# Path constants
LATEST_MILVUS_FILE = "./storage/save/latest_milvus.txt"
LATEST_ELASTIC_FILE = "./storage/save/latest_elastic.txt"
LATEST_VECTORIZE_FILE = "./storage/vectorize/latest_vectorize.txt"
LATEST_CLEAN_FILE = "./storage/clean/latest_clean.txt"
CLEANED_FILE = "./storage/clean/cleaned_patent.tsv"
OUTPUT_DIR = "./storage/vectorize/embeddings"

# Vectorize settings
BATCH_SIZE = 100

# Ensure output directory exists
os.makedirs(OUTPUT_DIR, exist_ok=True)

def read_file_content(path, default="", latest_history_id=None):
    if not os.path.exists(path):
        logging.warning(f"{path} tidak ditemukan, menggunakan default: {default}")
        add_log(f"{path} tidak ditemukan, menggunakan default: {default}")
        return default
    with open(path, "r") as f:
        return f.read().strip()

def write_file_content(path, content, latest_history_id=None):
    try:
        with open(path, "w") as f:
            f.write(content)
        logging.info(f"{os.path.basename(path)} diperbarui: {content}")
        add_log(f"{os.path.basename(path)} diperbarui: {content}")
    except Exception as e:
        logging.error(f"Gagal menulis ke {path}: {e}")
        add_log(f"Gagal menulis ke {path}: {e}")
        raise

def batchify(lst, batch_size):
    for i in range(0, len(lst), batch_size):
        yield lst[i:i + batch_size]

def run(payload):
    latest_history = payload.get("latest_history", {})

    logging.info("🔄 Memulai proses vektorisasi...")
    add_log("Proses vektorisasi dimulai.")

    latest_history_new = get_latest_update_history()

    if latest_history_new["status"] == 3:
        logging.info("[⛔] Proses dibatalkan karena berstatus canceled.")
        print("[⛔] Proses dibatalkan karena berstatus canceled.")
        add_log("Proses dibatalkan karena berstatus canceled.")
        update_latest_update_history(status=UpdateHistoryStatus.CANCELED.value, description="Proses dibatalkan", completed_at=datetime.now())
        return

    try:
        # Baca tanggal terakhir dari proses
        latest_clean = read_file_content(LATEST_CLEAN_FILE, latest_history_id=latest_history["update_history_id"])
        latest_vectorize = read_file_content(LATEST_VECTORIZE_FILE, latest_history_id=latest_history["update_history_id"])
        latest_elastic = read_file_content(LATEST_ELASTIC_FILE, latest_history_id=latest_history["update_history_id"])
        latest_milvus = read_file_content(LATEST_MILVUS_FILE, latest_history_id=latest_history["update_history_id"])

        if not os.path.exists(CLEANED_FILE):
            add_log("File cleaned tidak ditemukan.")
            update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="File cleaned tidak ditemukan.", completed_at=datetime.now())
            raise FileNotFoundError("❌ File cleaned tidak ditemukan!")

        # Jika vectorize sudah update, lanjut ke proses simpan
        if latest_vectorize >= latest_clean:
            # if latest_vectorize == latest_milvus and latest_vectorize == latest_elastic:
            #     logging.info("✅ Data vectorize sudah disimpan sebelumnya. Tidak perlu lanjut ke save.")
            #     return
            logging.info("[📦] Data sudah diproses vektorisasi.")
            logging.info("➡️ Mengirim ke tugas save...")
            add_log("✅ Data sudah diproses vektorisasi. Mengirim ke tugas save...")
            send_message("save_data", {"from": "vectorize", "directory": OUTPUT_DIR})
            return

        # Baca data cleaned
        data = pd.read_csv(CLEANED_FILE, sep="\t")
        data["patent_date"] = pd.to_datetime(data["patent_date"], errors="coerce")

        # Kosongkan folder OUTPUT_DIR
        add_log("Mengosongkan folder output untuk vektorisasi...")
        for filename in os.listdir(OUTPUT_DIR):
            file_path = os.path.join(OUTPUT_DIR, filename)
            try:
                if os.path.isfile(file_path) or os.path.islink(file_path):
                    os.unlink(file_path)
                    logging.info(f"🗑️ Menghapus file {file_path}")
                    # add_log(f"Menghapus file {file_path}")
                elif os.path.isdir(file_path):
                    shutil.rmtree(file_path)
                    logging.info(f"🗑️ Menghapus folder {file_path}")
                    add_log(f"Menghapus folder {file_path}")
            except Exception as e:
                logging.warning(f"⚠️ Gagal menghapus {file_path}. Error: {e}")
                add_log(f"Gagal menghapus {file_path}. Error: {e}")

        # Filter berdasarkan tanggal latest_vectorize
        # if latest_vectorize:
        #     data = data[data["patent_date"] > latest_vectorize]

        if data.empty:
            logging.info("✅ Tidak ada data baru untuk vektorisasi.")
            add_log("Tidak ada data baru untuk vektorisasi.")
            return

        data = data.reset_index(drop=True)
        logging.info(f"[🧾] Jumlah data untuk diproses: {len(data)}")
        add_log(f"Jumlah data untuk diproses: {len(data)}")

        for i in tqdm(range(0, len(data), BATCH_SIZE), desc="🔄 Memproses batch"):
            batch = data.iloc[i:i+BATCH_SIZE]
            abstracts = batch['patent_abstract'].dropna().astype(str).tolist()
            ids = batch['patent_id'].tolist()

            embeddings = []
            for batch in batchify(abstracts, 100):
                try:
                    vectors = get_batch_embeddings_sberta(batch)
                    embeddings.extend(vectors)
                except Exception as e:
                    logging.warning(f"⚠️ Gagal mendapatkan embedding untuk batch. Error: {e}")
                    add_log(f"Gagal embedding batch: {e}")
                    embeddings.extend([np.zeros(768) for _ in batch])

            embeddings = np.array(embeddings)
            output_file = os.path.join(OUTPUT_DIR, f'embeddings_batch_{i}.npz')
            np.savez(output_file, embeddings=embeddings, ids=ids)
            logging.info(f"📦 Batch {i} disimpan ke {output_file}")
            add_log(f"Batch {i} disimpan ke {output_file}")

        # Setelah berhasil proses, simpan latest_clean ke latest_vectorize
        write_file_content(LATEST_VECTORIZE_FILE, latest_clean, latest_history["update_history_id"])

        # Kirim pesan ke worker save
        send_message("save_data", {"from": "vectorize", "directory": OUTPUT_DIR})
        logging.info("➡️ Pesan dikirim ke worker save.")
        add_log("✅ Vectorisasi selesai. Mengirim ke tugas save...")

    except Exception as e:
        logging.error(f"❌ Terjadi kesalahan saat vektorisasi: {e}")
        add_log(f"❌ Terjadi kesalahan saat vektorisasi: {e}")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="Gagal saat vektorisasi.", completed_at=datetime.now())