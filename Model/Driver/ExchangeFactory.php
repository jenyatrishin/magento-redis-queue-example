<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model\Driver;

use Magento\Framework\MessageQueue\ExchangeFactoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\MessageQueue\ExchangeInterface;

class ExchangeFactory implements ExchangeFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var string
     */
    private string $exchangeModelClass;

    /**
     * ExchangeFactory constructor.
     * @param ObjectManagerInterface $objectManager
     * @param string $exchangeModelClass
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        string $exchangeModelClass = Exchange::class
    ) {
        $this->objectManager = $objectManager;
        $this->exchangeModelClass = $exchangeModelClass;
    }

    /**
     * @param string $connectionName
     * @param array $data
     * @return ExchangeInterface
     */
    public function create($connectionName, array $data = []): ExchangeInterface
    {
        return $this->objectManager->create($this->exchangeModelClass, $data);
    }
}
