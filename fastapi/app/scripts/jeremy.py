import pymysql
import pymysql.cursors # Untuk DictCursor

# --- Konfigurasi Database (Sama seperti di PHP Anda) ---
# PERHATIAN: Menyimpan kredensial langsung di kode seperti ini tidak disarankan
# untuk aplikasi produksi. Pertimbangkan environment variables atau file konfigurasi.
DB_CONFIG = {
    'host': 'sql306.infinityfree.com', # Kemungkinan besar tidak bisa diakses dari luar
    'user': 'if0_39113856',
    'password': 'FzZ0qivaGp7vO',      # Password Anda
    'database': 'if0_39113856_database',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor, # Agar hasil seperti fetch_assoc() PHP
    'connect_timeout': 10 # Timeout koneksi dalam detik
}

def fetch_data_directly_from_db_pymysql():
    """
    Mencoba mengambil data langsung dari database MySQL menggunakan PyMySQL.
    FUNGSI INI KEMUNGKINAN BESAR AKAN GAGAL KONEK KE DATABASE INFINITYFREE
    KARENA BATASAN REMOTE ACCESS.
    """
    connection = None  # Inisialisasi koneksi
    try:
        print(f"Mencoba menghubungkan ke database MySQL di host: {DB_CONFIG['host']} menggunakan PyMySQL...")
        connection = pymysql.connect(**DB_CONFIG)
        
        print("Berhasil terhubung ke database!")

        with connection.cursor() as cursor: # Menggunakan DictCursor dari config
            query = "SELECT exp, lokasistuffing, cabang, produk, actualleadtime FROM shipment_monitoring"
            print(f"Menjalankan query: {query}")
            cursor.execute(query)
            data_rows = cursor.fetchall()

            print("\nData berhasil diambil dari database:")
            if data_rows:
                for row in data_rows:
                    print(row)
                return {"data": data_rows} # Mirip dengan output JSON API Anda
            else:
                print("Tidak ada data yang ditemukan.")
                return {"data": []}

    except pymysql.err.OperationalError as err:
        # Error operasional, termasuk masalah koneksi, akses ditolak, dll.
        # Kode error MySQL bisa diakses melalui err.args[0]
        error_code = err.args[0]
        print(f"PyMySQL OperationalError: {err}")
        if error_code == 1045: # ER_ACCESS_DENIED_ERROR
            print("Error: Akses ditolak. Periksa username/password.")
        elif error_code == 1044: # ER_DBACCESS_DENIED_ERROR
            print("Error: Akses ke database ditolak untuk user.")
        elif error_code == 1049: # ER_BAD_DB_ERROR
             print("Error: Database tidak ditemukan.")
        elif error_code == 2003: # ER_CON_COUNT_ERROR (Can't connect to MySQL server on '...')
            print("Error: Tidak bisa terhubung ke server MySQL (Host tidak ditemukan atau server tidak merespons).")
            print("Ini SANGAT MUNGKIN terjadi jika mencoba konek ke database InfinityFree dari luar server mereka.")
        elif error_code == 2013: # Lost connection to MySQL server during query (often due to timeout)
            print("Error: Koneksi ke server MySQL hilang saat query (seringkali karena timeout).")
            print("Ini SANGAT MUNGKIN terjadi jika mencoba konek ke database InfinityFree dari luar server mereka.")
        else:
            print(f"Error operasional PyMySQL tidak dikenal (kode: {error_code}): {err}")
        return None
    except pymysql.MySQLError as e: # Menangkap error PyMySQL lainnya
        print(f"PyMySQL MySQLError: {e}")
        return None
    except Exception as e: # Menangkap error umum lainnya
        print(f"Error umum: {e}")
        return None
    finally:
        if connection:
            print("Menutup koneksi database.")
            connection.close()

if __name__ == "__main__":
    print("PERINGATAN: Skrip ini mencoba koneksi MySQL secara remote menggunakan PyMySQL.")
    print("Ini kemungkinan besar TIDAK AKAN BERHASIL untuk database di InfinityFree karena batasan keamanan mereka.\n")
    
    hasil_db = fetch_data_directly_from_db_pymysql()

    if hasil_db:
        print("\n--- Pengambilan data langsung dari DB (secara teoretis) selesai. ---")
        # import json
        # print(json.dumps(hasil_db, indent=4, ensure_ascii=False)) # Jika ingin output mirip JSON
    else:
        print("\n--- Gagal mengambil data langsung dari DB. ---")
        print("Seperti yang diperkirakan, koneksi ke database InfinityFree dari skrip eksternal biasanya tidak diizinkan.")