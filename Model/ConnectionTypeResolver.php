<?php
declare(strict_types=1);

namespace ITDelight\RedisQueue\Model;

use Magento\Framework\MessageQueue\ConnectionTypeResolverInterface;

class ConnectionTypeResolver implements ConnectionTypeResolverInterface
{
    /**
     * DB connection names.
     *
     * @var string[]
     */
    private array $dbConnectionNames;

    /**
     * Initialize dependencies.
     *
     * @param string[] $dbConnectionNames
     */
    public function __construct(array $dbConnectionNames = [])
    {
        $this->dbConnectionNames = $dbConnectionNames;
        $this->dbConnectionNames[] = 'redis';
    }

    /**
     * @param string $connectionName
     *
     * @return string|null
     */
    public function getConnectionType($connectionName): ?string
    {
        return in_array($connectionName, $this->dbConnectionNames) ? 'redis' : null;
    }
}
