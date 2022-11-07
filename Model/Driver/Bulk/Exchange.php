<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model\Driver\Bulk;

use Magento\Framework\MessageQueue\Bulk\ExchangeInterface;
use Magento\Framework\MessageQueue\Topology\ConfigInterface as MessageQueueConfig;
use ITDelight\RedisQueue\Model\ConnectionTypeResolver;
use ITDelight\RedisQueue\Model\Client\Queue;

class Exchange implements ExchangeInterface
{
    private MessageQueueConfig $queueConfig;

    private ConnectionTypeResolver $connectionTypeResolver;

    private Queue $queueClient;

    public function __construct(
        MessageQueueConfig $queueConfig,
        ConnectionTypeResolver $connectionTypeResolver,
        Queue $queueClient
    ) {
        $this->connectionTypeResolver = $connectionTypeResolver;
        $this->queueConfig = $queueConfig;
        $this->queueClient = $queueClient;
    }

    public function enqueue($topic, array $envelopes)
    {
        $queueNames = [];
        $exchanges = $this->queueConfig->getExchanges();
        foreach ($exchanges as $exchange) {
            $connection = $exchange->getConnection();
            if ($this->connectionTypeResolver->getConnectionType($connection)) {
                foreach ($exchange->getBindings() as $binding) {
                    if ($binding->getTopic() == $topic) {
                        $queueNames[] = $binding->getDestination();
                    }
                }
            }
        }
        $messages = array_map(
            function ($envelope) {
                return $envelope->getBody();
            },
            $envelopes
        );

        foreach ($queueNames as $queueName) {
            $this->addMessagesToQueue($queueName, $messages);
        }

        return null;
    }

    private function addMessagesToQueue(string $queueName, array $messages): void
    {
            foreach ($messages as $message) {
                $this->queueClient->enqueue($queueName, $message);
            }
    }
}
