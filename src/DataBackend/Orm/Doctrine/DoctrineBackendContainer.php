<?php

namespace malkusch\bav\DataBackend\Orm\Doctrine;

use malkusch\bav\DataBackend\DataBackend;
use malkusch\bav\DataBackend\DataBackendContainer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

/**
 * Container for DoctrineDataBackend objects.
 *
 * You will need Doctrine as composer dependency.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see DataBackend
 * @link http://www.doctrine-project.org/
 * @api
 */
class DoctrineBackendContainer extends DataBackendContainer
{
    
    private EntityManagerInterface $em;
    
    /**
     * Return the paths to the XML-Mappings
     *
     * @return string[]
     */
    public static function getXMLMappings(): array
    {
        return array(__DIR__ . "/Mapping/");
    }
    
    /**
     * Builds a container for a connection.
     *
     * @param Connection|array $connection DBAL connection or connection parameters (not a raw PDO instance)
     * @return DoctrineBackendContainer
     */
    public static function buildByConnection(Connection|array $connection, bool $isDevMode = false): self
    {
        $mappings = self::getXMLMappings();
        $config = ORMSetup::createXMLMetadataConfig($mappings, $isDevMode);
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('BAVDoctrineProxies');
            $config->setAutoGenerateProxyClasses(true);
        }

        if (is_array($connection)) {
            if (array_key_exists('pdo', $connection)) {
                throw new \InvalidArgumentException(
                    "Raw PDO connections are no longer supported; pass DBAL connection parameters or a DBAL Connection."
                );
            }
            $connection = DriverManager::getConnection($connection);
        }

        $entityManager = new EntityManager($connection, $config);
        return new self($entityManager);
    }
    
    /**
     * Injects the EntityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
    
    /**
     * Gets the EntityManager
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
    
    protected function makeDataBackend()
    {
        return new DoctrineDataBackend($this->em);
    }
}
