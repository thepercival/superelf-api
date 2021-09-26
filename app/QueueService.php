<?php

declare(strict_types=1);

namespace App;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Sports\Competition;
use Sports\Game;
use SportsImport\Queue\Game\ImportEvent as ImportGameEvent;
use SportsImport\Queue\Game\ImportDetailsEvent as ImportGameDetailsEvent;
use Interop\Queue\Message;
use Interop\Queue\Consumer;

/**
 * sudo rabbitmqctl list_queues
 * sudo rabbitmqctl list_vhosts
 * sudo rabbitmqctl start_app
 *
 * Class QueueService
 * @package App
 */
class QueueService implements ImportGameEvent, ImportGameDetailsEvent
{
    protected array $options;
    protected string $queueSuffix = '';

    public const NAME_UPDATE_GAME_QUEUE = 'update-gamedetails-queue';
    public const NAME_UPDATE_GAMEDETAILS_QUEUE = 'update-gamedetails-queue';

    public function __construct(array $options)
    {
        if (array_key_exists("queueSuffix", $options)) {
            $this->queueSuffix = $options["queueSuffix"];
            unset($options["queueSuffix"]);
        }
        $this->options = $options;
    }

    public function sendUpdateGameEvent(Game $game, \DateTimeImmutable $oldStartDateTime = null): void {
        $content = ["gameId" => $game->getId() ];
        if( $oldStartDateTime !== null ) {
            $content["oldTimestamp"] = $oldStartDateTime->getTimestamp();
        }
        $this->sendEventHelper( self::NAME_UPDATE_GAME_QUEUE, $content );
    }

    public function sendUpdateGameDetailsEvent(Game $game): void {
        $content = ["gameId" => $game->getId()];
        $this->sendEventHelper( self::NAME_UPDATE_GAMEDETAILS_QUEUE, $content );
    }

    protected function sendEventHelper(string $queueName, array $content): void {

        $context = $this->getContext();

        /** @var AmqpTopic $exchange */
        $exchange = $context->createTopic('amq.direct');
        // $topic->setType(AmqpTopic::TYPE_DIRECT);
        $exchange->addFlag(AmqpTopic::FLAG_DURABLE);
////$topic->setArguments(['alternate-exchange' => 'foo']);

        $queue = $this->getQueue( $queueName );
        $context->declareQueue($queue);

        $context->bind(new AmqpBind($exchange, $queue));

        $message = $context->createMessage(json_encode($content));
        $context->createProducer()->send($queue, $message);
    }

    public function receive(callable $callable, int $timeoutInSeconds, string $queueName): void
    {
        $context = $this->getContext();
        $consumer = $context->createConsumer($this->getQueue( $queueName ));

        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callable);

        $subscriptionConsumer->consume($timeoutInSeconds * 1000);
    }

    protected function getContext(): AmqpContext
    {
        $factory = new AmqpConnectionFactory($this->options);
        return $factory->createContext();
    }

    protected function getQueue( string $name ): AmqpQueue
    {
        /** @var AmqpQueue $queue */
        $queue = $this->getContext()->createQueue( $name . '-' . $this->queueSuffix);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        return $queue;
    }
}

