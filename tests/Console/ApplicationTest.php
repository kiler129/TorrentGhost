<?php
/*
 * This file is part of TorrentGhost project.
 * You are using it at your own risk and you are fully responsible
 *  for everything that code will do.
 *
 * (c) Grzegorz Zdanowski <grzegorz@noflash.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace noFlash\TorrentGhost\Test\Console;

use noFlash\TorrentGhost\Console\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new Application();
    }

    public function testApplicationNameAndVersionAreSetOnConstruction()
    {
        $this->assertSame(Application::NAME, $this->subjectUnderTest->getName());
        $this->assertSame(Application::VERSION, $this->subjectUnderTest->getVersion());
    }

    /**
     * @requires extension xdebug
     */
    public function testXDebugExtensionTraceAndScreamAreDisabledByConstructor()
    {
        if (!function_exists('ini_set') || !function_exists('ini_get')) {
            $this->markTestSkipped('ini_set/get() are disabled');
        }

        ini_set('xdebug.show_exception_trace', true);
        ini_set('xdebug.scream', true);

        new Application();

        $this->assertEquals(false, ini_get('xdebug.show_exception_trace'));
        $this->assertEquals(false, ini_get('xdebug.scream'));
    }

    public function testAllCommandsArePresent()
    {
        $this->assertTrue($this->subjectUnderTest->has('about'));
        $this->assertTrue($this->subjectUnderTest->has('run'));
        $this->assertTrue($this->subjectUnderTest->has('test-config'));
        $this->assertTrue($this->subjectUnderTest->has('test-rule'));
    }
}
