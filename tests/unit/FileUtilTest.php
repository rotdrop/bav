<?php

namespace malkusch\bav;

use malkusch\bav\Configuration\DefaultConfiguration;
use malkusch\bav\Util\FileUtil;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Test FileUtil
 *
 * @see FileUtil
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 */
class FileUtilTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test only renaming in one filesystem.
     */
    public function testSafeRenameOneFS()
    {
        $before = __DIR__ . "/../data/testSafeRenameBefore";
        $after = __DIR__ . "/../data/testSafeRenameAfter";

        touch($before);

        $fileUtil = new FileUtil();
        $fileUtil->safeRename($before, $after);

        $this->assertFileExists($after);

        unlink($after);
    }

    /**
     * Tests a user configured temporary directory
     */
    public function testSetConfiguredTempDirectory()
    {
        $directory = sys_get_temp_dir();
        
        $configuration = new DefaultConfiguration();
        $configuration->setTempDirectory($directory);
        
        $fileUtil = new FileUtil($configuration);
        $this->assertEquals($directory, $fileUtil->getTempDirectory());
    }

    /**
     * Tests getting a writable directory.
     */
    public function testGetWritableTempDirectory()
    {
        $fileUtil = new FileUtil();
        $this->assertTrue(is_writable($fileUtil->getTempDirectory()));
    }

    /**
     * Tests preferring the system temporary directory.
     */
    public function testSystemTempDirectory()
    {
        $directory = sys_get_temp_dir();
        if (! is_writable($directory)) {
            $this->markTestSkipped("The system temporary directory is not writable.");
        }

        $fileUtil = new FileUtil(new DefaultConfiguration());
        $this->assertSame(realpath($directory), $fileUtil->getTempDirectory());
    }
}
