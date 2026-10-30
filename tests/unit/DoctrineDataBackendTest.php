<?php

namespace rotdrop\bav;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use rotdrop\bav\DataBackend\Exception\BankNotFoundException;
use rotdrop\bav\DataBackend\Exception\DataBackendException;
use rotdrop\bav\DataBackend\Exception\DataBackendIOException;
use rotdrop\bav\DataBackend\Exception\MissingAttributesDataBackendIOException;
use rotdrop\bav\DataBackend\File\FileDataBackend;
use rotdrop\bav\DataBackend\Orm\Doctrine\DoctrineBackendContainer;
use rotdrop\bav\DataBackend\Orm\Doctrine\DoctrineDataBackend;
use rotdrop\bav\DataBackend\Orm\MetaData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DoctrineDataBackendTest extends TestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;
    private DoctrineDataBackend $backend;
    private array $temporaryFiles = array();
    private bool $failImport = false;
    private bool $failDownload = false;

    protected function setUp(): void
    {
        $container = DoctrineBackendContainer::buildByConnection(
            array('driver' => 'pdo_sqlite', 'memory' => true),
            true
        );
        $this->em = $container->getEntityManager();
        $this->connection = $this->em->getConnection();
        $this->connection->executeStatement('PRAGMA foreign_keys = ON');
        $this->backend = $this->getMockBuilder(DoctrineDataBackend::class)
            ->setConstructorArgs(array($this->em))
            ->onlyMethods(array('createFileBackend'))
            ->getMock();
        $this->backend->expects($this->atLeastOnce())->method('createFileBackend')->willReturnCallback(function () {
            $file = tempnam(sys_get_temp_dir(), 'bavtest');
            if ($file === false) {
                throw new \RuntimeException('Could not create a temporary fixture.');
            }
            $this->temporaryFiles[] = $file;
            if (! copy(__DIR__ . '/../data/fileParserTest/bb-excerpt.txt', $file)) {
                throw new \RuntimeException('Could not copy the bank fixture.');
            }
            $methods = $this->failImport ? array('install', 'getAllBanks') : array('install');
            $source = $this->getMockBuilder(FileDataBackend::class)
                ->setConstructorArgs(array($file))
                ->onlyMethods($methods)
                ->getMock();
            if ($this->failDownload) {
                $source->expects($this->once())->method('install')
                    ->willThrowException(new \RuntimeException('Download failed.'));
            } else {
                $source->expects($this->once())->method('install');
            }
            if ($this->failImport) {
                $source->method('getAllBanks')->willThrowException(new \RuntimeException('Import failed.'));
            }
            return $source;
        });
        $this->assertFalse($this->backend->isInstalled());
        $this->backend->install();
    }

    protected function tearDown(): void
    {
        $this->backend->free();
        $this->connection->close();
        foreach ($this->temporaryFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testMappingsAndSchema(): void
    {
        $validator = new SchemaValidator($this->em);
        $this->assertSame(array(), $validator->validateMapping());
        $this->assertTrue($this->connection->createSchemaManager()->tablesExist(array(
            'bav_bank', 'bav_agency', 'bav_meta',
        )));
    }

    public static function provideModes(): array
    {
        return array(array(false), array(true));
    }

    #[DataProvider('provideModes')]
    public function testBuildByConnection(bool $isDevMode): void
    {
        $container = DoctrineBackendContainer::buildByConnection($this->connection, $isDevMode);
        $this->assertSame($this->connection, $container->getEntityManager()->getConnection());
        $backend = new DoctrineDataBackend($container->getEntityManager());
        $this->assertTrue($backend->isInstalled());
        $bank = $backend->getBank('10020890');
        $this->assertSame(39785, $bank->getMainAgency()->getID());
        $this->assertCount(2, $bank->getAgencies());
    }

    public function testRawPDOParametersAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DoctrineBackendContainer::buildByConnection(array('pdo' => new \PDO('sqlite::memory:')), true);
    }

    public function testInstallationAndRepeatedUpdate(): void
    {
        $this->assertTrue($this->backend->isInstalled());
        $bank = $this->backend->getBank('10020890');
        $this->assertSame($bank, $this->backend->getBank('10020890'));
        $before = time();
        $this->backend->update();
        $this->assertGreaterThanOrEqual($before, $this->backend->getLastUpdate());
        $this->assertIsInt($this->backend->getLastUpdate());
        $this->assertNotSame($bank, $this->backend->getBank('10020890'));
        $this->assertSame(8, count($this->backend->getAllBanks()));
        $this->assertSame(10, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM bav_agency'));
        $this->backend->uninstall();
        $this->assertFalse($this->backend->isInstalled());
        $this->backend->install();
        $this->assertTrue($this->backend->isInstalled());
        foreach ($this->temporaryFiles as $file) {
            $this->assertFileDoesNotExist($file);
        }
    }

    public function testBankAndAgencyIdentity(): void
    {
        $bank = $this->backend->getBank('10020890');
        $mainAgency = $bank->getMainAgency();
        $this->assertSame(39785, $mainAgency->getID());
        $this->assertSame($bank, $mainAgency->getBank());
        $this->assertTrue($mainAgency->isMainAgency());
        $agencies = $bank->getAgencies();
        $this->assertCount(2, $agencies);
        foreach ($agencies as $agency) {
            $this->assertSame($bank, $agency->getBank());
            $this->assertFalse($agency->isMainAgency());
        }
    }

    public function testAllBanksAreAttachedToBackend(): void
    {
        $banks = $this->backend->getAllBanks();
        $this->assertCount(8, $banks);
        foreach ($banks as $bank) {
            $this->assertSame($bank, $this->backend->getBank($bank->getBankID()));
            $this->assertIsArray($bank->getAgencies());
        }
    }

    public function testBICQueriesAttachBanks(): void
    {
        $agencies = $this->backend->getBICAgencies('HYVEDEMM488');
        $this->assertCount(3, $agencies);
        $bank = $agencies[0]->getBank();
        $this->assertCount(2, $bank->getAgencies());
        foreach ($agencies as $agency) {
            $this->assertSame($bank, $agency->getBank());
            $this->assertSame('HYVEDEMM488', $agency->getBIC());
        }
        $this->assertSame(array(), $this->backend->getBICAgencies('UNKNOWN'));
    }

    public function testSQLQueriesDoNotFlushPendingChanges(): void
    {
        $metadata = $this->em->find(MetaData::class, MetaData::LASTMODIFIED);
        $metadata->setValue('123');
        $agencies = $this->backend->getAgencies('SELECT id FROM bav_agency WHERE bank = 10020890');
        $this->assertCount(3, $agencies);
        $bank = $agencies[0]->getBank();
        $this->assertCount(2, $bank->getAgencies());
        foreach ($agencies as $agency) {
            $this->assertSame($bank, $agency->getBank());
            $this->assertSame($bank, $this->backend->getBank($bank->getBankID()));
        }
        $this->assertNotEquals('123', $this->connection->fetchOne('SELECT value FROM bav_meta'));
    }

    public function testMissingSQLIdentifierDoesNotCloseEntityManager(): void
    {
        try {
            $this->backend->getAgencies('SELECT name FROM bav_agency LIMIT 1');
            $this->fail('A missing agency identifier must be rejected.');
        } catch (MissingAttributesDataBackendIOException $e) {
            $this->assertTrue($this->em->isOpen());
            $this->assertFalse($this->connection->isTransactionActive());
            $this->assertCount(1, $this->backend->getAgencies('SELECT id FROM bav_agency LIMIT 1'));
        }
    }

    public function testUnknownSQLIdentifier(): void
    {
        $this->expectException(DataBackendIOException::class);
        $this->backend->getAgencies('SELECT -1 AS id');
    }

    public function testUnknownBank(): void
    {
        $this->expectException(BankNotFoundException::class);
        $this->backend->getBank('99999999');
    }

    public function testDatabaseFailureIsWrapped(): void
    {
        $this->backend->uninstall();
        try {
            $this->backend->getBank('10020890');
            $this->fail('Reading an uninstalled backend must fail.');
        } catch (DataBackendException $e) {
            $this->assertInstanceOf(DBALException::class, $e->getPrevious());
        }
    }

    public function testInstallationCheckDoesNotHideConnectionErrors(): void
    {
        $container = DoctrineBackendContainer::buildByConnection(array(
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/does-not-exist/database.sqlite',
        ), true);
        $backend = new DoctrineDataBackend($container->getEntityManager());
        $this->expectException(DBALException::class);
        $backend->isInstalled();
    }

    public function testFailedImportRollsBackAndRemovesTemporaryFile(): void
    {
        $this->failImport = true;
        try {
            $this->backend->update();
            $this->fail('The failed import must propagate its error.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Import failed.', $e->getMessage());
            $this->assertFalse($this->em->isOpen());
            $this->assertFalse($this->connection->isTransactionActive());
            $this->assertSame(8, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM bav_bank'));
            $this->assertSame(10, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM bav_agency'));
            $this->assertFileDoesNotExist(end($this->temporaryFiles));
        }
    }

    public function testFailedDownloadDoesNotStartTransaction(): void
    {
        $this->failDownload = true;
        try {
            $this->backend->update();
            $this->fail('The failed download must propagate its error.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Download failed.', $e->getMessage());
            $this->assertTrue($this->em->isOpen());
            $this->assertFalse($this->connection->isTransactionActive());
            $this->assertCount(8, $this->backend->getAllBanks());
            $this->assertFileDoesNotExist(end($this->temporaryFiles));
        }
    }
}
