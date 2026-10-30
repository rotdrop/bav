<?php

namespace malkusch\bav;

use malkusch\bav\Configuration\ConfigurationRegistry;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests ConfigurationRegistryTest
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @licends GPL
 * @see ConfigurationRegistry
 */
class ConfigurationRegistryTest extends \PHPUnit\Framework\TestCase
{
    private $configuration;
    private $includePath;

    protected function setUp(): void
    {
        $this->configuration = ConfigurationRegistry::getConfiguration();
        $this->includePath = get_include_path();
    }
    
    /**
     * Disable update hook
     */
    protected function tearDown(): void
    {
        ConfigurationRegistry::setConfiguration($this->configuration);
        set_include_path($this->includePath);
    }

    /**
     * Tests the initialization with a configuration from the include path.
     * 
     * @see ConfigurationRegistry::classConstructor()
     */
    public function testInitWithIncludePathConfiguration()
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . "/../data/");
        
        ConfigurationRegistry::classConstructor();
        $configuration = ConfigurationRegistry::getConfiguration();
        
        $this->assertInstanceOf("malkusch\bav\Configuration\Configuration", $configuration);
        $this->assertEquals("test", $configuration->getTempDirectory());
        
    }
    
    /**
     * Tests the initialization with the default configuration
     * 
     * @see ConfigurationRegistry::classConstructor()
     */
    public function testInitWithDefaultConfiguration()
    {
        ConfigurationRegistry::classConstructor();
        $configuration = ConfigurationRegistry::getConfiguration();
        
        $this->assertInstanceOf("malkusch\bav\Configuration\DefaultConfiguration", $configuration);
        $this->assertNotEquals("test", $configuration->getTempDirectory());
    }
}
