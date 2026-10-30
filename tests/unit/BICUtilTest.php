<?php

namespace rotdrop\bav;

use rotdrop\bav\Util\BICUtil;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests BICUtil.
 *
 * @license WTFPL
 * @see BICUtil
 * @author Markus Malkusch <markus@malkusch.de>
 */
class BICUtilTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test cases for testNormalize
     * 
     * @see testNormalize()
     */
    public static function provideTestNormalize()
    {
        return array(
            array("VZVDDED1", "VZVDDED1XXX"),
            array("VZVDDED1ABC", "VZVDDED1ABC"),
        );
    }

    /**
     * Tests BICUtil:normalize()
     * 
     * @see BICUtil::normalize()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestNormalize")]
    public function testNormalize($bic, $expected)
    {
        $this->assertEquals($expected, BICUtil::normalize($bic));
    }
}
