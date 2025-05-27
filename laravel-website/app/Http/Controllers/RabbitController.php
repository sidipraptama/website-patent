<?php

namespace App\Http\Controllers;

use App\Services\RabbitService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitController extends Controller
{
    public function send()
    {
        RabbitService::sendDownloadTask();
        return response()->json(['status' => 'Task download_data sent!']);
    }
}
