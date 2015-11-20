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

class ConfigurationExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClassExtendsRuntimeException()
    {
        $reflection = new \ReflectionClass('noFlash\TorrentGhost\Exception\ConfigurationException');
        $this->assertTrue($reflection->isSubclassOf('\RuntimeException'));
    }
}
