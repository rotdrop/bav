<?php

namespace rotdrop\bav;

use rotdrop\bav\Bank\Bank;
use rotdrop\bav\Validator\ValidatorFactory;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests ValidatorFactory.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see ValidatorFactory
 */
class ValidatorFactoryTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     * @var ValidatorFactory
     */
    private $factory;
    
    protected function setUp(): void
    {
        $this->factory = new ValidatorFactory();
    }
    
    /**
     * Tests build()
     * 
     * @param string $type Validation type
     * @see ValidatorFactory::build()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestBuild")]
    public function testBuild($type)
    {
        $backend = $this->createStub(\rotdrop\bav\DataBackend\DataBackend::class);
        $bank = new Bank($backend, null, $type);
        
        $validator = $this->factory->build($bank);
        $this->assertInstanceOf("\\rotdrop\\bav\\Validator\\Validators\\Validator$type", $validator);
    }
    
    /**
     * Test cases for testBuild()
     * 
     * @return string[][]
     * @see testBuild()
     */
    public static function provideTestBuild()
    {
        $cases = array();
        
        $files = ClassFile::getClassFiles(__DIR__.'/../../src/Validator/Validators/');
        foreach ($files as $class) {
            if (! preg_match('~^Validator([A-Z0-9]{2})$~', $class->getName(), $matchType)) {
                continue;

            }
            $type = $matchType[1];
            $cases[] = array($type);
            
        }
        
        return $cases;
    }
}
