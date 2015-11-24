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

use noFlash\TorrentGhost\Aggregator\AbstractAggregator;
use noFlash\TorrentGhost\Aggregator\AggregatorInterface;
use noFlash\TorrentGhost\Configuration\AggregatorAbstractConfiguration;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use Psr\Log\LoggerInterface;

class AbstractAggregatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TorrentGhostConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appConfiguration;

    /**
     * @var AggregatorAbstractConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregatorConfiguration;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var AbstractAggregator
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->appConfiguration = $this->getMockBuilder(
            '\noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration'
        )->getMock();

        $this->aggregatorConfiguration = $this->getMockBuilder(
            '\noFlash\TorrentGhost\Configuration\AggregatorAbstractConfiguration'
        )->getMock();

        $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMockForAbstractClass();

        $this->subjectUnderTest = $this->getMockBuilder('noFlash\TorrentGhost\Aggregator\AbstractAggregator');
        $this->subjectUnderTest->setConstructorArgs(
            [
                $this->appConfiguration,
                $this->aggregatorConfiguration,
                $this->logger
            ]
        );
        $this->subjectUnderTest->enableOriginalConstructor();
        $this->subjectUnderTest->enableProxyingToOriginalMethods();
        $this->subjectUnderTest = $this->subjectUnderTest->getMockForAbstractClass();
    }

    public function testClassIsDefinedAbstract()
    {
        $classReflection = new \ReflectionClass('noFlash\TorrentGhost\Aggregator\AbstractAggregator');
        $this->assertTrue($classReflection->isAbstract());
    }

    public function testClassImplementsConfigurationInterface()
    {
        $this->assertInstanceOf('\noFlash\TorrentGhost\Aggregator\AggregatorInterface', $this->subjectUnderTest);
    }

    public function testCallingPingWillNotProduceError()
    {
        $this->assertNull($this->subjectUnderTest->ping());
    }

    public function testPingIntervalReturnsNoPingInterval()
    {
        $this->assertSame(AggregatorInterface::NO_PING_INTERVAL, $this->subjectUnderTest->getPingInterval());
    }

    //public function testInstanceNameConsistsOfDefaultTypeUnderscoreAndConfigurationDefinedName()
    //{
    //    $defaultType = AggregatorInterface::TYPE;
    //    $configurationName = md5(rand()); //Some random name, can be anything
    //    $expectedInstanceName = $defaultType . '_' . $configurationName;
    //
    //    $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getName')->willReturn(
    //        $configurationName
    //    );
    //
    //    $this->assertSame($expectedInstanceName, $this->subjectUnderTest->getName());
    //}

    public function testAggregatorIsConsideredReadyIfConfigurationIsValid()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('isValid')->willReturn(false);
        $this->assertFalse($this->subjectUnderTest->isReady());
    }

    public function testAggregatorIsNotConsideredReadyIfConfigurationIsNotValid()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('isValid')->willReturn(false);
        $this->assertFalse($this->subjectUnderTest->isReady());
    }

    public function testTryingToGetStreamReturnsNull()
    {
        $this->assertNull($this->subjectUnderTest->getStream());
    }

    public function testCheckingIfStreamIsWriteReadyReturnsFalseAndProducesErrorInLog()
    {
        $expectedLogLine = 'noFlash\TorrentGhost\Aggregator\AbstractAggregator::isWriteReady() was called but it was not expected (bug?)';
        $this->logger->expects($this->once())->method('error')->with($expectedLogLine);

        $this->assertFalse($this->subjectUnderTest->isWriteReady());
    }

    public function testInformingAggregatorStreamIsReadyToWriteProducesErrorInLog()
    {
        $expectedLogLine = 'noFlash\TorrentGhost\Aggregator\AbstractAggregator::onWrite() was called but it was not expected (bug?)';
        $this->logger->expects($this->once())->method('error')->with($expectedLogLine);

        $this->subjectUnderTest->onWrite();
    }

    public function testInformingAggregatorStreamIsReadyToReadProducesErrorInLog()
    {
        $expectedLogLine = 'noFlash\TorrentGhost\Aggregator\AbstractAggregator::onRead() was called but it was not expected (bug?)';
        $this->logger->expects($this->once())->method('error')->with($expectedLogLine);

        $this->subjectUnderTest->onRead();
    }

    public function testNameIsExtractedAccordingToGivenPattern()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/^(.buntu\.x86-64\.NIGHTLY)/'
        );

        $testString = 'Xbuntu.x86-64.NIGHTLY.2015-10-22';
        $expectedResult = 'Xbuntu.x86-64.NIGHTLY';

        $this->assertSame($expectedResult, $this->subjectUnderTest->extractTitle($testString));
    }

    public function testIfNameExtractionProduceMoreThanOneResultWarningIsRaisedAndFirstMatchIsReturned()
    {
        $configurationName = md5(rand()); //Some random name, can be anything
        $this->aggregatorConfiguration->expects($this->any())->method('getName')->willReturn($configurationName);

        $pattern = '/(DerpBian)\.ARM\.(DEV)$/';
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            $pattern
        );


        $testString = 'MoewDerpBian.ARM.DEV';
        $expectedResult = 'DerpBian';
        $expectedWarning = "Pattern $pattern configured in $configurationName aggregator to extract title resulted in 2 matches instead of one for input string \"$testString\". Only first one will be used.";

        $this->logger->expects($this->once())->method('warning')->with($expectedWarning);
        $this->assertSame($expectedResult, $this->subjectUnderTest->extractTitle($testString));
    }

    public function testTryingToExtractTitleWithInvalidPatternWillThrowRegexException()
    {
        $configurationName = md5(rand()); //Some random name, can be anything
        $this->aggregatorConfiguration->expects($this->any())->method('getName')->willReturn($configurationName);

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/invalid pattern#x'
        );

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\RegexException',
            'Pattern was configured for ' . $configurationName . ' aggregator to extract name'
        );
        $this->subjectUnderTest->extractTitle('test');
    }

    public function testIfTitleIsNotPresentInsideStringDuringExtractionFalseIsReturned()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/([0-9]+)/'
        );

        $this->assertFalse($this->subjectUnderTest->extractTitle('There is no numbers in this string'));
    }

    public function testIfTitlePatternMatchedButDidNotReturnedAnyResultsDuringExtractionFalseIsReturned()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getNameExtractPattern')->willReturn(
            '/[0-9]+/'
        );

        $this->assertFalse(
            $this->subjectUnderTest->extractTitle('There are 2 numbers in that string which one of them is 1')
        );
    }

    public function testLinkIsExtractedAccordingToGivenPattern()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/^File\: (http\:.*?) /'
        );

        $testString = 'File: http://example.com/file.bin | CRC32: 4020398583';
        $expectedResult = 'http://example.com/file.bin';

        $this->assertSame($expectedResult, $this->subjectUnderTest->extractLink($testString));
    }

    public function testIfLinkExtractionProduceMoreThanOneResultWarningIsRaisedAndFirstMatchIsReturned()
    {
        $configurationName = md5(rand()); //Some random name, can be anything
        $this->aggregatorConfiguration->expects($this->any())->method('getName')->willReturn($configurationName);

        $pattern = '/^File\: (http\:.*?) \| (CRC32)\: (.*?)/';
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            $pattern
        );


        $testString = 'File: http://example.org/moew.file | CRC32: 2001201454';
        $expectedResult = 'http://example.org/moew.file';
        $expectedWarning = "Pattern $pattern configured in $configurationName aggregator to extract link resulted in 3 matches instead of one for input string \"$testString\". Only first one will be used.";

        $this->logger->expects($this->once())->method('warning')->with($expectedWarning);
        $this->assertSame($expectedResult, $this->subjectUnderTest->extractLink($testString));
    }

    public function testTryingToExtractLinkWithInvalidPatternWillThrowRegexException()
    {
        $configurationName = md5(rand()); //Some random name, can be anything
        $this->aggregatorConfiguration->expects($this->any())->method('getName')->willReturn($configurationName);

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '%derp pattern *yy'
        );

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\RegexException',
            'Pattern was configured for ' . $configurationName . ' aggregator to extract link'
        );
        $this->subjectUnderTest->extractLink('this is example string');
    }

    public function testIfLinkIsNotPresentInsideStringDuringExtractionFalseIsReturned()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/(http.*?) /'
        );

        $this->assertFalse($this->subjectUnderTest->extractLink('URL: ftp://example.tld/file.elif [NEW]'));
    }

    public function testIfLinkPatternMatchedButDidNotReturnedAnyResultsDuringExtractionFalseIsReturned()
    {
        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkExtractPattern')->willReturn(
            '/[a-f]+/'
        );

        $this->assertFalse($this->subjectUnderTest->extractLink('For sure there\'re some a and f\'s in this string'));
    }

    public function testLinkIsTransformedAccordingToGivenPattern()
    {
        $this->aggregatorConfiguration->expects($this->any())->method('getLinkExtractPattern')->willReturn(
            '/ (http\:.*?) /'
        );

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTransformPattern')->willReturn(
            ['/^(http\:)(.*?)$/', 'https:$2']
        );

        $testString = 'DL: http://example.tld/moew.cat | INT | NEW | TESTED/NGT';
        $expectedResult = 'https://example.tld/moew.cat';

        $this->assertSame($expectedResult, $this->subjectUnderTest->extractLink($testString));
    }

    public function testTryingToTransformLinkWithInvalidPatternWillThrowRegexException()
    {
        $configurationName = md5(rand()); //Some random name, can be anything
        $this->aggregatorConfiguration->expects($this->any())->method('getName')->willReturn($configurationName);

        $this->aggregatorConfiguration->expects($this->any())->method('getLinkExtractPattern')->willReturn('/(.+)/');

        $this->aggregatorConfiguration->expects($this->atLeastOnce())->method('getLinkTransformPattern')->willReturn(
            '^wtf /r'
        );

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\RegexException',
            'Pattern was configured for ' . $configurationName . ' aggregator to transform link'
        );
        $this->subjectUnderTest->extractLink('this is example string');
    }
}
