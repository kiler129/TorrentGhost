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

use noFlash\TorrentGhost\Command\RunCommand;
use Psr\Log\LoggerInterface;

class RunCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RunCommand
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new RunCommand;
    }

    public function testObjectExtendsCommand()
    {
        $this->assertInstanceOf('Symfony\Component\Console\Command\Command', $this->subjectUnderTest);
    }

    public function testCommandIsNamedRun()
    {
        $this->assertSame('run', $this->subjectUnderTest->getName());
    }

    public function testCommandHaveCorrectDescription()
    {
        $this->assertSame('Runs the application', $this->subjectUnderTest->getDescription());
    }
}
