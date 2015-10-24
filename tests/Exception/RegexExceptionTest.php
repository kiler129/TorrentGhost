<?php

namespace noFlash\TorrentGhost\Test\Exception;


use noFlash\TorrentGhost\Exception\RegexException;

class RegexExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClassExtendsException()
    {
        $reflection = new \ReflectionClass('\noFlash\TorrentGhost\Exception\RegexException');
        $this->assertTrue($reflection->isSubclassOf('\Exception'));
    }

    public function testNewObjectIsConstructedWithZeroCode()
    {
        $exception = new RegexException('', '');
        $this->assertSame(0, $exception->getCode());
    }

    public function testExceptionMessageStartsWithProvidedMessage()
    {
        $exception = new RegexException('This is test', '');
        $this->assertStringStartsWith('This is test', $exception->getMessage());
    }

    public function testExceptionMessageEndsWithErrorMessageExplanationIfErrorOccured()
    {
        if (preg_last_error() !== PREG_NO_ERROR) {
            $this->fail('Test runtime error - preg_last_error is not clear, it contains error code of ' . preg_last_error());
        }

        $exception = new RegexException('', 'REGEX');
        $this->assertStringEndsWith('Pattern REGEX cannot be used.', $exception->getMessage());
    }
}
