<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model\Driver;

use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use ITDelight\RedisQueue\Model\Client\Queue as Client;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Psr\Log\LoggerInterface;

class Queue implements QueueInterface
{
    /**
     * Queue constructor.
     * @param Client $client
     * @param string $queueName
     * @param MessageEncoder $messageEncoder
     * @param EnvelopeFactory $envelopeFactory
     * @param LoggerInterface $logger
     * @param int $interval
     */
    public function __construct(
        private Client $client,
        private string $queueName,
        private MessageEncoder $messageEncoder,
        private EnvelopeFactory $envelopeFactory,
        private LoggerInterface $logger,
        private int $interval = 5
    ) {
    }

    /**
     * @return EnvelopeInterface|null
     */
    public function dequeue(): ?EnvelopeInterface
    {
        $envelope = null;
        $message = $this->client->dequeue($this->queueName);
        if ($message) {
            $message = json_decode($message, true);
            $envelope = $this->envelopeFactory->create([
                'body' => $message['body'],
                'properties' => $message['properties']
            ]);
        }

        return $envelope;
    }

    /**
     * @param EnvelopeInterface $envelope
     */
    public function acknowledge(EnvelopeInterface $envelope): void
    {
        $this->client->acknowledge($this->queueName);
    }

    /**
     * @param EnvelopeInterface $envelope
     */
    public function push(EnvelopeInterface $envelope): void
    {
        $this->client->enqueue($this->queueName, json_encode([
            'body' => $envelope->getBody(),
            'properties' => $envelope->getProperties()
        ]));
    }

    /**
     * @param EnvelopeInterface $envelope
     * @param bool $requeue
     * @param null $rejectionMessage
     */
    public function reject(EnvelopeInterface $envelope, $requeue = true, $rejectionMessage = null): void
    {
        $this->logger->debug('Message from queue ' . $this->queueName . ' was rejected');
    }

    /**
     * @param array|callable $callback
     */
    public function subscribe($callback): void
    {
        while (true) {
            while ($envelope = $this->dequeue()) {
                try {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    call_user_func($callback, $envelope);
                } catch (\Exception $e) {
                    $this->reject($envelope);
                }
            }
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            sleep($this->interval);
        }
    }
}
