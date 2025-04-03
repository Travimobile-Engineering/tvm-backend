<?php
namespace App\Services;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
class RabbitMQService{
   protected $connection;
   protected $channel;
   protected $queue;

   public function __construct(){
    $this->queue = env('RABBITMQ_QUEUE', 'email');
    $this->connection = new AMQPStreamConnection(
        env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD')
    );
    $this->channel = $this->connection->channel();
    $this->channel->queue_declare($this->queue, false, true, false, false);
   }

   public function publish($message){
    $msg = new AMQPMessage($message);
    $this->channel->basic_publish($msg, '', $this->queue);
   }

   public function consume($callback)
    {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
