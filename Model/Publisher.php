<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface as PublisherConfig;
use Magento\Framework\MessageQueue\ExchangeRepository;
use Magento\Framework\Exception\LocalizedException;

class Publisher implements PublisherInterface
{
    /**
     * Publisher constructor.
     * @param MessageValidator $messageValidator
     * @param MessageEncoder $messageEncoder
     * @param EnvelopeFactory $envelopeFactory
     * @param PublisherConfig $publisherConfig
     * @param ExchangeRepository $exchangeRepository
     */
    public function __construct(
        private MessageValidator $messageValidator,
        private MessageEncoder $messageEncoder,
        private EnvelopeFactory $envelopeFactory,
        private PublisherConfig $publisherConfig,
        private ExchangeRepository $exchangeRepository
    ) {
    }

    /**
     * @param string $topicName
     * @param mixed $data
     * @throws LocalizedException
     */
    public function publish($topicName, $data): void
    {
        $this->messageValidator->validate($topicName, $data);
        $data = $this->messageEncoder->encode($topicName, $data);
        $envelope = $this->envelopeFactory->create([
            'body' => $data,
            'properties' => [
                'topic_name' => $topicName,
                'delivery_mode' => 2,
                'message_id' => md5(uniqid($topicName))
            ]
        ]);
        $publisher = $this->publisherConfig->getPublisher($topicName);

        $connectionName = $publisher->getConnection()->getName();
        $exchange = $this->exchangeRepository->getByConnectionName($connectionName);
        $exchange->enqueue($topicName, $envelope);
    }
}
