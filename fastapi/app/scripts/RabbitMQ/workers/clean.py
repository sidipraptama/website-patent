import os
import pandas as pd
from datetime import datetime
import logging
from app.scripts.RabbitMQ.producer import send_message
import zipfile
from app.config.constants import UpdateHistoryStatus
from app.db.crud import update_latest_update_history, get_latest_update_history, add_log

# Setup logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')

LATEST_VECTORIZE_FILE = "./storage/vectorize/latest_vectorize.txt"
LATEST_DOWNLOAD_FILE = "./storage/download/latest_download.txt"
LATEST_CLEAN_FILE = "./storage/clean/latest_clean.txt"
OUTPUT_FILE = "./storage/clean/cleaned_patent.tsv"
EXTRACT_DIR = "./storage/clean/extracted"
DOWNLOAD_DIR = "./storage/download"

def unzip_file(zip_path, extract_to, latest_history_id=None):
    try:
        add_log(f"Mulai mengekstrak {zip_path} ke {extract_to}")
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(extract_to)
        logging.info(f"[🗃️] File {zip_path} berhasil diekstrak.")
        add_log(f"File {zip_path} berhasil diekstrak.")
    except Exception as e:
        logging.error(f"[❌] Gagal mengekstrak {zip_path}: {e}")
        add_log(f"Gagal mengekstrak {zip_path}: {e}")
        raise

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

def find_extracted_file(dir_path, filename_contains):
    for fname in os.listdir(dir_path):
        if filename_contains in fname and fname.endswith(".tsv"):
            return os.path.join(dir_path, fname)
    return None

