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

namespace noFlash\TorrentGhost\Test;

use noFlash\TorrentGhost\Application;
use noFlash\TorrentGhost\Configuration\ConfigurationProvider;
use Psr\Log\LoggerInterface;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationProvider
     */
    private $configurationProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Application
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->configurationProvider = $this->getMockBuilder(
            '\noFlash\TorrentGhost\Configuration\ConfigurationProvider'
        )->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMockForAbstractClass();

        $this->subjectUnderTest = new Application($this->configurationProvider, $this->logger);
    }


    public function testSourcesIsEmptyArrayOnFreshObject()
    {
        $this->assertSame([], $this->subjectUnderTest->getSources());
    }

    public function testDownloadRulesIsEmptyArrayOnFreshObject()
    {
        $this->assertSame([], $this->subjectUnderTest->getDownloadRules());
    }

}
