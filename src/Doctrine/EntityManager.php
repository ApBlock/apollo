<?php
namespace ApBlock\Apollo\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use ApBlock\Apollo\Logger\Interfaces\LoggerHelperInterface;
use ApBlock\Apollo\Logger\Traits\LoggerHelperTrait;

class EntityManager extends \Doctrine\ORM\EntityManager implements LoggerHelperInterface
{
    use LoggerHelperTrait;

    /**
     * @param Connection $conn
     * @param Configuration $config
     * @param EventManager $eventManager
     */
    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        parent::__construct($conn, $config, $eventManager);
    }
}
