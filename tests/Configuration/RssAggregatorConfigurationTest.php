<?php

namespace noFlash\TorrentGhost\Test\Configuration;


use noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration;

class RssAggregatorConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RssAggregatorConfiguration
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new RssAggregatorConfiguration();
    }

    public function testClassExtendsAggregatorAbstractConfiguration()
    {
        $this->assertInstanceOf('\noFlash\TorrentGhost\Configuration\AggregatorAbstractConfiguration',
            $this->subjectUnderTest);
    }

    public function testUrlIsNullByDefault()
    {
        $this->assertNull($this->subjectUnderTest->getUrl());
    }

    public function validUrlsProvider()
    {
        return [
            ['http://example.org'],
            ['https://example.org'],
            ['http://example.org/'],
            ['https://example.org/'],
            ['http://example.org/test'],
            ['http://example.org/test?x=1&y=2'],
            ['http://test:test@example.org/'],
            ['http://test:test@example.org:88/'],
            ['http://test@example.org/'],
            ['http://idn-name-with-ąćół-characters.pl/'],
            ['http://xn--punnycode-name-with--characters-z9c70dec04j.pl/'],
            ['http://example.org/żółty-kot'],
            ['http://example.pizza'],
            ['http://example.corporate'],
            ['ftp://example.corporate'],
            ['ftps://example.corporate'],
            ['http://127.0.0.1/feed.rss'],
            ['https://203.0.113.123/feed.rss'],
            ['ftp://127.0.0.1/feed.rss'],
            ['ftps://203.0.113.123/feed.rss'],
        ];
    }

    /**
     * @dataProvider validUrlsProvider
     */
    public function testUrlAcceptsValidValues($url)
    {
        $this->subjectUnderTest->setUrl($url);
        $this->assertSame($url, $this->subjectUnderTest->getUrl());
    }

    public function invalidOrDangerousUrlsProvider()
    {
        return [
            ['derp-derp-derp'],
            ['/derp'],
            ['derp.pl'],
            ['http://ex am ple.org'],
            ['https://ex am ple.org/'],
            ['ftp://ex am ple.org'],
            ['ftps://ex am ple.org/'],
            ['file:///etc/hosts'],
            ['javascript://aa'],
            ['javascript://test%0Aalert(321)'],
            [null]
        ];
    }

    /**
     * @dataProvider invalidOrDangerousUrlsProvider
     */
    public function testUrlSetterRejectsInvalidValues($url)
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->subjectUnderTest->setUrl($url);
    }

    public function testIntervalIsSetToFiveMinutesByDefault()
    {
        $this->assertSame(300, $this->subjectUnderTest->getInterval());
    }

    public function testIntervalAcceptsValidTimes()
    {
        $this->subjectUnderTest->setInterval(10);
        $this->assertSame(10, $this->subjectUnderTest->getInterval());

        $this->subjectUnderTest->setInterval(3000);
        $this->assertSame(3000, $this->subjectUnderTest->getInterval());

        $this->subjectUnderTest->setInterval(1);
        $this->assertSame(1, $this->subjectUnderTest->getInterval());

        $this->subjectUnderTest->setInterval(PHP_INT_MAX);
        $this->assertSame(PHP_INT_MAX, $this->subjectUnderTest->getInterval());
    }

    public function testIntervalThrowsInvalidArgumentExceptionWhenFloatWasPassed()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->subjectUnderTest->setInterval(1.0);
    }

    public function testIntervalThrowsInvalidArgumentExceptionWhenZeroWasPassed()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->subjectUnderTest->setInterval(0);
    }

    public function testIntervalThrowsInvalidArgumentExceptionWhenValueBelowZeroWasPassed()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->subjectUnderTest->setInterval(-5);
    }

    public function validXmlTagsProvider()
    {
        return [
            ['title'],
            ['test'],
            ['sample-longer-tag'],
        ];
    }

    /**
     * @dataProvider validXmlTagsProvider
     */
    public function testNameTagNameSetterAcceptsValidXmlTagNames($tagName)
    {
        $this->subjectUnderTest->setNameTagName($tagName);
        $this->assertSame($tagName, $this->subjectUnderTest->getNameTagName());
    }

    public function testNameTagNameSetterRejectsTagsStartingWithNumber()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid tagName for name');
        $this->subjectUnderTest->setNameTagName('1a');
    }

    public function testNameTagNameSetterRejectsTagsStartingWithDot()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid tagName for name');
        $this->subjectUnderTest->setNameTagName('.xx');
    }

    public function testNameTagNameSetterRejectsTagsContainingSpaces()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid tagName for name');
        $this->subjectUnderTest->setNameTagName('test tag');
    }

    /**
     * @dataProvider validXmlTagsProvider
     */
    public function testLinkTagNameSetterAcceptsValidXmlTagNames($tagName)
    {
        $this->subjectUnderTest->setLinkTagName($tagName);
        $this->assertSame($tagName, $this->subjectUnderTest->getLinkTagName());
    }

    public function testLinkTagNameSetterRejectsTagsStartingWithNumber()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid tagName for link');
        $this->subjectUnderTest->setLinkTagName('1b');
    }

    public function testLinkTagNameSetterRejectsTagsStartingWithDot()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid tagName for link');
        $this->subjectUnderTest->setLinkTagName('.yyyy');
    }

    public function testLinkTagNameSetterRejectsTagsContainingSpaces()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Invalid tagName for link');
        $this->subjectUnderTest->setLinkTagName('example tag name');
    }

    public function testConfigurationIsConsideredInvalidByDefault()
    {
        $this->assertFalse($this->subjectUnderTest->isValid());
    }

    public function testConfigurationIsConsideredValidAfterSettingUrl()
    {
        $this->subjectUnderTest->setUrl('http://example.org/feed.rss');
        $this->assertTrue($this->subjectUnderTest->isValid());
    }
}
