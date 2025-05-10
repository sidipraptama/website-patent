import pymysql
from app.core.config import settings

def get_mysql_connection():
    try:
        connection = pymysql.connect(
            host=settings.DB_HOST,
            user=settings.DB_USER,
            password=settings.DB_PASS,
            database=settings.DB_NAME,
            cursorclass=pymysql.cursors.DictCursor
        )
        return connection
    except pymysql.MySQLError as e:
        print(f"❌ Gagal terkoneksi ke MySQL: {e}")
        raise