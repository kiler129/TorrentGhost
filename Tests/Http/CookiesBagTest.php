<?php

namespace noFlash\TorrentGhost\Tests\Http;


use noFlash\TorrentGhost\Http\CookiesBag;

class CookiesBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CookiesBag
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new CookiesBag();
    }

    public function testClassImplementsArrayAccessInterface()
    {
        $this->assertInstanceOf('\ArrayAccess', $this->subjectUnderTest);
    }

    public function testClassImplementsIteratorInterface()
    {
        $this->assertInstanceOf('\Iterator', $this->subjectUnderTest);
    }

    public function testCheckingForNonExistingElementUsingHasReturnsFalse()
    {
        $this->assertFalse($this->subjectUnderTest->has('example'));
        $this->assertFalse($this->subjectUnderTest->has('foobar'));
    }

    public function testCheckingForExistingValueUsingHasReturnsTrue()
    {
        $this->subjectUnderTest->set('tEsT', 'abc');
        $this->assertTrue($this->subjectUnderTest->has('tEsT'), '1st test failed for case-sensitive check.');
        $this->assertTrue($this->subjectUnderTest->has('TEST'), '1st test failed for uppercase check.');
        $this->assertTrue($this->subjectUnderTest->has('test'), '1st test failed for lowercase check.');
        $this->assertTrue($this->subjectUnderTest->has('TesT'), '1st test failed for mixed-case check.');

        $this->subjectUnderTest->set('DemO', 'meow');
        $this->assertTrue($this->subjectUnderTest->has('DemO'), '2nd test failed for case-sensitive check.');
        $this->assertTrue($this->subjectUnderTest->has('DEMO'), '2nd test failed for uppercase check.');
        $this->assertTrue($this->subjectUnderTest->has('demo'), '2nd test failed for lowercase check.');
        $this->assertTrue($this->subjectUnderTest->has('dEmO'), '2nd test failed for mixed-case check.');
    }

    public function testRetrievingUnknownValueThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Cannot get foobar - it does not exist');
        $this->subjectUnderTest->get('foobar');
    }

    public function testPreviouslySetValueCanBeRetrievedUsingGet()
    {
        $this->subjectUnderTest->set('fOo', 'baRrR');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('fOo'),
            '1st test failed for for case-sensitive check.');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('FOO'), '1st test failed for for uppercase check.');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('foo'), '1st test failed for for lowercase check.');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('fOO'), '1st test failed for for mixed-case check.');

        $this->subjectUnderTest->set('wOOhaA', 'noTin');
        $this->assertSame('noTin', $this->subjectUnderTest->get('wOOhaA'),
            '2nd test failed for for case-sensitive check.');
        $this->assertSame('noTin', $this->subjectUnderTest->get('WOOHAA'), '2nd test failed for for uppercase check.');
        $this->assertSame('noTin', $this->subjectUnderTest->get('woohaa'), '2nd test failed for for lowercase check.');
        $this->assertSame('noTin', $this->subjectUnderTest->get('WOohAa'), '2nd test failed for for mixed-case check.');
    }

    public function testPreviouslyAddedValueCanBeRetrievedUsingGet()
    {
        $this->subjectUnderTest->add('fOo', 'baRrR');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('fOo'),
            '1st test failed for for case-sensitive check.');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('FOO'), '1st test failed for for uppercase check.');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('foo'), '1st test failed for for lowercase check.');
        $this->assertSame('baRrR', $this->subjectUnderTest->get('fOO'), '1st test failed for for mixed-case check.');

        $this->subjectUnderTest->add('wOOhaA', 'noTin');
        $this->assertSame('noTin', $this->subjectUnderTest->get('wOOhaA'),
            '2nd test failed for for case-sensitive check.');
        $this->assertSame('noTin', $this->subjectUnderTest->get('WOOHAA'), '2nd test failed for for uppercase check.');
        $this->assertSame('noTin', $this->subjectUnderTest->get('woohaa'), '2nd test failed for for lowercase check.');
        $this->assertSame('noTin', $this->subjectUnderTest->get('WOohAa'), '2nd test failed for for mixed-case check.');
    }

    public function testAddingCookieWithTheSameNameThrowsException()
    {
        $this->subjectUnderTest->add('fOo', 'baRrR');

        $this->setExpectedException('\LogicException', 'Cannot add FOO - already exists');
        $this->subjectUnderTest->add('FOO', 'notin');
    }

    public function testSettingCookieWhichAlreadyExistsReplacesPreviousOneWhileUsingSet()
    {
        $this->subjectUnderTest->set('FooBar', 'baz');
        $this->subjectUnderTest->set('fOOBar', 'DOH');
        $this->assertSame('DOH', $this->subjectUnderTest->get('fOOBar'));
    }

    public function testDeletingNonExistingCookieUsingDeleteWillNotChangeObjectState()
    {
        $oldSut = serialize($this->subjectUnderTest);
        $this->subjectUnderTest->delete('fooooo');

        $this->assertSame($oldSut, serialize($this->subjectUnderTest));
    }

    public function testDeletingSingleCookieUsingDeleteWillDeleteOnlySpecifiedEntry()
    {
        $this->subjectUnderTest->set('foo', 'BAR');
        $this->subjectUnderTest->set('AAA', 'bbb');

        $this->subjectUnderTest->delete('AaA');

        $this->assertTrue($this->subjectUnderTest->has('foo'), 'Incorrect entry disappeared.');
        $this->assertFalse($this->subjectUnderTest->has('AAA'), 'Deleted entry still exists.');
    }

    public function testResettingBagWillRemoveAllEntries()
    {
        $this->subjectUnderTest->set('zieff', 'ough');
        $this->subjectUnderTest->set('bar', 'isEmpty');
        $this->subjectUnderTest->set('meow', 'grumpy');

        $this->subjectUnderTest->reset();

        $this->assertFalse($this->subjectUnderTest->has('zieff'), '1st entry still exists after reset.');
        $this->assertFalse($this->subjectUnderTest->has('bar'), '1st entry still exists after reset.');
        $this->assertFalse($this->subjectUnderTest->has('meow'), '1st entry still exists after reset.');
    }

    public function testCountReturnsZeroOnFreshObject()
    {
        $this->assertSame(0, $this->subjectUnderTest->count());
    }

    public function testCountReturnsCorrectNumberOfEntries()
    {
        $this->subjectUnderTest->add('foo', 'notbar');
        $this->assertSame(1, $this->subjectUnderTest->count(), 'Invalid count value after adding new cookie.');

        $this->subjectUnderTest->set('nothing', 'something');
        $this->assertSame(2, $this->subjectUnderTest->count(), 'Invalid count value after setting new cookie.');

        $this->subjectUnderTest->set('notHING', 'aaa');
        $this->assertSame(2, $this->subjectUnderTest->count(), 'Invalid count value after overwriting cookie.');

        $this->subjectUnderTest->delete('NOThing');
        $this->assertSame(1, $this->subjectUnderTest->count(), 'Invalid count value after deleting cookie.');

        $this->subjectUnderTest->delete('derp');
        $this->assertSame(1, $this->subjectUnderTest->count(),
            'Invalid count value after deleting non-existing cookie.');

        $this->subjectUnderTest->set('lalalalala', 'ough');
        $this->subjectUnderTest->reset();
        $this->assertSame(0, $this->subjectUnderTest->count(), 'Invalid count value after resetting bag.');
    }

    public function testCurrentReturnsFalseOnFreshObject()
    {
        $this->assertFalse($this->subjectUnderTest->current());
    }

    public function testCurrentReturnsFirstCookieAfterAddingSingleEntry()
    {
        $this->subjectUnderTest->set('derpu', 'dERp');
        $this->assertSame('dERp', $this->subjectUnderTest->current());
    }

    public function testNextWillAdvanceInternalPointerToNextCookie()
    {
        $this->subjectUnderTest->set('derpu', 'dERp');
        $this->subjectUnderTest->set('stop', 'start');

        $this->subjectUnderTest->next();
        $this->assertSame('start', $this->subjectUnderTest->current());

        $this->subjectUnderTest->next();
        $this->assertFalse($this->subjectUnderTest->current());
    }

    public function testKeyReturnsFalseOnFreshObject()
    {
        $this->assertFalse($this->subjectUnderTest->key());
    }

    public function testKeyReturnsCorrectCookieNameForCurrentPointer()
    {
        $this->subjectUnderTest->set('baz', 'bar');
        $this->subjectUnderTest->set('aAa', 'bbb');

        $this->subjectUnderTest->next();
        $this->assertSame('aAa', $this->subjectUnderTest->key());

        $this->subjectUnderTest->next();
        $this->assertFalse($this->subjectUnderTest->key());
    }

    public function testCallingValidOnFreshObjectReturnsFalse()
    {
        $this->assertFalse($this->subjectUnderTest->valid());
    }

    public function testValidReturnsTrueUnlessPointerIsSetOutOfBoundaries()
    {
        $this->subjectUnderTest->set('xyz', 'zzz');
        $this->subjectUnderTest->set('qwerty', 'aaa');

        $this->assertTrue($this->subjectUnderTest->valid());
        $this->subjectUnderTest->next();
        $this->assertTrue($this->subjectUnderTest->valid());
        $this->subjectUnderTest->next();
        $this->assertFalse($this->subjectUnderTest->valid());
        $this->subjectUnderTest->set('new', 'entry');
        $this->assertTrue($this->subjectUnderTest->valid());
    }

    public function testRewindSetsPointerToFirstElement()
    {
        $this->subjectUnderTest->set('grumpy', 'programmer');
        $this->subjectUnderTest->set('cat', 'moew');

        $this->subjectUnderTest->next();
        $this->subjectUnderTest->rewind();
        $this->assertSame('programmer', $this->subjectUnderTest->current());

        $this->subjectUnderTest->next();
        $this->subjectUnderTest->next();
        $this->subjectUnderTest->rewind();
        $this->assertSame('programmer', $this->subjectUnderTest->current());
    }

    public function testCheckingForNonExistingElementUsingOffsetExistsReturnsFalse()
    {
        $this->assertFalse($this->subjectUnderTest->offsetExists('glassofwine'));
        $this->assertFalse($this->subjectUnderTest->offsetExists('agirl'));
    }

    public function testCheckingForExistingValueReturnsTrue()
    {
        $this->subjectUnderTest->set('boooRRRRing', 'test');
        $this->assertTrue($this->subjectUnderTest->offsetExists('boooRRRRing'),
            '1st test failed for case-sensitive check.');
        $this->assertTrue($this->subjectUnderTest->offsetExists('BOOORRRRING'), '1st test failed for uppercase check.');
        $this->assertTrue($this->subjectUnderTest->offsetExists('booorrrring'), '1st test failed for lowercase check.');
        $this->assertTrue($this->subjectUnderTest->offsetExists('BoOoRrRrING'),
            '1st test failed for mixed-case check.');

        $this->subjectUnderTest->set('DeRp', 'foobaz');
        $this->assertTrue($this->subjectUnderTest->offsetExists('DeRp'), '2nd test failed for case-sensitive check.');
        $this->assertTrue($this->subjectUnderTest->offsetExists('DERP'), '2nd test failed for uppercase check.');
        $this->assertTrue($this->subjectUnderTest->offsetExists('derp'), '2nd test failed for lowercase check.');
        $this->assertTrue($this->subjectUnderTest->offsetExists('DErP'), '2nd test failed for mixed-case check.');
    }

    public function testPreviouslySetValueCanBeRetrievedUsingOffsetGet()
    {
        $this->subjectUnderTest->set('BzZzZ', 'aAaAaaaaa');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('BzZzZ'),
            '1st test failed for for case-sensitive check.');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('BZZZZ'),
            '1st test failed for for uppercase check.');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('bzzzz'),
            '1st test failed for for lowercase check.');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('BzzZz'),
            '1st test failed for for mixed-case check.');

        $this->subjectUnderTest->set('alOHa', 'teSTING');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('alOHa'),
            '2nd test failed for for case-sensitive check.');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('ALOHA'),
            '2nd test failed for for uppercase check.');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('aloha'),
            '2nd test failed for for lowercase check.');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('AlOha'),
            '2nd test failed for for mixed-case check.');
    }

    public function testPreviouslyAddedValueCanBeRetrievedUsingOffsetGet()
    {
        $this->subjectUnderTest->add('BzZzZ', 'aAaAaaaaa');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('BzZzZ'),
            '1st test failed for for case-sensitive check.');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('BZZZZ'),
            '1st test failed for for uppercase check.');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('bzzzz'),
            '1st test failed for for lowercase check.');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->offsetGet('BzzZz'),
            '1st test failed for for mixed-case check.');

        $this->subjectUnderTest->add('alOHa', 'teSTING');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('alOHa'),
            '2nd test failed for for case-sensitive check.');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('ALOHA'),
            '2nd test failed for for uppercase check.');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('aloha'),
            '2nd test failed for for lowercase check.');
        $this->assertSame('teSTING', $this->subjectUnderTest->offsetGet('AlOha'),
            '2nd test failed for for mixed-case check.');
    }

    public function testValueCanBeSetUsingOffsetSet()
    {
        $this->subjectUnderTest->offsetSet('BzZzZ', 'aAaAaaaaa');
        $this->assertSame('aAaAaaaaa', $this->subjectUnderTest->get('BzZzZ'));
    }

    public function testSettingCookieWhichAlreadyExistsReplacesPreviousOneWhileUsingOffsetSet()
    {
        $this->subjectUnderTest->offsetSet('FooBar', 'runout');
        $this->subjectUnderTest->offsetSet('fOOBar', 'leCherry');
        $this->assertSame('leCherry', $this->subjectUnderTest->get('fOOBar'));
    }

    public function testDeletingNonExistingCookieUsingOffsetUnsetWillNotChangeObjectState()
    {
        $oldSut = serialize($this->subjectUnderTest);
        $this->subjectUnderTest->delete('ooooof');

        $this->assertSame($oldSut, serialize($this->subjectUnderTest));
    }

    public function testDeletingSingleCookieUsingOffsetUnsetWillDeleteOnlySpecifiedEntry()
    {
        $this->subjectUnderTest->set('cold', 'beer');
        $this->subjectUnderTest->set('no', 'wine');

        $this->subjectUnderTest->offsetUnset('No');

        $this->assertTrue($this->subjectUnderTest->has('cold'), 'Incorrect entry disappeared.');
        $this->assertFalse($this->subjectUnderTest->has('no'), 'Deleted entry still exists.');
    }

    public function testCreatingObjectFromEmptyArrayReturnsEmptyObject()
    {
        $this->assertEquals($this->subjectUnderTest, CookiesBag::fromArray([]));
    }

    public function testCreatingObjectFromArrayWhereOneOfElementsIsNotArrayThrowsRuntimeException()
    {
        $testArray = [
            ['valid', 'element'],
            'invalid',
            ['next', 'valid']
        ];

        $this->setExpectedException('\RuntimeException', 'Unexpected value at offset 1 - array length invalid');
        CookiesBag::fromArray($testArray);
    }

    public function testCreatingObjectFromArrayWhereOneOfElementsIsArrayWithLessThanTwoElements()
    {
        $testArray = [
            ['invalid'],
            ['valid', 'element'],
            ['next', 'valid']
        ];

        $this->setExpectedException('\RuntimeException', 'Unexpected value at offset 0 - array length invalid');
        CookiesBag::fromArray($testArray);
    }

    public function testCreatingObjectFromArrayWhereOneOfElementsIsArrayWithMoreThanTwoElements()
    {
        $testArray = [
            ['valid', 'element'],
            ['next', 'valid'],
            ['invalid', 'value', 'derp']
        ];

        $this->setExpectedException('\RuntimeException', 'Unexpected value at offset 2 - array length invalid');
        CookiesBag::fromArray($testArray);
    }

    public function testCreatingObjectFromArrayWhereArrayContainsDuplicateEntriesResultsInLogicExceptionWrappedInsideRuntimeException(
    )
    {
        $testArray = [
            ['valid', 'element'],
            ['test', 'foobazzz'],
            ['valid', 'element']
        ];

        try {
            CookiesBag::fromArray($testArray);

        } catch(\Exception $e) {
            $this->assertInstanceOf('\RuntimeException', $e, 'Thrown exception type is invalid.');
            $this->assertSame('Failed to add cookie from offset 2', $e->getMessage(),
                'Thrown exeception message is invalid.');

            $this->assertInstanceOf('\LogicException', $e->getPrevious(),
                'Exception type inside thrown one is invalid.');

            return;
        }

        $this->fail('No exception was thrown - expected one.');
    }

    public function testCreatingObjectFromArrayUsingTypicalValuesReturnsObjectContainingPassedCookies()
    {
        $testArray = [
            ['valid', 'element'],
            ['tESt', 'ElEment']
        ];

        $cookiesBag = CookiesBag::fromArray($testArray);
        $this->assertInstanceOf('\noFlash\TorrentGhost\Http\CookiesBag', $cookiesBag, 'Invalid instance.');
        $this->assertSame('element', $cookiesBag->get('valid'), 'Failed to read first element.');
        $this->assertSame('ElEment', $cookiesBag->get('TEST'), 'Failed to read second element.');
    }

    public function testCreatingObjectFromArrayUsingTypicalValuesWhereElementsHaveAssocKeysReturnsObjectContainingPassedCookies(
    )
    {
        $testArray = [
            ['name' => 'tESt', 'text' => 'ElEment'],
            ['key' => 'valid', 'val' => 'element']
        ];

        $cookiesBag = CookiesBag::fromArray($testArray);
        $this->assertInstanceOf('\noFlash\TorrentGhost\Http\CookiesBag', $cookiesBag, 'Invalid instance.');
        $this->assertSame('element', $cookiesBag->get('valid'), 'Failed to read first element.');
        $this->assertSame('ElEment', $cookiesBag->get('TEST'), 'Failed to read second element.');
    }
}
