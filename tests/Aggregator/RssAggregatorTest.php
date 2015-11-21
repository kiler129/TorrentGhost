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

namespace noFlash\TorrentGhost\Test\Aggregator;

use noFlash\TorrentGhost\Aggregator\RssAggregator;
use noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use Psr\Log\LoggerInterface;

class RssAggregatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TorrentGhostConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appConfiguration;

    /**
     * @var RssAggregatorConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregatorConfiguration;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var RssAggregator
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->appConfiguration = $this->getMockBuilder(
            '\noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration'
        )->getMock();

        $this->aggregatorConfiguration = $this->getMockBuilder(
            '\noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration'
        )->getMock();

        $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMockForAbstractClass();

        $this->subjectUnderTest = new RssAggregator(
            $this->appConfiguration, $this->aggregatorConfiguration, $this->logger
        );
    }

    public function testClassExtendsAbstractAggregator()
    {
        $this->assertTrue(
            is_subclass_of(
                '\noFlash\TorrentGhost\Aggregator\RssAggregator',
                '\noFlash\TorrentGhost\Aggregator\AbstractAggregator'
            )
        );
    }

    public function testClassProvidesCorrectTypeConstant()
    {
        $this->assertSame('rss', RssAggregator::TYPE);
    }

    public function testFeedParserWillReturnFalseAndLogErrorOnInvalidXml()
    {
        $this->logger->expects($this->once())->method('error')->with($this->stringStartsWith('RSS XML parsing failed'));
        $this->assertFalse($this->subjectUnderTest->parseFeedXml('invalid xml'));
    }

    public function testFeedParserWillReturnFalseAndLogErrorIfChannelElementIsMissing()
    {
        $rss = '<rss version="2.0"></rss>';

        $this->logger->expects($this->once())->method('error')->with(
            $this->stringEndsWith('failed to extract items from XML')
        );
        $this->assertFalse($this->subjectUnderTest->parseFeedXml($rss));
    }

    public function testFeedParserWillReturnFalseAndLogErrorIfThereIsNoItems()
    {
        $rss = '<rss version="2.0"><channel></channel></rss>';

        $this->logger->expects($this->once())->method('error')->with(
            $this->stringEndsWith('failed to extract items from XML')
        );
        $this->assertFalse($this->subjectUnderTest->parseFeedXml($rss));
    }

    public function testFeedParserIsAbleToExtractNameAndLinksFromItems()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <custom-name-tag>Example name</custom-name-tag>
                            <custom-link-tag>http://link.tld/resource/</custom-link-tag>
                        </item>
                        <item>
                            <custom-name-tag>Another name</custom-name-tag>
                            <custom-link-tag>http://example.com/getme/</custom-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'custom-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'custom-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(.*?)$/'
        );

        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(
            ['Example' => 'http://link.tld/resource/', 'Another' => 'http://example.com/getme/'],
            $result
        );
    }

    public function testFeedParserIgnoresItemsAndEmitsWarningIfNameTagIsNotPresentInsideItem()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <other-name-tag>Example name</other-name-tag>
                            <other-link-tag>http://link.tld/resource/</other-link-tag>
                        </item>
                        <item>
                            <whoops-name-tag>Another name</whoops-name-tag>
                            <other-link-tag>http://example.com/getme/</other-link-tag>
                        </item>
                        <item>
                            <other-name-tag>Foo name</other-name-tag>
                            <other-link-tag>http://example.org/dl/</other-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'other-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'other-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(.*?)$/'
        );

        $this->logger->expects($this->once())->method('warning')->with(
            'Unable to locate name tag <other-name-tag> in rss_ for item#1. Skipping item.'
        );

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(['Example' => 'http://link.tld/resource/', 'Foo' => 'http://example.org/dl/'], $result);
    }


    public function testFeedParserIgnoresItemsAndEmitsWarningIfLinkTagIsNotPresentInsideItem()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <foo-name-tag>Example name</foo-name-tag>
                            <bar-link-tag>http://link.tld/resource/</bar-link-tag>
                        </item>
                        <item>
                            <foo-name-tag>Another name</foo-name-tag>
                            <foo-link-tag>http://example.com/getme/</foo-link-tag>
                        </item>
                        <item>
                            <foo-name-tag>Foo name</foo-name-tag>
                            <foo-link-tag>http://example.org/dl/</foo-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'foo-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'foo-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(.*?)$/'
        );

        $this->logger->expects($this->once())->method('warning')->with(
            'Unable to locate name tag <foo-link-tag> in rss_ for item#0. Skipping item.'
        );

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(['Another' => 'http://example.com/getme/', 'Foo' => 'http://example.org/dl/'], $result);
    }

    public function testFeedParserIgnoresItemsAndEmitsWarningIfNameCannotBeExtracted()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <foo-name-tag>Another name</foo-name-tag>
                            <foo-link-tag>http://example.com/getme/</foo-link-tag>
                        </item>
                        <item>
                            <foo-name-tag>Foo-name</foo-name-tag>
                            <foo-link-tag>http://example.org/dl/</foo-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'foo-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'foo-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(.*?)$/'
        );

        $this->logger->expects($this->once())->method('warning')->with(
            'Extracting name from "Foo-name" failed in rss_ for item#1. Skipping item.'
        );

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(['Another' => 'http://example.com/getme/'], $result);
    }

    public function testFeedParserIgnoresItemsAndEmitsWarningIfLinkCannotBeExtracted()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <foo-name-tag>Another name</foo-name-tag>
                            <foo-link-tag>https://example.com/getme/</foo-link-tag>
                        </item>
                        <item>
                            <foo-name-tag>Bar name</foo-name-tag>
                            <foo-link-tag>http://example.org/dl/</foo-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'foo-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'foo-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(https.*?)$/'
        );

        $this->logger->expects($this->once())->method('warning')->with(
            'Extracting link from "http://example.org/dl/" failed in rss_ for item#1. Skipping item.'
        );

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(['Another' => 'https://example.com/getme/'], $result);
    }

    public function testFeedParserIgnoresItemsAndEmitsWarningOnDuplicateItemName()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <another-name-tag>Example name</another-name-tag>
                            <foo-link-tag>http://link.tld/resource/</foo-link-tag>
                        </item>
                        <item>
                            <another-name-tag>Another name</another-name-tag>
                            <foo-link-tag>http://example.com/getme/</foo-link-tag>
                        </item>
                        <item>
                            <another-name-tag>Example name</another-name-tag>
                            <foo-link-tag>https://example.org/dl/</foo-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'another-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'foo-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(.*?)$/'
        );

        $this->logger->expects($this->once())->method('warning')->with(
            'Duplicate name "Example name" in rss_ for item#2. Skipping item.'
        );

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(
            ['Example' => 'http://link.tld/resource/', 'Another' => 'http://example.com/getme/'],
            $result
        );
    }

    public function testFeedParserTransformsLinks()
    {
        $rss = '<rss version="2.0">
                    <channel>
                        <title>Example channel</title>
                        <item>
                            <custom-name-tag>Example name</custom-name-tag>
                            <custom-link-tag>http://link.tld/resource/</custom-link-tag>
                        </item>
                        <item>
                            <custom-name-tag>Another name</custom-name-tag>
                            <custom-link-tag>http://example.com/getme/</custom-link-tag>
                        </item>
                    </channel>
                </rss>';

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameTagName')->willReturn(
            'custom-name-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTagName')->willReturn(
            'custom-link-tag'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.*?) /'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^(.*?)$/'
        );
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTransformPattern')->willReturn(
            ['/^(.*?)$/', '$1?passkey=1234']
        );


        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $result = $this->subjectUnderTest->parseFeedXml($rss);
        $this->assertSame(
            [
                'Example' => 'http://link.tld/resource/?passkey=1234',
                'Another' => 'http://example.com/getme/?passkey=1234'
            ],
            $result
        );
    }

    public function testRetrievingPingIntervalReturnsValueFromConfiguration()
    {
        $randomNumber = rand(1, getrandmax());
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getInterval')->willReturn(
            $randomNumber
        );

        $this->assertSame($randomNumber, $this->subjectUnderTest->getPingInterval());
    }
}
