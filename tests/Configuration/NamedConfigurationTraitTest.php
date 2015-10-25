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

namespace noFlash\TorrentGhost\Test\Configuration;


use noFlash\TorrentGhost\Configuration\NamedConfigurationTrait;

class NamedConfigurationTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NamedConfigurationTrait
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = $this->getMockForTrait('noFlash\TorrentGhost\Configuration\NamedConfigurationTrait');
    }

    public function testAggregatorNameCanBeSet()
    {
        $this->subjectUnderTest->setName('ExampleAggregator');
        $this->assertSame('ExampleAggregator', $this->subjectUnderTest->getName());

        $this->subjectUnderTest->setName('DerpDerpMoew');
        $this->assertSame('DerpDerpMoew', $this->subjectUnderTest->getName());
    }

    public function testAggregatorNameSetterConvertsValuesToString()
    {
        $this->subjectUnderTest->setName(123);
        $this->assertSame('123', $this->subjectUnderTest->getName());
    }
}
