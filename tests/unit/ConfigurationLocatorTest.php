<?php

namespace rotdrop\bav;

use rotdrop\bav\Configuration\ConfigurationLocator;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests ConfigurationLocator
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @licends GPL
 * @see ConfigurationLocator
 */
class ConfigurationLocatorTest extends \PHPUnit\Framework\TestCase
{
    private $includePath;

    protected function setUp(): void
    {
        $this->includePath = get_include_path();
    }

    protected function tearDown(): void
    {
        set_include_path($this->includePath);
    }
    
    /**
     * locate() should return null
     * 
     * @see ConfigurationLocator::locate();
     */
    public function testlocateReturnsNull()
    {
        $locator = new ConfigurationLocator(array(__DIR__ . "/noop.php"));
        $this->assertNull($locator->locate());
    }
    
    /**
     * locate() should throw an exception
     * 
     * @see ConfigurationLocator::locate();
     */
    public function testlocateThrowsException()
    {
        $this->expectException(\rotdrop\bav\Configuration\ConfigurationException::class);

        $locator = new ConfigurationLocator(array(
            __DIR__ . "/../data/no_configuration.php"
        ));
        $locator->locate();
    }
    
    /**
     * locate() should find absolute paths
     * 
     * @param string[] $paths
     * @see ConfigurationLocator::locate();
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestLocateFindsAbsolutePath")]
    public function testLocateFindsAbsolutePath(array $paths)
    {
        $locator = new ConfigurationLocator($paths);
        $configuration = $locator->locate();
        
        $this->assertInstanceOf("rotdrop\bav\Configuration\Configuration", $configuration);
        $this->assertEquals("test", $configuration->getTempDirectory());
    }
    
    /**
     * Test cases for testLocateFindsAbsolutePath().
     * 
     * @return string[][][]
     * @see testLocateFindsAbsolutePath()
     */
    public static function provideTestLocateFindsAbsolutePath()
    {
        $existingPath = __DIR__ . "/../data/configuration.php";
        $notExistingPath = __DIR__ . "/../data/noop.php";
        
        return array(
            array(array($existingPath)),
            array(array($existingPath, $notExistingPath)),
            array(array($notExistingPath, $existingPath)),
        );
    }
    
    /**
     * locate() should find paths from the include path
     * 
     * @param string[] $paths
     * @see ConfigurationLocator::locate();
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestLocateFindsIncludePath")]
    public function testLocateFindsIncludePath($includePath, array $paths)
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . $includePath);
        
        $locator = new ConfigurationLocator($paths);
        $configuration = $locator->locate();
        
        $this->assertInstanceOf("rotdrop\bav\Configuration\Configuration", $configuration);
        $this->assertEquals("test", $configuration->getTempDirectory());
        
    }
    
    /**
     * Test cases for testLocateFindsIncludePath().
     * 
     * @return string[][][]
     * @see testLocateFindsIncludePath()
     */
    public static function provideTestLocateFindsIncludePath()
    {
        $existingPath = "configuration.php";
        $notExistingPath = "noop.php";
        $includePath = __DIR__ . "/../data";
        
        return array(
            array($includePath, array($existingPath)),
            array($includePath, array($existingPath, $notExistingPath)),
            array($includePath, array($notExistingPath, $existingPath)),
        );
    }
}
