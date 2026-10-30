<?php

namespace rotdrop\bav;

use rotdrop\bav\Validator\ContextValidation;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests ContextValidation
 *
 * @license WTFPL
 * @see ContextValidation
 * @author Markus Malkusch <markus@malkusch.de>
 */
class ContextValidationTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var ContextValidation
     */
    private $validation;
    
    protected function setUp(): void
    {
        $bav = new BAV();
        $this->validation = new ContextValidation($bav->getDataBackend());
    }

    /**
     * Tests switching the bank context
     * 
     * @see ContextValidation::isValidBank()
     */
    public function testContextSwitch()
    {
        $this->validation->isValidBank("0");
        $this->assertTrue($this->validation->isValidAccount("0"));
        
        $this->validation->isValidBank("10000000");
        $this->assertFalse($this->validation->isValidAccount("0"));
        
        $this->validation->isValidBank("0");
        $this->assertTrue($this->validation->isValidAccount("0"));
    }
    
    /**
     * Tests isValidBank()
     * 
     * @see ContextValidation::isValidBank()
     */
    public function testValidBank()
    {
        $this->assertTrue($this->validation->isValidBank("10000000"));
        $this->assertFalse($this->validation->isValidBank("0"));
    }
    
    /**
     * Tests isValidAccount()
     * 
     * @see ContextValidation::isValidAccount()
     */
    public function testValidAccount()
    {
        // set context
        $this->validation->isValidBank("10000000");
        
        $this->assertTrue($this->validation->isValidAccount("12345"));
        $this->assertFalse($this->validation->isValidAccount("0"));
    }
    
    /**
     * Tests filter validation
     * 
     * @see ContextValidation::getValidAccountFilterCallback()
     * @see ContextValidation::getValidBankFilterCallback()
     */
    public function testFilterValidation()
    {
        // Valid bank context
        $this->assertTrue(
            filter_var(
                "10000000",
                FILTER_CALLBACK,
                $this->validation->getValidBankFilterCallback()
            )
        );
        $this->assertTrue(
            filter_var(
                "12345",
                FILTER_CALLBACK,
                $this->validation->getValidAccountFilterCallback()
            )
        );
        $this->assertFalse(
            filter_var(
                "0",
                FILTER_CALLBACK,
                $this->validation->getValidAccountFilterCallback()
            )
        );
        
        // Invalid bank context, account always valid
        $this->assertFalse(
            filter_var(
                "12345",
                FILTER_CALLBACK,
                $this->validation->getValidBankFilterCallback()
            )
        );
        $this->assertTrue(
            filter_var(
                "0",
                FILTER_CALLBACK,
                $this->validation->getValidAccountFilterCallback()
            )
        );
    }
    
    /**
     * Test an invalid context
     * 
     * @see ContextValidation::getValidAccountFilterCallback()
     */
    public function testFilterInvalidContext()
    {
        $this->expectException(\rotdrop\bav\DataBackend\Exception\InvalidContextException::class);

        filter_var(
            "0",
            FILTER_CALLBACK,
            $this->validation->getValidAccountFilterCallback()
        );
    }
    
    /**
     * Test an invalid context
     * 
     * @see ContextValidation::isValidAccount()
     */
    public function testInvalidContext()
    {
        $this->expectException(\rotdrop\bav\DataBackend\Exception\InvalidContextException::class);

        $this->validation->isValidAccount("12345");
    }
}
