import os
import time
import requests
import logging
from bs4 import BeautifulSoup
from tqdm import tqdm
from datetime import datetime
from app.scripts.RabbitMQ.producer import send_message
from requests.exceptions import RequestException, Timeout
from app.config.constants import UpdateHistoryStatus
from app.db.crud import update_latest_update_history, get_latest_update_history, add_log

# UpdateHistoryStatus Enum:
# ONGOING = 0
# SUCCESS = 1
# FAILED = 2

# Setup logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')

# Config
MAX_RETRIES = 3
RETRY_DELAY = 5
TIMEOUT = 30
LATEST_DOWNLOAD_FILE = "./storage/download/latest_download.txt"

DOWNLOAD_URLS = {
    "g_patent": "https://s3.amazonaws.com/data.patentsview.org/download/g_patent.tsv.zip",
    "g_patent_abstract": "https://s3.amazonaws.com/data.patentsview.org/download/g_patent_abstract.tsv.zip"
}

HEADERS = {
    "User-Agent": "Mozilla/5.0 (compatible; PatentBot/1.0; +https://example.com/bot)"
}

def fetch_latest_version(latest_history_id=None):
    url = "https://patentsview.org/download/data-download-tables"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Referer": "https://patentsview.org/",
        "Accept-Language": "en-US,en;q=0.5"
    }

    try:
        response = requests.get(url, headers=headers, timeout=TIMEOUT)
        response.raise_for_status()
    except RequestException as e:
        logging.error(f"[❌] Gagal mengambil informasi versi: {e}")
        add_log(f"Gagal mengambil informasi versi: {e}")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="Gagal mengambil informasi versi", completed_at=datetime.now())
        return None

    soup = BeautifulSoup(response.text, "html.parser")
    version_td = soup.find("td", {"headers": "view-field-update-version-table-column"})

    if version_td:
        time_tag = version_td.find("time")
        if time_tag and time_tag.get("datetime"):
            return time_tag["datetime"][:10]

    logging.warning("[⚠️] Tidak bisa mem-parsing versi terbaru dari halaman.")
    add_log("Tidak bisa mem-parsing versi terbaru dari halaman.")
    return None

def read_version_file(filepath, latest_history_id=None):
    try:
        if os.path.exists(filepath):
            with open(filepath, "r") as f:
                return f.read().strip()
    except Exception as e:
        logging.warning(f"[⚠️] Gagal membaca {filepath}: {e}")
        add_log(f"Gagal membaca {filepath}: {e}")
    return None

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

def download_file(url, filename, version, latest_history_id=None):
    dated_filename = f"{version.replace('-', '')}_{filename}"
    local_path = os.path.join("storage/download", dated_filename)
    os.makedirs("storage/download", exist_ok=True)

    if os.path.exists(local_path):
        logging.info(f"[🗑️] File {dated_filename} sudah ada, menghapus sebelum download ulang.")
        add_log(f"File {dated_filename} sudah ada, menghapus sebelum download ulang.")
        os.remove(local_path)  # ✅ Hapus file yang lama

    attempt = 0
    while attempt < MAX_RETRIES:
        try:
            logging.info(f"[⬇️] Mengunduh {dated_filename} (Percobaan {attempt + 1})...")
            add_log(f"Mengunduh {dated_filename} (Percobaan {attempt + 1})...")
            with requests.get(url, stream=True, timeout=TIMEOUT) as response:
                response.raise_for_status()
                total_size = int(response.headers.get("content-length", 0))
                block_size = 1024 * 1024

                with open(local_path, "wb") as f, tqdm(
                    desc=dated_filename,
                    total=total_size,
                    unit='B',
                    unit_scale=True,
                    unit_divisor=1024,
                ) as bar:
                    for chunk in response.iter_content(chunk_size=block_size):
                        if chunk:
                            f.write(chunk)
                            bar.update(len(chunk))

            if os.path.getsize(local_path) == 0:
                raise Exception("File hasil unduhan kosong")

            logging.info(f"[✅] {dated_filename} berhasil diunduh.")
            add_log(f"{dated_filename} berhasil diunduh.")
            return local_path
        except (RequestException, Timeout, Exception) as e:
            logging.warning(f"[⚠️] Gagal mengunduh {dated_filename}: {e}")
            add_log(f"Gagal mengunduh {dated_filename}: {e}")
            attempt += 1
            if attempt < MAX_RETRIES:
                logging.info(f"[🔁] Mencoba ulang dalam {RETRY_DELAY} detik...")
                add_log(f"Mencoba ulang dalam {RETRY_DELAY} detik...")
                time.sleep(RETRY_DELAY)
            else:
                logging.error(f"[❌] Gagal mengunduh {dated_filename} setelah {MAX_RETRIES} kali percobaan.")
                add_log(f"Gagal mengunduh {dated_filename} setelah {MAX_RETRIES} kali percobaan.")
                update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="Gagal mengunduh file.", completed_at=datetime.now())
                raise

def run(payload):
    latest_history = payload.get("latest_history", {})
    
    logging.info("🔄 Memulai proses download...")
    add_log("Proses download dimulai.")

    latest_history_new = get_latest_update_history()

    print(latest_history_new)

    if latest_history_new["status"] == 3:
        logging.info("[⛔] Proses dibatalkan karena berstatus canceled.")
        print("[⛔] Proses dibatalkan karena berstatus canceled.")
        add_log("Proses dibatalkan karena berstatus canceled.")
        return

    logging.info("[📡] Mengecek versi terbaru data paten...")
    add_log("Mengecek versi terbaru data paten...")

    latest_version = fetch_latest_version(latest_history["update_history_id"])
    if not latest_version:
        logging.error("[⛔] Versi terbaru tidak ditemukan, menghentikan proses.")
        add_log("Versi terbaru tidak ditemukan, menghentikan proses.")
        update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="Gagal mengambil versi terbaru download", completed_at=datetime.now())
        return

    stored_version = read_version_file(LATEST_DOWNLOAD_FILE)
    logging.info(f"[🗓️] Versi Terbaru: {latest_version}, Versi Tersimpan: {stored_version}")
    add_log("Versi terbaru ditemukan, memeriksa versi tersimpan...")

    if latest_version != stored_version:
        logging.info("[🚀] Versi baru ditemukan. Memulai proses unduh...")
        add_log("Versi baru ditemukan. Memulai proses unduh...")

        for name, url in DOWNLOAD_URLS.items():
            filename = os.path.basename(url)
            try:
                download_file(url, filename, latest_version, latest_history["update_history_id"])
            except Exception as e:
                logging.error(f"[❌] Menghentikan proses karena gagal mengunduh {filename}")
                add_log(f"Gagal mengunduh {filename}: {e}")
                update_latest_update_history(status=UpdateHistoryStatus.FAILED.value, description="Gagal mengunduh file.", completed_at=datetime.now())
                return

        write_version_file(LATEST_DOWNLOAD_FILE, latest_version, latest_history["update_history_id"])
        logging.info("[📨] Unduhan selesai. Mengirim tugas 'clean_data' ke RabbitMQ...")
        add_log("✅ Unduhan selesai. Mengirim tugas...")
        send_message("clean_data", {"from": "download"})
    else:
        logging.info("[⏸️] Tidak ada versi baru. Melewati proses unduh.")
        add_log("✅ Tidak ada versi baru. Melewati proses unduh.")
        send_message("clean_data", {"from": "download"})