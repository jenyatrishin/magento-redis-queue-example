<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model\Driver;

use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\ExchangeInterface;
use Magento\Framework\MessageQueue\Topology\ConfigInterface as MessageQueueConfig;
use ITDelight\RedisQueue\Model\ConnectionTypeResolver;
use ITDelight\RedisQueue\Model\Client\Queue;

class Exchange implements ExchangeInterface
{
    /**
     * @var MessageQueueConfig
     */
    private MessageQueueConfig $queueConfig;

    /**
     * @var ConnectionTypeResolver
     */
    private ConnectionTypeResolver $connectionTypeResolver;

    /**
     * @var Queue
     */
    private Queue $queueClient;

    /**
     * Exchange constructor.
     * @param MessageQueueConfig $queueConfig
     * @param ConnectionTypeResolver $connectionTypeResolver
     * @param Queue $queueClient
     */
    public function __construct(
        MessageQueueConfig $queueConfig,
        ConnectionTypeResolver $connectionTypeResolver,
        Queue $queueClient
    ) {
        $this->connectionTypeResolver = $connectionTypeResolver;
        $this->queueConfig = $queueConfig;
        $this->queueClient = $queueClient;
    }

    /**
     * @param string $topic
     * @param EnvelopeInterface $envelope
     * @return null|array
     */
    public function enqueue($topic, EnvelopeInterface $envelope): ?array
    {
        $queueNames = [];
        $exchanges = $this->queueConfig->getExchanges();
        foreach ($exchanges as $exchange) {
            $connection = $exchange->getConnection();
            if ($this->connectionTypeResolver->getConnectionType($connection)) {
                foreach ($exchange->getBindings() as $binding) {
                    if ($binding->getTopic() === $topic) {
                        $queueNames[] = $binding->getDestination();
                    }
                }
            }
        }
        $data = [
            'body' => $envelope->getBody(),
            'properties' => $envelope->getProperties()
        ];
        foreach ($queueNames as $queueName) {
            $this->queueClient->enqueue($queueName, json_encode($data));
        }

        return null;
    }
}