def run(payload):
    latest_history = payload.get("latest_history", {})

    logging.info("🔄 Memulai proses cleaning...")
    add_log("Proses cleaning dimulai.")

    latest_history_new = get_latest_update_history()

    if latest_history_new["status"] == 3:
        logging.info("[⛔] Proses dibatalkan karena berstatus canceled.")
        print("[⛔] Proses dibatalkan karena berstatus canceled.")
        add_log("Proses dibatalkan karena berstatus canceled.")
        update_latest_update_history(status=UpdateHistoryStatus.CANCELED.value, description="Proses dibatalkan", completed_at=datetime.now())
        return

    try:
        # Baca tanggal latest_download dan latest_clean
        latest_vectorize = read_file_content(LATEST_VECTORIZE_FILE, latest_history_id=latest_history["update_history_id"])
        latest_download = read_file_content(LATEST_DOWNLOAD_FILE, latest_history_id=latest_history["update_history_id"])
        latest_clean = read_file_content(LATEST_CLEAN_FILE, latest_history_id=latest_history["update_history_id"])

        # Cek jika perlu extract
        formatted_download_date = latest_download.replace("-", "")  # dari 2025-03-17 jadi 20250317
        zip_patent = f"./storage/download/{formatted_download_date}_g_patent.tsv.zip"
        zip_abstract = f"./storage/download/{formatted_download_date}_g_patent_abstract.tsv.zip"

        if not os.path.exists(zip_patent) or not os.path.exists(zip_abstract):
            add_log(f"File zip {zip_patent} atau {zip_abstract} tidak ditemukan.")
            raise FileNotFoundError(f"[❌] File zip {zip_patent} atau {zip_abstract} tidak ditemukan.")

        # Bersihkan direktori extract sebelum ekstraksi
        if os.path.exists(EXTRACT_DIR):
            for f in os.listdir(EXTRACT_DIR):
                os.remove(os.path.join(EXTRACT_DIR, f))
        else:
            os.makedirs(EXTRACT_DIR)

        if os.path.exists(OUTPUT_FILE) and latest_clean >= latest_download:
            # if latest_clean == latest_vectorize:
            #     logging.info("✅ File cleaned sudah di-vektorisasi sebelumnya. Tidak perlu kirim lagi.")
            #     return
            
            logging.info(f"[📂] File cleaned sudah ada dan up-to-date.")
            logging.info("➡️ Mengirim ke tugas vectorize...")
            add_log("✅ File cleaned sudah ada dan up-to-date. Mengirim ke tugas vectorize...")
            send_message("vectorize_data", {"from": "clean", "file": OUTPUT_FILE})
            return

        # if os.path.exists(OUTPUT_FILE) and latest_clean >= latest_download:
        #     logging.info(f"[🗑️] File cleaned sudah ada dan up-to-date. Menghapus untuk proses ulang.")
        #     add_log("File cleaned sudah ada dan up-to-date. Menghapus untuk proses ulang.")

        #     os.remove(OUTPUT_FILE)
        
        # Unzip kedua file
        unzip_file(zip_patent, EXTRACT_DIR, latest_history_id=latest_history["update_history_id"])
        unzip_file(zip_abstract, EXTRACT_DIR, latest_history_id=latest_history["update_history_id"])

        file_patent = find_extracted_file(EXTRACT_DIR, "g_patent.tsv")
        file_abstract = find_extracted_file(EXTRACT_DIR, "g_patent_abstract.tsv")

        if not file_patent or not file_abstract:
            add_log(f"File {file_patent} atau {file_abstract} tidak ditemukan.")
            update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="File tidak ditemukan setelah ekstraksi.", completed_at=datetime.now())
            raise FileNotFoundError(f"File {file_patent} atau {file_abstract} tidak ditemukan.")
        
        # Baca file TSV
        add_log(f"Membaca file {file_patent} dan {file_abstract}...")
        g_patent = pd.read_csv(file_patent, sep="\t")
        g_patent_abstract = pd.read_csv(file_abstract, sep="\t")

        required_cols = ["patent_id", "patent_date", "patent_title"]
        for col in required_cols:
            if col not in g_patent.columns:
                add_log(f"Kolom '{col}' tidak ditemukan dalam data paten.")
                update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description=f"Kolom '{col}' tidak ditemukan dalam data paten.", completed_at=datetime.now())
                raise ValueError(f"Kolom '{col}' tidak ditemukan dalam data paten")
            
        add_log("Merge data g_patent dan g_patent_abstract...")
        merged = pd.merge(g_patent, g_patent_abstract, on="patent_id", how="inner")
        merged = merged.drop(columns=["withdrawn", "filename"], errors="ignore")
        merged["patent_date"] = pd.to_datetime(merged["patent_date"], errors="coerce")

        # Setelah proses merge dan konversi tanggal
        merged["patent_date"] = pd.to_datetime(merged["patent_date"], errors="coerce")
        merged["patent_abstract"] = merged["patent_abstract"].astype(str).str.strip()

        # Pisahkan data lama dan baru
        existing_data = merged[merged["patent_date"] <= latest_clean]  # ✅ Tambahan untuk anti-duplikat
        filtered = merged[merged["patent_date"] > latest_clean]

        # Filter hanya abstrak yang belum ada sebelumnya (berdasarkan data lama)
        existing_abstracts = set(existing_data['patent_abstract'].dropna().str.strip())  # ✅
        filtered = filtered[~filtered['patent_abstract'].str.strip().isin(existing_abstracts)]  # ✅

        if filtered.empty:
            logging.info("✅ Tidak ada data baru yang valid setelah filter duplikat abstrak.")
            add_log("Tidak ada data baru yang valid setelah filter duplikat abstrak.")
            return

        # Dedup dan urutkan
        add_log("Menghapus duplikat dan mengurutkan data...")
        filtered = filtered.sort_values(by=["patent_title", "patent_date"], ascending=[True, False])
        filtered = filtered.drop_duplicates(subset="patent_title", keep="first")
        filtered = filtered.sort_values(by=["patent_abstract", "patent_date"], ascending=[True, False])
        filtered = filtered.drop_duplicates(subset="patent_abstract", keep="first")

        filtered.to_csv(OUTPUT_FILE, sep="\t", index=False)
        logging.info(f"✅ Cleaned data disimpan di {OUTPUT_FILE}")
        add_log(f"Cleaned data disimpan di {OUTPUT_FILE}")

        # Update latest_clean.txt dengan tanggal maksimum dari data terbaru
        write_file_content(LATEST_CLEAN_FILE, latest_download)

        logging.info("➡️ Mengirim ke tugas vectorize...")
        add_log("✅ Clean data selesai. Mengirim ke tugas vectorize...")
        send_message("vectorize_data", {"from": "clean", "file": OUTPUT_FILE})

    except Exception as e:
        logging.error(f"❌ Terjadi kesalahan: {e}")
        add_log(f"Terjadi kesalahan: {e}")
        send_message("vectorize_data", {"from": "clean", "file": OUTPUT_FILE})