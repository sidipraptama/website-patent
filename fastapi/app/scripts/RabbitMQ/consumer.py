import pika
import json
import time
import traceback

from datetime import datetime
from app.scripts.RabbitMQ.workers import download, clean, vectorize, save
from app.config.constants import UpdateHistoryStatus
from app.db.crud import create_update_history, update_latest_update_history, get_latest_update_history, add_log

TASK_MAP = {
    "download_data": download.run,
    "clean_data": clean.run,
    "vectorize_data": vectorize.run,
    "save_data": save.run
}

def callback(ch, method, properties, body):
    update_history_id = None  # ✅ Inisialisasi awal
    try:
        message = json.loads(body.decode())
        task = message.get("task")
        payload = message.get("payload", {})

        print("[DEBUG] Payload type:", type(payload), "Content:", payload)
        print(f"[📥] Received task: {task}")

        latest_history = get_latest_update_history()
        if latest_history and latest_history.get("status") != 0:
            update_history_id = create_update_history(
                status=0,
                started_at=datetime.now(),
                description="Proses dimulai"
            )
            add_log(f"🟢 Task '{task}' diterima dan proses dimulai.")
        elif latest_history:
            update_history_id = latest_history["update_history_id"]
            add_log(f"🟢 Task '{task}' diterima dan proses dilanjutkan.")
        else:
            # Tidak ada history sebelumnya → buat baru
            update_history_id = create_update_history(
                status=0,
                started_at=datetime.now(),
                description="Proses pertama, dimulai"
            )
            latest_history = get_latest_update_history()
            add_log(f"🟢 Task '{task}' diterima dan proses dimulai.")

        payload["latest_history"] = latest_history

        if task in TASK_MAP:
            TASK_MAP[task](payload)
            print(f"[✅] Task '{task}' completed")
            ch.basic_ack(delivery_tag=method.delivery_tag)
        else:
            print(f"[❌] Unknown task: {task}")
            ch.basic_ack(delivery_tag=method.delivery_tag)

    except FileNotFoundError as e:
        print(f"[⏳] File belum tersedia: {e}")
        traceback.print_exc()
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=True)

    except Exception as e:
        print(f"[🔥] Error while processing task: {e}")
        traceback.print_exc()
        if update_history_id:
            add_log(f"❌ Error saat memproses task '{task}': {e}")
        ch.basic_ack(delivery_tag=method.delivery_tag)

def start_consumer():
    while True:
        try:
            connection = pika.BlockingConnection(pika.ConnectionParameters(host="localhost"))
            channel = connection.channel()

            channel.queue_declare(queue="task_queue", durable=True)
            channel.basic_qos(prefetch_count=1)
            channel.basic_consume(queue="task_queue", on_message_callback=callback)

            print("[*] Waiting for tasks. To exit press CTRL+C")
            latest_history = get_latest_update_history()
            update_history_id = latest_history["update_history_id"] if latest_history else None

            if update_history_id:
                add_log("Menunggu tugas baru...")

            channel.start_consuming()

        except pika.exceptions.AMQPConnectionError as e:
            latest_history = get_latest_update_history()
            update_history_id = latest_history["update_history_id"] if latest_history else None

            print(f"[⚠️] Connection lost: {e}. Reconnecting in 5 seconds...")
            if update_history_id:
                add_log(f"Connection lost: {e}. Reconnecting in 5 seconds...")
            time.sleep(5)

        except KeyboardInterrupt:
            latest_history = get_latest_update_history()
            update_history_id = latest_history["update_history_id"] if latest_history else None

            print("[👋] Stopping consumer.")
            if update_history_id:
                add_log("Stopping consumer.")
            try:
                connection.close()
            except:
                pass
            break

        except Exception as e:
            latest_history = get_latest_update_history()
            update_history_id = latest_history["update_history_id"] if latest_history else None

            print(f"[💥] Unexpected error: {e}. Restarting in 5 seconds...")
            if update_history_id:
                add_log(f"Unexpected error: {e}. Restarting in 5 seconds...")
            traceback.print_exc()
            time.sleep(5)

if __name__ == '__main__':
    start_consumer()