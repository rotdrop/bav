<?php

namespace rotdrop\bav\DataBackend\Orm;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for storing metadata for an ORM backend.
 *
 * @internal
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
#[ORM\Entity]
#[ORM\Table(name: 'bav_meta')]
class MetaData
{
    
    const LASTMODIFIED = "lastModified";
    
    /**
     * @var String
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private $name;
    
    /**
     * @var String
     */
    #[ORM\Column(type: 'string', length: 128)]
    private $value;
    
    /**
     * Sets the name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * Gets the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
    
    /**
     * Gets the value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
