<?php

namespace rotdrop\bav;

use rotdrop\bav\DataBackend\Orm\Doctrine\DoctrineBackendContainer;
use rotdrop\bav\DataBackend\Pdo\PDODataBackend;
use rotdrop\bav\DataBackend\SQLDataBackend;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Test SQLDataBackend::getAgencies($sql)
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @licends GPL
 */
class AgencyQueryTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @return string[][]
     */
    public static function provideBackends()
    {
        return array(array('pdo'), array('doctrine'));
    }

    private function createBackend(string $type): SQLDataBackend
    {
        if ($type === 'pdo') {
            return new PDODataBackend(PDOFactory::makePDO());
        }

        $container = DoctrineBackendContainer::buildByConnection(DoctrineConnectionFactory::makeConnection(), true);
        return $container->getDataBackend();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testOnlyID(string $type)
    {
        $backend = $this->createBackend($type);
        $agencies = $backend->getAgencies(
            'SELECT id FROM bav_agency LIMIT 100'
        );
        $this->assertAgencies($agencies, 100);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testIDAndBank(string $type)
    {
        $backend = $this->createBackend($type);
        $agencies = $backend->getAgencies(
            'SELECT id, bank FROM bav_agency LIMIT 100'
        );
        $this->assertAgencies($agencies, 100);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testNoBank(string $type)
    {
        $backend = $this->createBackend($type);
        $agencies = $backend->getAgencies(
            'SELECT id, name, postcode, city, shortTerm, pan, bic FROM bav_agency LIMIT 100'
        );
        $this->assertAgencies($agencies, 100);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("provideBackends")]
    public function testNoID(string $type)
    {
        $backend = $this->createBackend($type);
        $this->expectException(\rotdrop\bav\DataBackend\Exception\MissingAttributesDataBackendIOException::class);

        $result = $backend->getAgencies(
            'SELECT name, postcode, city, shortTerm, pan, bic, bank FROM bav_agency LIMIT 1'
        );
    }

    private function assertAgencies(Array $agencies, $count)
    {
        $this->assertEquals($count, count($agencies));

        foreach ($agencies as $agency) {
            if ($agency->isMainAgency()) {
                $this->assertTrue(
                    $agency->getBank()->getMainAgency() === $agency,
                    "Inconsistent Main Agency"
                );
                $this->assertTrue(
                    $agency->getBank() === $agency->getBank()->getMainAgency()->getBank(),
                    "Inconsistent Bank"
                );

            } else {
                $includedCount = 0;
                foreach ($agency->getBank()->getAgencies() as $banksAgency) {
                    $this->assertTrue(
                        $banksAgency->getBank() === $agency->getBank(),
                        "Inconsistent bank"
                    );
                    if ($banksAgency === $agency) {
                        $includedCount++;

                    }
                }
                $this->assertEquals(1, $includedCount, "Inconsistent agency");
            }
        }
    }
}
