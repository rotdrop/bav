<?php

namespace malkusch\bav;

use malkusch\bav\DataBackend\File\Download\Downloader;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests Downloader.
 *
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 */
class DownloaderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Returns URIs and the expected md5 sum of their contents.
     *
     * @return String[][]
     */
    public static function provideTestURIs()
    {
        return array(
            array("http://www.gnu.org/licenses/gpl-2.0.txt", "b234ee4d69f5fce4486a80fdaf4a4263")
        );
    }

    /**
     * Tests downloadContent();
     * 
     * @param string $uri URI
     * @param string $md5 expected MD5 sum
     * @see Downloader::downloadContent()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestURIs")]
    public function testDownloadContent($uri, $md5)
    {
        $downloader = new Downloader();
        $content = $downloader->downloadContent($uri);
        $this->assertEquals($md5, md5($content));
    }

    /**
     * Tests downloadFile();
     * 
     * @param string $uri URI
     * @param string $md5 expected MD5 sum
     * @see Downloader::downloadFile()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestURIs")]
    public function testDownloadFile($uri, $md5)
    {
        $downloader = new Downloader();
        $file = $downloader->downloadFile($uri);
        $this->assertEquals($md5, md5_file($file));
        unlink($file);
    }

    /**
     * Tests a sequence of downloadContent() and downloadFile().
     * 
     * @param string $uri URI
     * @param string $md5 expected MD5 sum
     * @see Downloader::downloadFile()
     * @see Downloader::downloadContent()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider("provideTestURIs")]
    public function testDownloadSequence($uri, $md5)
    {
        $downloader = new Downloader();
        
        $content = $downloader->downloadContent($uri);
        $this->assertEquals($md5, md5($content));

        $file = $downloader->downloadFile($uri);
        $this->assertEquals($md5, md5_file($file));
        unlink($file);

        $content = $downloader->downloadContent($uri);
        $this->assertEquals($md5, md5($content));
    }

    /**
     * Tests failing downloadContent();
     * 
     * @see Downloader::downloadContent()
     */
    public function testFailDownloadContent()
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Download\Exception\DownloaderException::class);

        $downloader = new Downloader();
        $downloader->downloadContent("http://example.org/XXX");
    }

    /**
     * Tests failing downloadFile();
     * 
     * @see Downloader::downloadFile()
     */
    public function testFailDownloadFile()
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Download\Exception\DownloaderException::class);

        $downloader = new Downloader();
        $file = $downloader->downloadFile("http://example.org/XXX");
    }
}
