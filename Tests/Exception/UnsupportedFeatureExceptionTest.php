<?php

namespace noFlash\TorrentGhost\Tests\Exception;


class UnsupportedFeatureExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClassExtendsException()
    {
        $reflection = new \ReflectionClass('\noFlash\TorrentGhost\Exception\UnsupportedFeatureException');
        $this->assertTrue($reflection->isSubclassOf('\Exception'));
    }

}
