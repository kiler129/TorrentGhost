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

class InvalidSourceExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClassExtendsException()
    {
        $reflection = new \ReflectionClass('\noFlash\TorrentGhost\Exception\InvalidSourceException');
        $this->assertTrue($reflection->isSubclassOf('\Exception'));
    }
}
