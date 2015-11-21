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

use noFlash\TorrentGhost\Configuration\ConfigurationProvider;
use Psr\Log\LoggerInterface;

/**
 * @TODO This test should not use example YAML file!
 */
class ConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigurationProvider
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMockForAbstractClass();

        $this->subjectUnderTest = ConfigurationProvider::fromYamlFile(
            __DIR__ . '/../../doc/config.example.yml',
            $this->logger
        );
    }


    public function testGetAggregatorConfigurationClassNameByAggregatorTypeProvidesValidClassName()
    {
        $expectedClassName = '\noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration';

        $this->assertSame(
            $expectedClassName,
            $this->subjectUnderTest->getAggregatorConfigurationClassNameByAggregatorType('Rss')
        );
    }

    public function testGetAggregatorConfigurationClassNameByAggregatorTypeIsCaseInsensitive()
    {
        $expectedClassName = '\noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration';

        $this->assertSame(
            $expectedClassName,
            $this->subjectUnderTest->getAggregatorConfigurationClassNameByAggregatorType('rss')
        );

        $this->assertSame(
            $expectedClassName,
            $this->subjectUnderTest->getAggregatorConfigurationClassNameByAggregatorType('RSS')
        );

        $this->assertSame(
            $expectedClassName,
            $this->subjectUnderTest->getAggregatorConfigurationClassNameByAggregatorType('rsS')
        );
    }

    public function testGetAggregatorConfigurationClassNameByAggregatorTypeReturnsFalseForUnknownClass()
    {
        $this->assertFalse(
            $this->subjectUnderTest->getAggregatorConfigurationClassNameByAggregatorType('unknown')
        );
    }

    public function testGetAggregatorClassNameByAggregatorNameProvidesValidClassName()
    {
        $expectedClassName = '\noFlash\TorrentGhost\Aggregator\RssAggregator';

        $this->assertSame(
            $expectedClassName,
            $this->subjectUnderTest->getAggregatorClassNameByAggregatorName('AnotherOSTracker')
        );
    }

    public function testGetAggregatorClassNameByAggregatorNameReturnsFalseForUnknownClass()
    {
        $this->assertFalse(
            $this->subjectUnderTest->getAggregatorConfigurationClassNameByAggregatorType('BlahBlah')
        );
    }

    public function testGetApplicationConfigurationProvidesProperlyConfiguredAppConfiguration()
    {
        $configuration = $this->subjectUnderTest->getApplicationConfiguration();

        $this->assertInstanceOf('\noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration', $configuration);
        $this->assertTrue($configuration->isValid());
    }

    public function testGetAggregatorsNamesProvidesArrayWithAllSourceNames()
    {
        $names = $this->subjectUnderTest->getAggregatorsNames();

        $this->assertInternalType('array', $names);
        $this->assertCount(2, $names);
        $this->assertContains('OpenSourceTracker', $names);
        $this->assertContains('AnotherOSTracker', $names);
    }

    public function testGetAggregatorConfigurationProvidesConfiguredAggregatorConfigurationObject()
    {
        $aggregatorName = 'AnotherOSTracker';
        $configuration = $this->subjectUnderTest->getAggregatorConfiguration($aggregatorName);

        $this->assertInstanceOf('\noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration', $configuration);
        $this->assertSame($aggregatorName, $configuration->getName());
        $this->assertTrue($configuration->isValid());
    }

    public function testGetAggregatorConfigurationReturnsFalseForUnknownAggregator()
    {
        $configuration = $this->subjectUnderTest->getAggregatorConfiguration('DerpDerp');

        $this->assertFalse($configuration);
    }

    public function testGetRulesNamesProvidesArrayWithAllRulesNames()
    {
        $names = $this->subjectUnderTest->getRulesNames();

        $this->assertInternalType('array', $names);
        $this->assertCount(1, $names);
        $this->assertContains('Ubuntu', $names);
    }

    public function testGetRuleProvidesConfiguredRuleObject()
    {
        $ruleName = 'Ubuntu';
        $configuration = $this->subjectUnderTest->getRule($ruleName);

        $this->assertInstanceOf('\noFlash\TorrentGhost\Rule\DownloadRule', $configuration);
        $this->assertSame($ruleName, $configuration->getName());
        //$this->assertTrue($configuration->isValid());

        //TODO this testcase is not fully baked
    }

    public function testGetRuleReturnsFalseForUnknownRule()
    {
        $configuration = $this->subjectUnderTest->getRule('PiratedStuff');

        $this->assertFalse($configuration);
    }
}
