from datetime import datetime
from app.db.mysql import get_mysql_connection

def create_update_history(status=None, started_at=None, completed_at=None, description=None):
    conn = get_mysql_connection()
    try:
        with conn.cursor() as cursor:
            fields = []
            placeholders = []
            values = []

            if status is not None:
                fields.append("status")
                placeholders.append("%s")
                values.append(status)
            if started_at is not None:
                fields.append("started_at")
                placeholders.append("%s")
                values.append(started_at)
            if completed_at is not None:
                fields.append("completed_at")
                placeholders.append("%s")
                values.append(completed_at)
            if description is not None:
                fields.append("description")
                placeholders.append("%s")
                values.append(description)

            if not fields:
                return False  # Tidak ada yang dimasukkan

            sql = f"""
                INSERT INTO update_history ({', '.join(fields)})
                VALUES ({', '.join(placeholders)})
            """
            cursor.execute(sql, values)
            conn.commit()
            return cursor.lastrowid  # Mengembalikan ID history terbaru
    finally:
        conn.close()

def update_latest_update_history(status=None, started_at=None, completed_at=None, description=None):
    conn = get_mysql_connection()
    try:
        with conn.cursor() as cursor:
            # Ambil ID terakhir (terbaru)
            cursor.execute("SELECT update_history_id FROM update_history ORDER BY update_history_id DESC LIMIT 1")
            result = cursor.fetchone()
            if not result:
                print("❌ Tidak ada data update_history untuk diperbarui.")
                return False

            update_history_id = result["update_history_id"]

            # Bangun query update
            fields = []
            values = []

            if status is not None:
                fields.append("status = %s")
                values.append(status)
            if started_at is not None:
                fields.append("started_at = %s")
                values.append(started_at)
            if completed_at is not None:
                fields.append("completed_at = %s")
                values.append(completed_at)
            if description is not None:
                fields.append("description = %s")
                values.append(description)

            if not fields:
                return False  # Tidak ada yang diupdate

            values.append(update_history_id)

            sql = f"""
                UPDATE update_history
                SET {', '.join(fields)}
                WHERE update_history_id = %s
            """
            cursor.execute(sql, values)
            conn.commit()
            return True
    finally:
        conn.close()

def add_log(message: str):
    conn = get_mysql_connection()
    try:
        with conn.cursor() as cursor:
            # Ambil update_history_id dan status terbaru
            cursor.execute("""
                SELECT update_history_id, status 
                FROM update_history 
                ORDER BY update_history_id DESC 
                LIMIT 1
            """)
            result = cursor.fetchone()
            if not result:
                print("❌ Tidak ada update_history tersedia untuk ditambahkan log.")
                return

            update_history_id = result["update_history_id"]
            status = result["status"]

            if status not in (0, 3):
                print(f"⚠️ Status update_history_id {update_history_id} bukan 'Ongoing atau Canceled' (status=0). Log tidak ditambahkan.")
                return

            # Insert log
            sql = """
                INSERT INTO update_logs (update_history_id, message)
                VALUES (%s, %s)
            """
            cursor.execute(sql, (update_history_id, message))
            conn.commit()
            print(f"✅ Log berhasil ditambahkan untuk update_history_id {update_history_id}.")
    finally:
        conn.close()

def get_latest_update_history():
    conn = get_mysql_connection()
    try:
        # No need for dictionary=True, it's already handled by cursorclass
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT update_history_id, status, started_at, completed_at, description
                FROM update_history
                ORDER BY update_history_id DESC
                LIMIT 1
            """)
            result = cursor.fetchone()
            if not result:
                print("❌ Tidak ada data update_history.")
                return None
            return result  # Bisa akses seperti result["status"]
    finally:
        conn.close()

def update_latest_updated_at():
    conn = get_mysql_connection()
    try:
        with conn.cursor() as cursor:
            now = datetime.now()

            sql = """
                UPDATE update_settings
                SET last_updated_at = %s
                ORDER BY id DESC
                LIMIT 1
            """
            cursor.execute(sql, (now,))
            conn.commit()
            print(f"✅ latest_updated_at berhasil diperbarui untuk 1 data terbaru.")
            return True
    except Exception as e:
        print(f"❌ Gagal memperbarui latest_updated_at: {e}")
        return False
    finally:
        conn.close()