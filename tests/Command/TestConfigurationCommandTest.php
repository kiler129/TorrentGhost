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

namespace noFlash\TorrentGhost\Test\Command;

use noFlash\TorrentGhost\Command\TestConfigurationCommand;

class TestConfigurationCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestConfigurationCommand
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new TestConfigurationCommand();
    }

    public function testCommandIsNamedRun()
    {
        $this->assertSame('test-config', $this->subjectUnderTest->getName());
    }

    public function testCommandHaveCorrectDescription()
    {
        $this->assertSame('Tests application configuration', $this->subjectUnderTest->getDescription());
    }
}
