<?php

namespace malkusch\bav;

use malkusch\bav\Bank\Agency;
use malkusch\bav\Bank\Bank;
use malkusch\bav\DataBackend\DataBackend;
use malkusch\bav\DataBackend\File\FileDataBackend;
use malkusch\bav\DataBackend\File\FileParser\FileParser;
use malkusch\bav\DataBackend\Orm\Doctrine\DoctrineBackendContainer;
use malkusch\bav\DataBackend\Orm\Doctrine\DoctrineDataBackend;
use malkusch\bav\DataBackend\Pdo\PDODataBackend;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests the Backends.
 * This test needs some memory (about 550M)!
 *
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 */
class BackendTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var FileDataBackend
     */
    private static $referenceBackend;
    
    /**
     * @var DataBackend[]
     */
    private $freeableDatabackends = array();

    /**
     * Defines the reference backend
     */
    public static function setUpBeforeClass(): void
    {
        self::$referenceBackend = new FileDataBackend();
    }
    public static function provideBackends(): array
    {
        return array(array('pdo'), array('file'), array('doctrine'));
    }

    public static function provideInstallationBackends(): array
    {
        return self::provideBackends();
    }

    private function createBackend(string $type, bool $installation = false): DataBackend
    {
        switch ($type) {
            case 'pdo':
                $backend = new PDODataBackend(PDOFactory::makePDO(), $installation ? 'bavtest_' : 'bav_');
                break;

            case 'file':
                $file = $installation ? tempnam(sys_get_temp_dir(), 'bavtest') : null;
                $backend = new FileDataBackend($file);
                break;

            case 'doctrine':
                $connection = $installation
                    ? array('driver' => 'pdo_sqlite', 'memory' => true)
                    : array('pdo' => PDOFactory::makePDO());
                $container = DoctrineBackendContainer::buildByConnection($connection, true);
                $backend = new DoctrineDataBackend($container->getEntityManager());
                break;

            default:
                throw new \InvalidArgumentException("Unknown backend: $type");
        }

        $this->freeableDatabackends[] = $backend;
        if ($installation) {
            if ($backend->isInstalled()) {
                $backend->uninstall();
            }
        } elseif (! $backend->isInstalled()) {
            $backend->install();
        }

        return $backend;
    }

    protected function tearDown(): void
    {
        // Reduce memory foot print
        foreach ($this->freeableDatabackends as $backend) {
            $backend->free();
            
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideInstallationBackends")]
    public function testInstallation(string $backendType)
    {
        $backend = $this->createBackend($backendType, true);
        $backend->install();
        $backend->update();
        $backend->uninstall();
        $this->assertFalse($backend->isInstalled());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideInstallationBackends")]
    public function testIsInstalled(string $backendType)
    {
        $backend = $this->createBackend($backendType, true);
        $backend->install();
        $this->assertTrue($backend->isInstalled());

        $backend->uninstall();
        $this->assertFalse($backend->isInstalled());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideInstallationBackends")]
    public function testLastUpdate(string $backendType)
    {
        $backend = $this->createBackend($backendType, true);
        $now = time();
        $backend->install();
        $update = $backend->getLastUpdate();
        $this->assertGreaterThanOrEqual($now, $update);

        $now = time();
        $backend->update();
        $update = $backend->getLastUpdate();
        $this->assertGreaterThanOrEqual($now, $update);

        $backend->uninstall();
    }

    /**
     * Tests that the backend provides all banks and agencies
     * 
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testInstallationIsComplete(string $backendType)
    {
        $backend = $this->createBackend($backendType);
        $agencies = array();
        
        foreach ($this->provideBanks() as $referenceBank) {
            $bank = $backend->getBank($referenceBank->getBankID());
            
            $this->assertEquals(
                $referenceBank->getValidationType(),
                $bank->getValidationType()
            );
            
            $agencies[$bank->getMainAgency()->getID()] = $bank->getMainAgency();
            foreach ($bank->getAgencies() as $agency) {
                $agencies[$agency->getID()] = $agency;
                
            }
        }
        
        $expectedAgencies = $this->provideAgencies();
        
        $this->assertEquals(count($expectedAgencies), count($agencies));
        
        foreach ($agencies as $id => $agency) {
            $expectedAgency = $expectedAgencies[$id];
            $this->assertEqualAgency($expectedAgency, $agency);
            
        }
    }
    
    /**
     * Read all banks from the bundesbank file.
     * 
     * @return Bank[]
     */
    private function provideBanks()
    {
        $parser = new FileParser();
        $databackend = new FileDataBackend($parser->getFile());
        $banks = array();
        
        for ($line = 0; $line < $parser->getLines(); $line++) {
            $data = $parser->readLine($line);
            $bank = $parser->getBank($databackend, $data);
            $banks[$bank->getBankID()] = $bank;
            
        }
        
        return $banks;
    }
    
    /**
     * Read all agencies from the bundesbank file.
     * 
     * @return Agency[]
     */
    private function provideAgencies()
    {
        $parser = new FileParser();
        $databackend = new FileDataBackend($parser->getFile());
        $agencies = array();
        
        for ($line = 0; $line < $parser->getLines(); $line++) {
            $data = $parser->readLine($line);
            $bank = $parser->getBank($databackend, $data);
            $agency = $parser->getAgency($bank, $data);
            $agencies[$agency->getID()] = $agency;
            
        }
        
        return $agencies;
    }

    /**
     * Testet, dass ein erneutes $backend->getBank($id) das selbe
     * Objekt zurückliefert.
     *
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testSingleInstances(string $backendType)
    {
        $backend = $this->createBackend($backendType);
        foreach (self::$referenceBackend->getAllBanks() as $refBank) {
            $this->assertTrue(
                $backend->getBank($refBank->getBankID()) === $backend->getBank($refBank->getBankID()),
                "Different objects for bank {$refBank->getBankID()}"
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testGetAllBanks(string $backendType)
    {
        $backend = $this->createBackend($backendType);
        $this->assertEquals(
            count(self::$referenceBackend->getAllBanks()),
            count($backend->getAllBanks())
        );
    }

    /**
     * Test loading all banks
     * 
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testBanks(string $backendType)
    {
        $backend = $this->createBackend($backendType);
        foreach (self::$referenceBackend->getAllBanks() as $referenceBank) {
            $testedBank = $backend->getBank($referenceBank->getBankID());

            $this->assertEquals(
                $referenceBank->getValidationType(),
                $testedBank->getValidationType()
            );

            $this->assertEqualAgency(
                $referenceBank->getMainAgency(),
                $testedBank->getMainAgency()
            );

            $this->assertEquals(
                count($referenceBank->getAgencies()),
                count($testedBank->getAgencies())
            );
            
        }
    }

    /**
     * Tests loading all agencies
     * 
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testAgencies(string $backendType)
    {
        $backend = $this->createBackend($backendType);
        foreach (self::$referenceBackend->getAllBanks() as $referenceBank) {
            $testedBank = $backend->getBank($referenceBank->getBankID());
            
            $referenceAgencies = array();
            foreach ($referenceBank->getAgencies() as $agency) {
                $referenceAgencies[$agency->getID()] = $agency;

            }

            foreach ($testedBank->getAgencies() as $agency) {
                $referenceAgency = $referenceAgencies[$agency->getID()];
                
                $this->assertEqualAgency($referenceAgency, $agency);

            }
        }
    }

    private function assertEqualAgency(Agency $a, Agency $b)
    {
        $this->assertTrue($a->getBank()->getBankID() === $b->getBank()->getBankID());
        $this->assertTrue($a->getID()                === $b->getID());
        $this->assertTrue($a->getPostcode()          === $b->getPostcode());
        $this->assertTrue($a->getCity()              === $b->getCity());
        $this->assertTrue($a->getName()              === $b->getName());
        $this->assertTrue($a->getShortTerm()         === $b->getShortTerm());
        $this->assertTrue($a->hasPAN()               === $b->hasPAN());
        $this->assertTrue($a->hasBIC()               === $b->hasBIC());
        if ($a->hasPAN()) {
            $this->assertTrue($a->getPAN() === $b->getPAN());

        }
        if ($a->hasBIC()) {
            $this->assertTrue($a->getBIC() === $b->getBIC());

        }
    }

    /**
     * Test cases for testIsValidBIC()
     * 
     * @see testIsValidBIC()
     * @return array
     */
    public static function provideTestIsValidBIC()
    {
        $cases = array();
        foreach (self::provideBackends() as $backendArray) {
            $backend = $backendArray[0];
            $cases[] = array($backend, true, "VZVDDED1XXX");
            $cases[] = array($backend, true, "VZVDDED1001");
            $cases[] = array($backend, false, "VZVDDED1001X");
            $cases[] = array($backend, false, "");
            $cases[] = array($backend, false, "VZVDDED1~~~");

        }
        return $cases;
    }

    /**
     * Tests DataBackend::isValidBIC();
     *
     * @see DataBackend::isValidBIC();
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestIsValidBIC")]
    public function testIsValidBIC(string $backendType, $expected, $bic)
    {
        $backend = $this->createBackend($backendType);
        $this->assertEquals($expected, $backend->isValidBIC($bic));
    }

    /**
     * Test cases for testGetBICAgencies()
     * 
     * @see testGetBICAgencies()
     */
    public static function provideTestGetBICAgencies()
    {
        $cases = array();
        foreach (self::provideBackends() as $backendArray) {
            $backend = $backendArray[0];
            $cases[] = array($backend, "XXX", array());
            $cases[] = array($backend, "VZVDDED1XXX", array("52944"));
            $cases[] = array($backend, "VZVDDED1XXX", array("52944"));
            $cases[] = array($backend, "DELBDE33XXX", array("8536", "13567", "33248", "51683"));

        }
        return $cases;
    }

    /**
     * Tests DataBackend::getBICAgencies()
     * 
     * @see DataBackend::getBICAgencies();
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestGetBICAgencies")]
    public function testGetBICAgencies(string $backendType, $bic, $expectedAgencyIds)
    {
        $backend = $this->createBackend($backendType);
        $agencies = $backend->getBICAgencies($bic);
        $getID = function (Agency $agency) {
            return $agency->getID();
        };
        $agenciesIds = array_map($getID, $agencies);
        
        sort($expectedAgencyIds);
        sort($agenciesIds);
        $this->assertEquals($expectedAgencyIds, $agenciesIds);
    }
}
