<?php

namespace malkusch\bav;

use malkusch\bav\DataBackend\File\Download\Uripicker\DOMURIPicker;
use malkusch\bav\DataBackend\File\Download\Uripicker\FallbackURIPicker;
use malkusch\bav\DataBackend\File\Download\Uripicker\RegExpURIPicker;
use malkusch\bav\DataBackend\File\Download\Uripicker\URIPicker;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests URIPicker.
 *
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 */
class URIPickerTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Provides URIPicker implementations.
     *
     * @return URIPicker[][]
     */
    public static function provideURIPicker()
    {
        return array(
            array(new RegExpURIPicker()),
            array(new DOMURIPicker()),
            array(new FallbackURIPicker()),
        );
    }

    /**
     * All picker return the same result.
     *
     */
    public function testSameResults()
    {
        $lastResult = null;
        $html = file_get_contents(__DIR__ . "/../data/bankleitzahlen_download.html");
        foreach (self::provideURIPicker() as $picker) {
            $picker = $picker[0];
            $result = $picker->pickURI($html);
            if (is_null($lastResult)) {
                $lastResult = $result;
                continue;

            }

            $this->assertEquals($lastResult, $result);
        }
    }

    /**
     * Tests pickURI()
     *
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideURIPicker")]
    public function testPickURI(URIPicker $picker)
    {
        $html = file_get_contents(__DIR__ . "/../data/bankleitzahlen_download.html");
        $uri = $picker->pickURI($html);

        $this->assertEquals(
            // @codingStandardsIgnoreStart
            "/Redaktion/DE/Downloads/Aufgaben/Unbarer_Zahlungsverkehr/Bankleitzahlen/2014_09_07/blz_2014_06_09_txt.txt?__blob=publicationFile",
            // @codingStandardsIgnoreEnd
            $uri
        );
    }

    /**
     * Tests pickURI()
     *
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideURIPicker")]
    public function testFailPickURI(URIPicker $picker)
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Download\Uripicker\Exception\URIPickerException::class);

        $html = "XXX";
        $uri = $picker->pickURI($html);
    }

    /**
     * All pickers are available on this platform.
     *
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideURIPicker")]
    public function testIsAvailable(URIPicker $picker)
    {
        $this->assertTrue($picker->isAvailable());
    }
}
