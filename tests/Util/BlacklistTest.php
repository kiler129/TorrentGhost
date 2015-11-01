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

namespace noFlash\TorrentGhost\Test\Util;

use noFlash\TorrentGhost\Util\Blacklist;
use Psr\Log\LoggerInterface;

class BlacklistTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Blacklist
     */
    private $subjectUnderTest;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function setUp()
    {
        $logger = $this->getMockForAbstractClass('Psr\Log\LoggerInterface');

        $this->subjectUnderTest = new Blacklist($logger);
    }

    public function testBlacklistIsEmptyOnFreshObject()
    {
        $this->assertSame([], $this->subjectUnderTest->getBlacklist());
    }

    public function testBlacklistElementCanBeAdded()
    {
        $this->subjectUnderTest->addBlacklistEntry('foo', 'bar');
        $this->assertSame(
            ['foo' => 'bar'],
            $this->subjectUnderTest->getBlacklist(),
            'Invalid result with first element'
        );

        $this->subjectUnderTest->addBlacklistEntry('baz', 'moew');
        $this->assertSame(
            ['foo' => 'bar', 'baz' => 'moew'],
            $this->subjectUnderTest->getBlacklist(),
            'Invalid result with second element'
        );
    }

    public function testAddingAlreadyExistingElementThrowsException()
    {
        $this->subjectUnderTest->addBlacklistEntry('a', 'b');
        $this->subjectUnderTest->addBlacklistEntry('c', 'd');

        $this->setExpectedException(
            '\LogicException',
            'Cannot add blacklist entry with key "a" - entry already exists.'
        );
        $this->subjectUnderTest->addBlacklistEntry('a', 'd');
    }

    public function testBlacklistElementValueCanBeOverwritten()
    {
        $this->subjectUnderTest->addBlacklistEntry('a', 'b');
        $this->subjectUnderTest->addBlacklistEntry('e', 'f');
        $this->subjectUnderTest->setBlacklistEntry('a', 'd');

        $this->assertSame(['a' => 'd', 'e' => 'f'], $this->subjectUnderTest->getBlacklist());
    }

    public function testBlacklistCanBeOverwritten()
    {
        $this->subjectUnderTest->addBlacklistEntry('moew', 'foo');
        $this->subjectUnderTest->addBlacklistEntry('bar', 'baz');
        $this->subjectUnderTest->setBlacklist(['aa' => 'bb']);

        $this->assertSame(['aa' => 'bb'], $this->subjectUnderTest->getBlacklist());
    }

    public function testBlacklistSetterRejectsNonArrayValues()
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

        $this->subjectUnderTest->setBlacklist('test');
    }

    public function testBlacklistElementsCanBeRemoved()
    {
        $this->subjectUnderTest->addBlacklistEntry('a', 'b');
        $this->subjectUnderTest->addBlacklistEntry('c', 'd');
        $this->subjectUnderTest->addBlacklistEntry('e', 'f');

        $this->subjectUnderTest->removeBlacklistEntry('c');
        $this->assertSame(['a' => 'b', 'e' => 'f'], $this->subjectUnderTest->getBlacklist());

        $this->subjectUnderTest->removeBlacklistEntry('a');
        $this->assertSame(['e' => 'f'], $this->subjectUnderTest->getBlacklist());

        $this->subjectUnderTest->removeBlacklistEntry('e');
        $this->assertSame([], $this->subjectUnderTest->getBlacklist());
    }

    public function testRemovingNonExistingElementWillThrowException()
    {
        $this->setExpectedException(
            '\LogicException',
            'Cannot remove blacklist entry identified by key "foo" - entry does not exist.'
        );
        $this->subjectUnderTest->removeBlacklistEntry('foo');
    }

    public function testHasBlacklistEntryWillCorrectlyDetectExistingElement()
    {
        $this->subjectUnderTest->setBlacklist(['a' => 'x']);
        $this->assertTrue($this->subjectUnderTest->hasBlacklistEntry('a', 'x'));
    }

    public function testHasBlacklistEntryWillCorrectlyDetectNonExistingElement()
    {
        $this->subjectUnderTest->setBlacklist(['a' => 'x']);
        $this->assertFalse($this->subjectUnderTest->hasBlacklistEntry('x', 'x'));
    }

    public function testHasBlacklistEntryWillIgnoreValueIfValueParameterWasSetToNullOrDefault()
    {
        $this->subjectUnderTest->setBlacklist(['a' => 'x', 'b' => 3]);
        $this->assertTrue(
            $this->subjectUnderTest->hasBlacklistEntry('a', null),
            'Existing element was not detected if value parameter was set to null'
        );
        $this->assertTrue(
            $this->subjectUnderTest->hasBlacklistEntry('a'),
            'Existing element was not detected if value parameter was not set [default]'
        );

        $this->assertFalse(
            $this->subjectUnderTest->hasBlacklistEntry('a', 'b'),
            'Existing element was detected even if value was different'
        );
        $this->assertFalse(
            $this->subjectUnderTest->hasBlacklistEntry('b', '3'),
            'Existing element was detected if value type was different'
        );
    }

    public function testBlacklistCanBeCleared()
    {
        $this->subjectUnderTest->setBlacklist(['a' => 'b']);
        $this->subjectUnderTest->addBlacklistEntry('c', 'd');
        $this->subjectUnderTest->setBlacklistEntry('e', 'f');

        $this->subjectUnderTest->clearBlacklist();
        $this->assertSame([], $this->subjectUnderTest->getBlacklist());
    }

    public function testElementsAreAddedToBlacklistWhileApplyingBlacklistOnData()
    {
        $data = ['foo' => 'bzz', 'a' => 'y'];

        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertSame(['foo' => 'bzz', 'a' => 'y'], $this->subjectUnderTest->getBlacklist());
    }

    public function testElementsExitingInBlacklistAreRemovedFromDataWhileApplyingBlacklist()
    {
        $data = ['foo' => 'bzz', 'a' => 'y'];

        $this->subjectUnderTest->applyBlacklist($data); //This will add entries from $data to blacklist
        $this->assertSame(['foo' => 'bzz', 'a' => 'y'], $data, 'Data array got modified after 1st call');

        $data['z'] = 'test';
        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertSame(['z' => 'test'], $data, 'After 2nd call data array is not correct');

        $data = ['a' => 'y', 'foo' => 'bzz', 'z' => 'test'];
        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertSame([], $data, 'After 3rd call data array is not correct');
    }

    public function testDebugMessageIsLoggedWhileElementIsRemovedFromBlacklistWhileApplyingItOnData()
    {
        $data = ['foo' => 'bzz', 'a' => 'y'];

        $this->subjectUnderTest->applyBlacklist($data); //This will add entries from $data to blacklist
        $this->assertSame(['foo' => 'bzz', 'a' => 'y'], $data, 'Data array got modified after 1st call');

        $data['z'] = 'test';
        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertSame(['z' => 'test'], $data, 'After 2nd call data array is not correct');

        $data = ['a' => 'y', 'foo' => 'bzz', 'z' => 'test'];
        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertSame([], $data, 'After 3rd call data array is not correct');
    }

    public function testElementExistingInBlacklistButWithDifferentValueIsNotRemovedFromDataArrayWhileApplyingBlacklist()
    {
        $data = ['a' => 'b', 'c' => 'd'];

        $this->subjectUnderTest->applyBlacklist($data);

        $data['c'] = 'x';
        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertSame(['c' => 'x'], $data);
    }

    public function testElementsNoLongerPresentInDataIsRemovedFromBlacklistWhileApplyingBlacklist()
    {
        $data = ['z' => 'a', 'x' => 'b'];

        $this->subjectUnderTest->applyBlacklist($data);

        unset($data['x']);
        $this->subjectUnderTest->applyBlacklist($data);
        $this->assertFalse($this->subjectUnderTest->hasBlacklistEntry('x'));
    }

    public function testValueOfElementWhichChangedItsValueIsUpdatedInBlacklistArray()
    {
        $data = ['o' => 'p', 'y' => 'u'];

        $this->subjectUnderTest->applyBlacklist($data); //This will add entries from $data to blacklist
        $data['o'] = 'x';

        $this->subjectUnderTest->applyBlacklist($data);
        $blacklist = $this->subjectUnderTest->getBlacklist();
        $this->assertArrayHasKey('o', $blacklist);
        $this->assertSame('x', $blacklist['o']);
    }
}
