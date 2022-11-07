<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Setup;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\MessageQueue\Topology\ConfigInterface as MessageQueueConfig;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use ITDelight\RedisQueue\Model\Client\Queue;
use Psr\Log\LoggerInterface;

class Recurring implements InstallSchemaInterface
{
    /**
     * Recurring constructor.
     * @param MessageQueueConfig $messageQueueConfig
     * @param Queue $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        private MessageQueueConfig $messageQueueConfig,
        private Queue $client,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $queues = [];
        foreach ($this->messageQueueConfig->getQueues() as $queue) {
            $queues[] = $queue->getName();
        }

        foreach ($queues as $queue) {
            try {
                $this->client->createQueue($queue);
            } catch (LocalizedException $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }
}
