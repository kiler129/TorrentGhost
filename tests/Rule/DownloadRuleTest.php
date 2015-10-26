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

namespace noFlash\TorrentGhost\Test\Rule;


use noFlash\TorrentGhost\Rule\DownloadRule;

class DownloadRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DownloadRule
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new DownloadRule();
    }

    public function testClassImplementsRuleInterface()
    {
        $this->assertInstanceOf('\noFlash\TorrentGhost\Rule\RuleInterface', $this->subjectUnderTest);
    }

    public function testClassUsesNamedConfigurationTrait()
    {
        $sutReflection = new \ReflectionClass('\noFlash\TorrentGhost\Rule\DownloadRule');
        $classTraits = $sutReflection->getTraitNames();

        $this->assertContains('noFlash\TorrentGhost\Configuration\NamedConfigurationTrait', $classTraits);
    }

    public function testEmptySourcesArrayIsProvidedByFreshObject()
    {
        $this->assertSame([], $this->subjectUnderTest->getSources());
    }

    public function testAggregatorInterfaceInstanceCanBeAddedAsSource()
    {
        $aggregator = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                           ->getMockForAbstractClass();

        $this->assertTrue($this->subjectUnderTest->addSource($aggregator), 'Failed to add source');
        $this->assertSame(
            [$aggregator],
            $this->subjectUnderTest->getSources(),
            'Source is not available after addition'
        );
    }

    public function testMoreThanSingleSourceCanBeAdded()
    {
        $aggregator1 = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                            ->getMockForAbstractClass();
        $aggregator2 = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                            ->getMockForAbstractClass();

        $this->assertTrue($this->subjectUnderTest->addSource($aggregator1), 'Failed to add 1st source');
        $this->assertTrue($this->subjectUnderTest->addSource($aggregator2), 'Failed to add 2md source');

        $availableSources = $this->subjectUnderTest->getSources();
        $this->assertContains($aggregator1, $availableSources, '1st source is not available after addition');
        $this->assertContains($aggregator2, $availableSources, '2nd source is not available after addition');
    }

    public function testTheSameSourceCanBeAddedOnlyOnce()
    {
        $aggregator = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                           ->getMockForAbstractClass();
        $aggregator->expects($this->atLeastOnce())->method('getName')->willReturn('TEST');

        $this->assertTrue($this->subjectUnderTest->addSource($aggregator), 'Failed to add source for the first time');

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\InvalidSourceException',
            'Cannot add source TEST - it is already in'
        );
        $this->subjectUnderTest->addSource($aggregator);
    }

    public function testAddSourceRejectsObjectsOtherThanAggregatorInterface()
    {
        if (PHP_MAJOR_VERSION < 7) {
            /*
             * For explanation refer to links below:
             * - http://stackoverflow.com/questions/25570786/how-to-unit-test-type-hint-with-phpunit
             * - https://github.com/sebastianbergmann/phpunit/issues/178
             */
            $this->setExpectedException(get_class(new \PHPUnit_Framework_Error("", 0, "", 1)));

        } else {
            $this->setExpectedException('\TypeError');
        }

        $this->subjectUnderTest->addSource(new \stdClass());
    }

    public function testSourceCanBeRemoved()
    {
        $aggregator = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                           ->getMockForAbstractClass();

        $this->subjectUnderTest->addSource($aggregator);
        $this->assertTrue($this->subjectUnderTest->removeSource($aggregator), 'Failed to remove source');
        $this->assertNotContains(
            $aggregator,
            $this->subjectUnderTest->getSources(),
            'Source is still available after removing'
        );
        $this->assertSame(
            [],
            $this->subjectUnderTest->getSources(),
            'Sources array looks corrupted after removing added source'
        );
    }

    public function testRemovingOneSourceLeavesOtherIntact()
    {
        $aggregator1 = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                            ->getMockForAbstractClass();
        $aggregator2 = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                            ->getMockForAbstractClass();

        $this->subjectUnderTest->addSource($aggregator1);
        $this->subjectUnderTest->addSource($aggregator2);
        $this->assertTrue($this->subjectUnderTest->removeSource($aggregator1));

        $availableSources = array_values($this->subjectUnderTest->getSources()); //Test intentionally ignores array keys
        $this->assertNotContains($aggregator1, $availableSources, 'Source is still available after removing');
        $this->assertSame(
            [$aggregator2],
            $availableSources,
            'Sources array looks corrupted after removing single source'
        );
    }

    public function testSourceNotAddedBeforeResultsInInvalidSourceException()
    {
        $aggregator = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                           ->getMockForAbstractClass();
        $aggregator->expects($this->atLeastOnce())->method('getName')->willReturn('FOO');

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\InvalidSourceException',
            'Cannot remove Source FOO - it was not added to'
        );
        $this->subjectUnderTest->removeSource($aggregator);
    }

    public function testRemoveSourceRejectsObjectsOtherThanAggregatorInterface()
    {
        if (PHP_MAJOR_VERSION < 7) {
            /*
             * For explanation refer to links below:
             * - http://stackoverflow.com/questions/25570786/how-to-unit-test-type-hint-with-phpunit
             * - https://github.com/sebastianbergmann/phpunit/issues/178
             */
            $this->setExpectedException(get_class(new \PHPUnit_Framework_Error("", 0, "", 1)));

        } else {
            $this->setExpectedException('\TypeError');
        }

        $this->subjectUnderTest->removeSource(new \stdClass());
    }

    public function testHasSourceCorrectlyLocatesSources()
    {
        $aggregator1 = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                            ->getMockForAbstractClass();
        $aggregator2 = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                            ->getMockForAbstractClass();

        $this->subjectUnderTest->addSource($aggregator2);

        $this->assertFalse($this->subjectUnderTest->hasSource($aggregator1));
        $this->assertTrue($this->subjectUnderTest->hasSource($aggregator2));
    }

    public function testNameContainsPatternGetterReturnsNullByDefault()
    {
        $this->assertNull($this->subjectUnderTest->getNameContainsPattern());
    }

    public function testNameContainsPatternCanBeSet()
    {
        $this->subjectUnderTest->setNameContainsPattern('/./');
        $this->assertSame('/./', $this->subjectUnderTest->getNameContainsPattern());

        $this->subjectUnderTest->setNameContainsPattern('/a/');
        $this->assertSame('/a/', $this->subjectUnderTest->getNameContainsPattern());
    }

    public function testNameContainsPatternRejectsInvalidPatterns()
    {
        $this->setExpectedException('\noFlash\TorrentGhost\Exception\RegexException');
        $this->subjectUnderTest->setNameContainsPattern('grump grump');
    }

    public function testNameNotContainsPatternGetterReturnsNullByDefault()
    {
        $this->assertNull($this->subjectUnderTest->getNameNotContainsPattern());
    }

    public function testNameNotContainsPatternCanBeSet()
    {
        $this->subjectUnderTest->setNameNotContainsPattern('/./');
        $this->assertSame('/./', $this->subjectUnderTest->getNameNotContainsPattern());

        $this->subjectUnderTest->setNameNotContainsPattern('/a/');
        $this->assertSame('/a/', $this->subjectUnderTest->getNameNotContainsPattern());
    }

    public function testNameNotContainsPatternRejectsInvalidPatterns()
    {
        $this->setExpectedException('\noFlash\TorrentGhost\Exception\RegexException');
        $this->subjectUnderTest->setNameNotContainsPattern('grump grump');
    }

    public function testRuleIsConsideredValidIfAtLeastOneSourceWasAdded()
    {
        $aggregator = $this->getMockBuilder('\noFlash\TorrentGhost\Aggregator\AggregatorInterface')
                           ->getMockForAbstractClass();

        $this->assertFalse($this->subjectUnderTest->isValid(), 'Rule returned valid state without sources');

        $this->subjectUnderTest->addSource($aggregator);
        $this->assertTrue($this->subjectUnderTest->isValid(), 'Rule returned invalid state after adding source');

        $this->subjectUnderTest->removeSource($aggregator);
        $this->assertFalse(
            $this->subjectUnderTest->isValid(),
            'Rule returned valid state after removing previously added source'
        );
    }
}
