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
        $aggregator = 'testAggregator';

        $this->assertTrue($this->subjectUnderTest->addSource($aggregator), 'Failed to add source');
        $this->assertSame(
            [$aggregator],
            $this->subjectUnderTest->getSources(),
            'Source is not available after addition'
        );
    }

    public function testMoreThanSingleSourceCanBeAdded()
    {
        $aggregator1 = 'exampleFirstAggregator';
        $aggregator2 = 'exampleSecondAggregator';

        $this->assertTrue($this->subjectUnderTest->addSource($aggregator1), 'Failed to add 1st source');
        $this->assertTrue($this->subjectUnderTest->addSource($aggregator2), 'Failed to add 2md source');

        $availableSources = $this->subjectUnderTest->getSources();
        $this->assertContains($aggregator1, $availableSources, '1st source is not available after addition');
        $this->assertContains($aggregator2, $availableSources, '2nd source is not available after addition');
    }

    public function testTheSameSourceCanBeAddedOnlyOnce()
    {
        $aggregator = 'fooBarAggregator';

        $this->assertTrue($this->subjectUnderTest->addSource($aggregator), 'Failed to add source for the first time');

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\InvalidSourceException',
            'Cannot add source fooBarAggregator - it is already in'
        );
        $this->subjectUnderTest->addSource($aggregator);
    }

    public function testAddSourceRejectsObjects()
    {
        $this->setExpectedException(
            (PHP_MAJOR_VERSION >= 7) ? '\TypeError' : '\InvalidArgumentException',
            'Expected string - got object'
        );

        $this->subjectUnderTest->addSource(new \stdClass());
    }

    public function testAddSourceRejectsArrays()
    {
        $this->setExpectedException(
            (PHP_MAJOR_VERSION >= 7) ? '\TypeError' : '\InvalidArgumentException',
            'Expected string - got array'
        );

        $this->subjectUnderTest->addSource([]);
    }

    public function testSourceCanBeRemoved()
    {
        $aggregator = 'fooSource';

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
        $aggregator1 = '1stSource';
        $aggregator2 = '2ndSource';

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
        $aggregator = 'ghostSource';

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\InvalidSourceException',
            'Cannot remove source ghostSource - it was not added to'
        );
        $this->subjectUnderTest->removeSource($aggregator);
    }

    public function testRemoveSourceRejectsObjects()
    {
        $this->setExpectedException(
            (PHP_MAJOR_VERSION >= 7) ? '\TypeError' : '\InvalidArgumentException',
            'Expected string - got object'
        );

        $this->subjectUnderTest->removeSource(new \stdClass());
    }

    public function testRemoveSourceRejectsArrays()
    {
        $this->setExpectedException(
            (PHP_MAJOR_VERSION >= 7) ? '\TypeError' : '\InvalidArgumentException',
            'Expected string - got array'
        );

        $this->subjectUnderTest->removeSource([]);
    }

    public function testHasSourceCorrectlyLocatesSources()
    {
        $aggregator1 = 'derpAggregator';
        $aggregator2 = 'derpSource';

        $this->subjectUnderTest->addSource($aggregator2);

        $this->assertFalse($this->subjectUnderTest->hasSource($aggregator1));
        $this->assertTrue($this->subjectUnderTest->hasSource($aggregator2));
    }

    public function testHasSourceRejectsObjects()
    {
        $this->setExpectedException(
            (PHP_MAJOR_VERSION >= 7) ? '\TypeError' : '\InvalidArgumentException',
            'Expected string - got object'
        );

        $this->subjectUnderTest->hasSource(new \stdClass());
    }

    public function testHasSourceRejectsArrays()
    {
        $this->setExpectedException(
            (PHP_MAJOR_VERSION >= 7) ? '\TypeError' : '\InvalidArgumentException',
            'Expected string - got array'
        );

        $this->subjectUnderTest->hasSource([]);
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

    public function testNameContainsPatternAcceptsNull()
    {
        $this->subjectUnderTest->setNameContainsPattern('/./');
        $this->subjectUnderTest->setNameContainsPattern(null);
        $this->assertNull($this->subjectUnderTest->getNameContainsPattern());
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

    public function testNameNotContainsPatternAcceptsNull()
    {
        $this->subjectUnderTest->setNameNotContainsPattern('/./');
        $this->subjectUnderTest->setNameNotContainsPattern(null);
        $this->assertNull($this->subjectUnderTest->getNameNotContainsPattern());
    }

    public function testRuleIsConsideredValidIfAtLeastOneSourceWasAdded()
    {
        $aggregator = 'christmasSource';

        $this->assertFalse($this->subjectUnderTest->isValid(), 'Rule returned valid state without sources');

        $this->subjectUnderTest->addSource($aggregator);
        $this->assertTrue($this->subjectUnderTest->isValid(), 'Rule returned invalid state after adding source');

        $this->subjectUnderTest->removeSource($aggregator);
        $this->assertFalse(
            $this->subjectUnderTest->isValid(),
            'Rule returned valid state after removing previously added source'
        );
    }

    public function testCheckNameReturnsTrueForAnyStringIfBothMatchAndNotMatchPatternsAreSetToNull()
    {
        $this->subjectUnderTest->setNameContainsPattern(null);
        $this->subjectUnderTest->setNameNotContainsPattern(null);

        $this->assertTrue($this->subjectUnderTest->checkName('test'));
        $this->assertTrue($this->subjectUnderTest->checkName('foo bar'));
        $this->assertTrue($this->subjectUnderTest->checkName('☃'));
    }

    public function testCheckNameValidatesNameAccordingToNameContainsPattern()
    {
        $this->subjectUnderTest->setNameContainsPattern('/^[A-Fx0-9]+$/');

        $this->assertTrue($this->subjectUnderTest->checkName('0xDBF'));
        $this->assertTrue($this->subjectUnderTest->checkName('ABC'));
        $this->assertFalse($this->subjectUnderTest->checkName('ZZz'));
        $this->assertFalse($this->subjectUnderTest->checkName('abc'));
    }

    public function testCheckNameValidatesNameAccordingToNameNotContainsPattern()
    {
        $this->subjectUnderTest->setNameNotContainsPattern('/^[a-z]+$/');

        $this->assertTrue($this->subjectUnderTest->checkName('BOO'));
        $this->assertTrue($this->subjectUnderTest->checkName('BAR1'));
        $this->assertFalse($this->subjectUnderTest->checkName('foo'));
        $this->assertFalse($this->subjectUnderTest->checkName('bzz'));
    }

    public function testCheckNameValidatesNameAccordingToBothNameContainsAndNameNotContainsPatterns()
    {
        $this->subjectUnderTest->setNameContainsPattern('/^[a-z]+$/');
        $this->subjectUnderTest->setNameNotContainsPattern('/foo|x/');

        $this->assertTrue($this->subjectUnderTest->checkName('test'));
        $this->assertTrue($this->subjectUnderTest->checkName('blah'));
        $this->assertFalse($this->subjectUnderTest->checkName('barfoobazz'));
        $this->assertFalse($this->subjectUnderTest->checkName('mixer'));
    }
}
