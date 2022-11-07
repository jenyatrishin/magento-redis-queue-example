<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model\Driver;

use Magento\Framework\MessageQueue\QueueFactoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\MessageQueue\QueueInterface;

class QueueFactory implements QueueFactoryInterface
{
    /**
     * QueueFactory constructor.
     * @param ObjectManagerInterface $objectManager
     * @param string $className
     */
    public function __construct(
        private ObjectManagerInterface $objectManager,
        private string $className = Queue::class
    ) {
    }

    /**
     * @param string $queueName
     * @param string $connectionName
     * @return QueueInterface
     */
    public function create($queueName, $connectionName): QueueInterface
    {
        return $this->objectManager->create(
            $this->className,
            [
                'queueName' => $queueName,
                'connectionName' => $connectionName
            ]
        );
    }
}
