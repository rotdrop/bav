<?php

namespace rotdrop\bav\DataBackend\Orm\Doctrine;

use rotdrop\bav\Bank\Agency;
use rotdrop\bav\Bank\Bank;
use rotdrop\bav\DataBackend\Exception\BankNotFoundException;
use rotdrop\bav\DataBackend\Exception\DataBackendException;
use rotdrop\bav\DataBackend\Exception\DataBackendIOException;
use rotdrop\bav\DataBackend\Exception\MissingAttributesDataBackendIOException;
use rotdrop\bav\DataBackend\Exception\NoMainAgencyException;
use rotdrop\bav\DataBackend\File\FileDataBackend;
use rotdrop\bav\DataBackend\Orm\MetaData;
use rotdrop\bav\DataBackend\SQLDataBackend;
use rotdrop\bav\Util\FileUtil;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Use Doctrine ORM as backend.
 *
 * You will need Doctrine as composer dependency.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @link http://www.doctrine-project.org/
 */
class DoctrineDataBackend extends SQLDataBackend
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    protected function getNewBank($bankID)
    {
        try {
            $bank = $this->em->find(Bank::class, $bankID);
            if ($bank == null) {
                throw new BankNotFoundException($bankID);

            }
            $bank->setDataBackend($this);
            return $bank;
            
        } catch (ORMException | DBALException $e) {
            throw new DataBackendException($e->getMessage(), $e->getCode(), $e);
            
        }
    }

    public function getAgenciesForBank(Bank $bank)
    {
        $query = $this->em->createQuery(
            "select agency from rotdrop\bav\Bank\Agency agency where agency.id != :mainAgency and agency.bank=:bank"
        );
        $query->setParameters(array(
            "mainAgency" => $bank->getMainAgency()->getID(),
            "bank" => $bank
        ));
        return $query->getResult();
    }

    public function getAllBanks()
    {
        $banks = $this->em->getRepository(Bank::class)->findAll();
        foreach ($banks as $bank) {
            $bank->setDataBackend($this);
        }
        return $banks;
    }

    public function getBICAgencies($bic)
    {
        $agencies = $this->em->getRepository(Agency::class)->findBy(array(
            "bic" => $bic
        ));
        foreach ($agencies as $agency) {
            $agency->getBank()->setDataBackend($this);
        }
        return $agencies;
    }

    public function getLastUpdate()
    {
        $lastModified = $this->em->find(MetaData::class, MetaData::LASTMODIFIED);
        if ($lastModified == null) {
            throw new DataBackendException();
            
        }
        return (int) $lastModified->getValue();
    }

    public function getMainAgency(Bank $bank)
    {
        // Return the Doctrine proxy
        return $bank->getMainAgency();
    }
    
    private function getClassesMetadata()
    {
        return array(
            $this->em->getClassMetadata(Bank::class),
            $this->em->getClassMetadata(Agency::class),
            $this->em->getClassMetadata(MetaData::class),
        );
    }

    public function install()
    {
        $tool = new SchemaTool($this->em);
        $classes = $this->getClassesMetadata();
        $tool->createSchema($classes);
        
        $this->update();
    }

    public function isInstalled()
    {
        try {
            $this->em->getConnection()->executeQuery("SELECT name FROM bav_meta LIMIT 1")->free();
            return true;
            
        } catch (TableNotFoundException $e) {
            return false;
            
        }
    }

    public function uninstall()
    {
        $tool = new SchemaTool($this->em);
        $classes = $this->getClassesMetadata();
        $tool->dropSchema($classes);
        $this->free();
    }

    public function update()
    {
        $fileBackend = $this->createFileBackend();
        try {
            $fileBackend->install();
            $this->free();
            $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($fileBackend) {
                $em->createQuery("DELETE FROM rotdrop\bav\Bank\Agency")->execute();
                $em->createQuery("DELETE FROM rotdrop\bav\Bank\Bank")->execute();

                foreach ($fileBackend->getAllBanks() as $bank) {
                    try {
                        $agencies = $bank->getAgencies();
                        $agencies[] = $bank->getMainAgency();
                        $em->persist($bank);
                        foreach ($agencies as $agency) {
                            $em->persist($agency);
                        }
                    } catch (NoMainAgencyException $e) {
                        trigger_error(
                            "Skipping bank {$e->getBank()->getBankID()} without any main agency."
                        );
                    }
                }

                $lastModified = $em->find(MetaData::class, MetaData::LASTMODIFIED);
                if ($lastModified == null) {
                    $lastModified = new MetaData();
                }
                $lastModified->setName(MetaData::LASTMODIFIED);
                $lastModified->setValue(time());
                $em->persist($lastModified);
            });
            $this->free();
        } finally {
            $fileBackend->free();
            $fileBackend->uninstall();
        }
    }

    protected function createFileBackend(): FileDataBackend
    {
        $fileUtil = new FileUtil();
        $file = tempnam($fileUtil->getTempDirectory(), 'bav');
        if ($file === false) {
            throw new DataBackendIOException("Could not create a temporary bank file.");
        }
        return new FileDataBackend($file);
    }

    public function free()
    {
        parent::free();
        $this->em->clear();
    }
    
    /**
     * You may use an arbitrary SQL statement to receive Agency objects.
     * Your statement should at least return the id of the agencies.
     *
     * @param string $sql
     * @throws MissingAttributesDataBackendIOException
     * @throws DataBackendIOException
     * @throws DataBackendException
     * @return Agency[]
     */
    public function getAgencies($sql)
    {
        $agencies = array();
        $backend = $this;
        $em = $this->em;
        $this->em->getConnection()->transactional(function () use (&$agencies, $sql, $backend, $em) {
            $stmt = $em->getConnection()->executeQuery($sql);

            try {
                foreach ($stmt->iterateAssociative() as $result) {
                    if (! array_key_exists('id', $result)) {
                        throw new MissingAttributesDataBackendIOException();
                    }
                    $id = $result["id"];
                    $agency = $em->find(Agency::class, $id);
                    if ($agency === null) {
                        throw new DataBackendIOException("Agency $id does not exist.");
                    }
                    $agencies[] = $agency;

                    $agency->getBank()->setDataBackend($backend);
                }
            } finally {
                $stmt->free();
            }
        });
        return $agencies;
    }
}
