<?php

namespace Wallabag\CoreBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPEntryConsumer extends AbstractConsumer implements ConsumerInterface
{
    public function execute(AMQPMessage $msg)
    {
        return $this->handleMessage($msg->body);
    }
}
