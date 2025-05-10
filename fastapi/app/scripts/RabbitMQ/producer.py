import pika
import json

def send_message(task_name, payload=None):
    connection = pika.BlockingConnection(pika.ConnectionParameters(host="localhost"))
    channel = connection.channel()

    channel.queue_declare(queue="task_queue", durable=True)

    message = {
        "task": task_name,
        "payload": payload or {}
    }

    channel.basic_publish(
        exchange="",
        routing_key="task_queue",
        body=json.dumps(message),
        properties=pika.BasicProperties(
            delivery_mode=2  # make message persistent
        )
    )

    print(f"[✔] Sent task: {task_name}")
    connection.close()