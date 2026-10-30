<?php

namespace rotdrop\bav;

use rotdrop\bav\DataBackend\DataBackend;
use rotdrop\bav\DataBackend\File\FileDataBackend;
use rotdrop\bav\DataBackend\Update\AutomaticUpdatePlan;
use rotdrop\bav\DataBackend\Update\LogUpdatePlan;
use rotdrop\bav\DataBackend\Update\UpdatePlan;
use rotdrop\bav\Util\FileUtil;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests UpdatePlan
 *
 * @see TimeMock
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 */
class UpdatePlanTest extends \PHPUnit\Framework\TestCase
{
    use UserErrorAssertions;

    private $file;

    protected function tearDown(): void
    {
        TimeMock::disable();
        if ($this->file !== null) {
            unlink($this->file);
        }
    }

    /**
     * Provide test cases for testIsOutdated()
     * 
     * @see testIsOutdated()
     */
    public static function provideTestIsOutdated()
    {
        return array(
            array("2012-11-01", "2013-01-31", false),
            array("2012-10-31", "2013-01-31", true),
            array("2013-02-01", "2013-04-01", false),
            array("2013-01-31", "2013-04-01", true),
            array("2013-02-01", "2013-02-01", false),
            array("2013-01-31", "2013-02-01", true),
            array("2013-02-01", "2013-02-31", false),
            array("2013-01-31", "2013-02-31", true),
            array("2013-02-01", "2013-03-01", false),
            array("2013-01-31", "2013-03-01", true),
            array("2013-02-01", "2013-03-31", false),
            array("2013-01-31", "2013-03-31", true),
        );
    }

    /**
     * Tests isOutdated()
     * 
     * @param string $mtime file time
     * @param string $time current time
     * @param bool $expectedIsOutdated
     * @see UpdatePlan::isOutdated()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestIsOutdated")]
    public function testIsOutdated($mtime, $time, $expectedIsOutdated)
    {
        $fileUtil = new FileUtil();
        $file = $this->file = tempnam($fileUtil->getTempDirectory(), 'bavtest');
        touch($file, strtotime($mtime));
        $backend = new FileDataBackend($file);

        TimeMock::setTime(strtotime($time));

        $updatePlan = new AutomaticUpdatePlan();
        $this->assertEquals($expectedIsOutdated, $updatePlan->isOutdated($backend));

        TimeMock::disable();
    }

    public function testLogUpdatePlan()
    {
        $updatePlan = new LogUpdatePlan();
        $backend = $this->createStub(DataBackend::class);
        $this->assertUserError(
            E_USER_WARNING,
            "bav's bank data is outdated. Update the data with e.g. bin/bav-update.php",
            function () use ($updatePlan, $backend) {
                $updatePlan->perform($backend);
            }
        );
    }

    public function testAutomaticUpdatePlanNotice()
    {
        $backend = $this->createMock(DataBackend::class);
        $backend->expects($this->once())->method('update');
        $updatePlan = new AutomaticUpdatePlan();
        $this->assertUserError(
            E_USER_NOTICE,
            "bav's bank data was updated sucessfully.",
            function () use ($updatePlan, $backend) {
                $updatePlan->perform($backend);
            }
        );
    }

    public function testAutomaticUpdatePlan()
    {
        $fileUtil = new FileUtil();
        $file = $this->file = tempnam($fileUtil->getTempDirectory(), 'bavtest');
        touch($file, strtotime("-1 year"));
        $backend = $this->getMockBuilder(FileDataBackend::class)
            ->setConstructorArgs(array($file))
            ->onlyMethods(array('update'))
            ->getMock();
        $backend->expects($this->once())->method('update')->willReturnCallback(function () use ($file) {
            touch($file);
            clearstatcache(true, $file);
        });

        $updatePlan = new AutomaticUpdatePlan();
        $this->assertTrue($updatePlan->isOutdated($backend));

        $this->assertUserError(
            E_USER_NOTICE,
            "bav's bank data was updated sucessfully.",
            function () use ($updatePlan, $backend) {
                $updatePlan->perform($backend);
            }
        );
        $this->assertFalse($updatePlan->isOutdated($backend));
    }
}
