<?php

namespace App\Http\Controllers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitController extends Controller
{
    public function send()
    {
        // Establish a connection to the RabbitMQ server
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare the queue we want to send messages to (task_queue from your Python code)
        $channel->queue_declare('task_queue', false, true, false, false);

        // Prepare the message payload
        $payload = json_encode([
            'task' => 'download_data',  // Specify the task to run in the Python worker
            'payload' => (object) []
        ]);

        // Create a new message with the payload
        $msg = new AMQPMessage(
            $payload,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]  // Ensure the message is persistent
        );

        // Publish the message to the 'task_queue' RabbitMQ queue
        $channel->basic_publish($msg, '', 'task_queue');

        // Close the channel and the connection
        $channel->close();
        $connection->close();

        // Return a response indicating the message was sent
        return response()->json(['status' => 'Task download_data sent!']);
    }
}
