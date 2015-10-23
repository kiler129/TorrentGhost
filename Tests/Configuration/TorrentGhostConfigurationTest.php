<?php

namespace noFlash\TorrentGhost\Tests\Configuration;


use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;

class TorrentGhostConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TorrentGhostConfiguration
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new TorrentGhostConfiguration();
    }

    public function testClassImplementsConfigurationInterface()
    {
        $this->assertInstanceOf('\noFlash\TorrentGhost\Configuration\ConfigurationInterface', $this->subjectUnderTest);
    }

    public function testFileSavePathIsNullByDefault()
    {
        $this->assertNull($this->subjectUnderTest->getFilesSavePath());
    }

    public function testFilesSavePathAcceptsValidAbsoluteDirectoryPath()
    {
        $exampleDirectory = realpath(sys_get_temp_dir()); //It cannot be mocked using e.g. vfs sine SUT will try to extract realpath. I know it's bad assumption for that test but it's sad reality.

        $this->subjectUnderTest->setFilesSavePath($exampleDirectory);
        $this->assertSame($exampleDirectory, $this->subjectUnderTest->getFilesSavePath());
    }

    public function testFilesSavePathAcceptsValidRelativeDirectoryPathAndConvertsItToAbsolute()
    {
        $exampleRelative = dirname(__FILE__);
        $exampleAbsolute = realpath($exampleRelative);

        $this->subjectUnderTest->setFilesSavePath($exampleRelative);
        $this->assertSame($exampleAbsolute, $this->subjectUnderTest->getFilesSavePath());
    }

    public function testFilesSavePathRejectsNonDirectoryPaths()
    {
        $file = tempnam(sys_get_temp_dir(), 'TGH');
        if ($file === false) {
            $this->fail("Environment problem: failed to create test file");
        }

        $this->setExpectedException('\LogicException', 'doesn\'t represent directory');
        $this->subjectUnderTest->setFilesSavePath($file);
        @unlink($file);
    }

    public function testFilesSavePathRejectsInaccessiblePaths()
    {
        $unknownPath = tempnam(sys_get_temp_dir(), 'TGH');
        unlink($unknownPath);

        $this->setExpectedException('\RuntimeException', 'invalid or inaccessible');
        $this->subjectUnderTest->setFilesSavePath($unknownPath);
    }

    public function testFileSizeLimitIsSetTo25MegabytesByDefault()
    {
        $this->assertSame(25 * 1000 * 1000, $this->subjectUnderTest->getRawFileSizeLimit());
    }

    public function testFileSizeLimitAcceptsLargeRawValues()
    {
        $this->subjectUnderTest->setRawFileSizeLimit(500 * 1000 * 1000);
        $this->assertSame(500 * 1000 * 1000, $this->subjectUnderTest->getRawFileSizeLimit(), 'Failed for 500MB');

        $this->subjectUnderTest->setRawFileSizeLimit((int)(1.5 * 1000 * 1000 * 1000));
        $this->assertEquals(1.5 * 1000 * 1000 * 1000, $this->subjectUnderTest->getRawFileSizeLimit(),
            'Failed for 1.5GB');

        $this->subjectUnderTest->setRawFileSizeLimit(2 * 1000 * 1000 * 1000);
        $this->assertSame(2 * 1000 * 1000 * 1000, $this->subjectUnderTest->getRawFileSizeLimit(), 'Failed for 2GB');
    }

    public function testFileSizeLimitAcceptsRawZero()
    {
        $this->subjectUnderTest->setRawFileSizeLimit(0);
        $this->assertSame(0, $this->subjectUnderTest->getRawFileSizeLimit());
    }

    public function testFileSizeLimitRejectsRawNegativeValues()
    {
        $this->setExpectedException('\OutOfRangeException');
        $this->subjectUnderTest->setRawFileSizeLimit(-1);
    }

    public function testFileSizeLimitRejectsRawValuesExceedingMaxSize()
    {
        $this->setExpectedException('\OutOfRangeException');
        $this->subjectUnderTest->setRawFileSizeLimit(2147483648);
    }

    public function testFileSizeLimitRejectsRawFloatValues()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->subjectUnderTest->setRawFileSizeLimit(M_PI);
    }

    public function raw2HumanValuesProvider()
    {
        return [
            [0, '0'],
            [1, '1'],
            [999, '999'],
            [1000, '1K'],
            [1500, '1.5K'],
            [100000, '100K'],
            [999999, '999.999K'],
            [1000000, '1M'],
            [999999999, '999.999999M'],
            [1000000000, '1G'],
            [1200000000, '1.2G'],
            [2000000000, '2G'],
            [2147483647, '2.147483647G']
        ];
    }

    /**
     * @dataProvider raw2HumanValuesProvider
     */
    public function testFileSizeLimitProperlyConvertsRawValues($raw, $human)
    {
        $this->subjectUnderTest->setRawFileSizeLimit($raw);
        $this->assertSame($human, $this->subjectUnderTest->getFileSizeLimit());
    }

    public function human2RawValuesProvider()
    {
        return [
            //Standard integers
            [0, 0],
            [1, 1],
            [999, 999],
            [1000, 1000],
            [1500, 1500],
            [100000, 100000],
            [999999, 999999],
            [1000000, 1000000],
            [999999999, 999999999],
            [1000000000, 1000000000],
            [1200000000, 1200000000],
            [2000000000, 2000000000],
            [2147483647, 2147483647],

            //Integers as strings
            ['0', 0],
            ['1', 1],
            ['999', 999],
            ['1000', 1000],
            ['1500', 1500],
            ['100000', 100000],
            ['999999', 999999],
            ['1000000', 1000000],
            ['999999999', 999999999],
            ['1000000000', 1000000000],
            ['1200000000', 1200000000],
            ['2000000000', 2000000000],
            ['2147483647', 2147483647],

            //Floats
            [0.1, 0],
            [0.4, 0],
            [0.5, 1],
            [1.9999, 2],

            //Floats as strings
            ['0.1', 0],
            ['0.4', 0],
            ['0.5', 1],
            ['1.9999', 2],

            //Values with postfixes
            ['1K', 1000],
            ['1.5K', 1500],
            ['100K', 100000],
            ['999.999K', 999999],
            ['1M', 1000000],
            ['999.999999M', 999999999],
            ['1G', 1000000000],
            ['1.2G', 1200000000],
            ['2G', 2000000000],
            ['2.147483647G', 2147483647]
        ];
    }

    /**
     * @dataProvider human2RawValuesProvider
     */
    public function testFileSizeLimitAcceptsValuesWithoutPostfix($human, $raw)
    {
        $this->subjectUnderTest->setFileSizeLimit($human);
        $this->assertSame($raw, $this->subjectUnderTest->getRawFileSizeLimit());
    }

    public function testFileSizeLimitRejectsNegativeValues()
    {
        $this->setExpectedException('\OutOfRangeException');
        $this->subjectUnderTest->setFileSizeLimit('-1M');
    }

    public function testFileSizeLimitRejectsValuesExceedingMaxSize()
    {
        $this->setExpectedException('\OutOfRangeException');
        $this->subjectUnderTest->setFileSizeLimit('2.147483648G');
    }

    public function testConfigurationIsConsideredValidAfterSettingFilesSavePath()
    {
        $this->assertFalse($this->subjectUnderTest->isValid(), 'Configuration should not be valid for fresh object');
        $this->subjectUnderTest->setFilesSavePath(sys_get_temp_dir());
        $this->assertTrue($this->subjectUnderTest->isValid(),
            'Configuration should be valid after setting files save path');
    }
}
