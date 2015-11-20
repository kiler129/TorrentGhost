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

use noFlash\TorrentGhost\Command\AboutCommand;
use noFlash\TorrentGhost\Console\Application;
use Symfony\Component\Console\Output\StreamOutput;

class AboutCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AboutCommand
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new AboutCommand();
    }

    public function testCommandIsNamedRun()
    {
        $this->assertSame('about', $this->subjectUnderTest->getName());
    }

    public function testCommandHaveCorrectDescription()
    {
        $this->assertSame('Information about TorrentGhost', $this->subjectUnderTest->getDescription());
    }

    public function testCommandOutputsSomeTextWithName()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMockForAbstractClass();

        $fp = fopen('php://memory', 'w');
        $output = new StreamOutput($fp);

        $this->subjectUnderTest->run($input, $output);

        fseek($fp, 0);
        $this->assertContains(Application::NAME, stream_get_contents($fp));
    }
}
