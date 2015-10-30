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

namespace noFlash\TorrentGhost\Test\Exception;

use noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException;

class UnknownConfigurationParameterExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClassExtendsRuntimeException()
    {
        $reflection = new \ReflectionClass('\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException');
        $this->assertTrue($reflection->isSubclassOf('\RuntimeException'));
    }

    public function testNewObjectIsConstructedWithZeroCode()
    {
        $exception = new UnknownConfigurationParameterException('', '');
        $this->assertSame(0, $exception->getCode());
    }

    public function testMessageIsPassedWithoutModification()
    {
        $exception = new UnknownConfigurationParameterException('Test message', '');
        $this->assertSame('Test message', $exception->getMessage());

        $exception = new UnknownConfigurationParameterException('Another test', '');
        $this->assertSame('Another test', $exception->getMessage());
    }

    public function testParameterNameIsPassedWithoutModification()
    {
        $exception = new UnknownConfigurationParameterException('', 'ParameterName');
        $this->assertSame('ParameterName', $exception->getParameterName());

        $exception = new UnknownConfigurationParameterException('', 'AnotherParam');
        $this->assertSame('AnotherParam', $exception->getParameterName());
    }
}
