<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model\Client;

use Credis_Client;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\LocalizedException;

class Queue
{
    private const NEXT_U_ID = 'nextUId';
    private const ACKNOWLEDGE = 'acknowledge';

    private Credis_Client $client;

    private string $prefix = 'jr';

    public function __construct(DeploymentConfig $deploymentConfig, string $prefix = 'jr')
    {
        $config = $deploymentConfig->getConfigData('queue');
        if (empty($config)) {
            throw new LocalizedException(__('You should set queue config'));
        }
        $this->prefix = $prefix;
        $this->client = new Credis_Client(
            $config['redis']['host'],
            $config['redis']['port'],
            $config['redis']['timeout'] ?? null,
            $config['redis']['persistent'] ?? '',
            $config['redis']['db'],
        );
    }

    public function createQueue(string $name): bool
    {
        $this->validateQueue($name);

        $queueName = $this->createQueueName($name);

        if ($this->isQueueExists($queueName)) {
            throw new LocalizedException(__('Queue with name %1 already exists', $name));
        }
        $this->client->hSetNx($queueName, 'created', strval((new \DateTimeImmutable())->getTimestamp()));
        $this->client->hSetNx($queueName, self::ACKNOWLEDGE, '0');
        $this->client->hSetNx($queueName, self::NEXT_U_ID, '1');

        $this->client->sAdd("{$this->prefix}QUEUES", [$queueName]);

        return true;
    }

    public function enqueue(string $queueName, string $message): string
    {
        $queueName = $this->createQueueName($queueName);
        $id = $this->client->hGet($queueName, self::NEXT_U_ID);
        $this->client->hSet($queueName, $id, $message);
        $id = $id + 1;
        $this->client->hSet($queueName, self::NEXT_U_ID, (string)$id);
        //TODO: add realtime mode behavior

        return (string)$id;
    }

    public function dequeue(string $queueName): string
    {
        $queueName = $this->createQueueName($queueName);
        $id = current($this->client->hKeys($queueName));
        if ($id === 'nextUId') {
            return '';
        }
        $message = $this->client->hGet($queueName, $id);
        $this->client->hDel($queueName, $id);

        return (string)$message;
    }

    public function deleteQueue(string $name): bool
    {
        $queueName = $this->createQueueName($name);

        $this->client->hDel($queueName, 'created');
        $this->client->sRem("{$this->prefix}QUEUES", $queueName);

        return true;
    }

    public function queueList(): array
    {
        return $this->client->sMembers("{$this->prefix}QUEUES");
    }

    public function acknowledge(string $name): int
    {
        $queueName = $this->createQueueName($name);
        $count = $this->client->hGet($queueName, self::ACKNOWLEDGE);
        $count++;
        $this->client->hSet($queueName, self::ACKNOWLEDGE, (string)$count);

        return $count;
    }

    private function validateQueue(string $queueName): void
    {

    }

    private function createQueueName(string $name): string
    {
        return "{$this->prefix}$name:Q";
    }

    private function isQueueExists(string $queueName): bool
    {
        return (bool)$this->client->sIsMember("{$this->prefix}QUEUES", $queueName);
    }
}
