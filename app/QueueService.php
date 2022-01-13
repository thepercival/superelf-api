<?php

declare(strict_types=1);

namespace App;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Sports\Game;
use SportsImport\Event\Game as GameEvent;
use SportsImport\Queue\Game\ImportEvents as ImportGameEvents;

/**
 * sudo rabbitmqctl list_queues
 * sudo rabbitmqctl list_vhosts
 * sudo rabbitmqctl start_app
 *
 * Class QueueService
 * @package App
 */
class QueueService implements ImportGameEvents
{
    private string $queueSuffix = '';

    public const GENERAL_QUEUE = 'game-events-queue';

    /**
     * @param array<array-key, mixed> $amqpOptions
     */
    public function __construct(private array $amqpOptions)
    {
        if (array_key_exists('suffix', $amqpOptions) === false) {
            throw new \Exception('option queue:suffix is missing', E_ERROR);
        }
        $this->queueSuffix = (string)$amqpOptions['suffix'];
        unset($amqpOptions['suffix']);
    }

    public function sendCreateEvent(Game $newGame): void
    {
        $content = ['action' => GameEvent::Create->value, 'gameId' => $newGame->getId()];
        $this->sendEventHelper(self::GENERAL_QUEUE, $content);
    }

    public function sendRescheduleEvent(\DateTimeImmutable $oldStartDateTime, Game $updatedGame): void
    {
        $content = [
            'action' => GameEvent::Reschedule->value,
            'oldTimestamp' => $oldStartDateTime->getTimestamp(),
            'gameId' => $updatedGame->getId()
        ];
        $this->sendEventHelper(self::GENERAL_QUEUE, $content);
    }

    public function sendUpdateBasicsEvent(Game $updatedGame): void
    {
        $content = [
            'action' => GameEvent::UpdateBasics->value,
            'gameId' => $updatedGame->getId()
        ];
        $this->sendEventHelper(self::GENERAL_QUEUE, $content);
    }

    public function sendUpdateScoresLineupsAndEventsEvent(Game $updatedGame): void
    {
        $content = [
            'action' => GameEvent::UpdateScoresLineupsAndEvents->value,
            'gameId' => $updatedGame->getId()
        ];
        $this->sendEventHelper(self::GENERAL_QUEUE, $content);
    }

    /**
     * @param string $queueName
     * @param array<string, mixed> $content
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\Exception\InvalidDestinationException
     * @throws \Interop\Queue\Exception\InvalidMessageException
     */
    protected function sendEventHelper(string $queueName, array $content): void
    {
        $context = $this->getContext();

        /** @var AmqpTopic $exchange */
        $exchange = $context->createTopic('amq.direct');
        // $topic->setType(AmqpTopic::TYPE_DIRECT);
        $exchange->addFlag(AmqpTopic::FLAG_DURABLE);
        ////$topic->setArguments(['alternate-exchange' => 'foo']);

        $queue = $this->getQueue($queueName);
        $context->declareQueue($queue);

        $context->bind(new AmqpBind($exchange, $queue));

        $message = $context->createMessage(json_encode($content));
        $context->createProducer()->send($queue, $message);
    }

    public function receive(callable $callable, int $timeoutInSeconds, string $queueName): void
    {
        $context = $this->getContext();
        $consumer = $context->createConsumer($this->getQueue($queueName));

        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callable);

        $subscriptionConsumer->consume($timeoutInSeconds * 1000);
    }

    protected function getContext(): AmqpContext
    {
        $factory = new AmqpConnectionFactory($this->amqpOptions);
        return $factory->createContext();
    }

    protected function getQueue(string $name): AmqpQueue
    {
        /** @var AmqpQueue $queue */
        $queue = $this->getContext()->createQueue($name . '-' . $this->queueSuffix);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        return $queue;
    }
}
