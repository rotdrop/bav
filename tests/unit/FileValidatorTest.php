<?php

namespace malkusch\bav;

use malkusch\bav\DataBackend\File\Validator\FileValidator;

require_once __DIR__ . "/../bootstrap.php";

/**
 * Tests FileValidator.
 *
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 * @see BAV
 */
class FileValidatorTest extends \PHPUnit\Framework\TestCase
{
    private $file;
    private $invalidFile;

    protected function setUp(): void
    {
        $line = file(__DIR__ . '/../data/fileParserTest/bb-excerpt.txt')[0];
        $this->file = tempnam(sys_get_temp_dir(), 'bavtest');
        $this->invalidFile = tempnam(sys_get_temp_dir(), 'bavtest');
        file_put_contents(
            $this->file,
            str_repeat($line, (int) ceil(FileValidator::FILESIZE / strlen($line)))
        );
    }

    protected function tearDown(): void
    {
        unlink($this->file);
        unlink($this->invalidFile);
    }

    public function testValidate()
    {
        $this->expectNotToPerformAssertions();
        $validator = new FileValidator();
        $validator->validate($this->file);
    }

    public function testInvalidFileSize()
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Validator\Exception\InvalidFilesizeException::class);

        $validator = new FileValidator();
        $validator->validate(__FILE__);
    }

    public function testInvalidLineLength()
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Validator\Exception\InvalidLineLengthException::class);

        $invalidFile = $this->invalidFile;
        copy($this->file, $invalidFile);

        $fp = fopen($invalidFile, "c");
        fputs($fp, "invalid line\n");
        fclose($fp);

        $validator = new FileValidator();
        $validator->validate($invalidFile);
    }

    public function testNotConstantLineLength()
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Validator\Exception\InvalidLineLengthException::class);

        $invalidFile = $this->invalidFile;
        copy($this->file, $invalidFile);

        $fp = fopen($invalidFile, "a");
        fputs($fp, "X\n");
        fclose($fp);

        $validator = new FileValidator();
        $validator->validate($invalidFile);
    }

    public function testInvalidFirstLineContent()
    {
        $this->expectException(\malkusch\bav\DataBackend\File\Validator\Exception\FieldException::class);

        $invalidFile = $this->invalidFile;
        copy($this->file, $invalidFile);

        $fp = fopen($invalidFile, "c");
        fputs($fp, "XXX");
        fclose($fp);

        $validator = new FileValidator();
        $validator->validate($invalidFile);
    }
}
