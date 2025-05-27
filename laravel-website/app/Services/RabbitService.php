<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitService
{
    public static function sendDownloadTask()
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('task_queue', false, true, false, false);

        $payload = json_encode([
            'task' => 'download_data',
            'payload' => (object) []
        ]);
        $msg = new AMQPMessage($payload, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        $channel->basic_publish($msg, '', 'task_queue');
        $channel->close();
        $connection->close();
    }
}
