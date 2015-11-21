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

namespace noFlash\TorrentGhost\Test\Http;

use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use noFlash\TorrentGhost\Http\FetchJob;
use phpmock\phpunit\PHPMock;
use Psr\Http\Message\RequestInterface;

/**
 * @todo Test params from prepareCUrl & prepareHttpCUrl
 */
class FetchJobTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    /**
     * Namespace used to create function mocks
     */
    const SUT_NAMESPACE = '\noFlash\TorrentGhost\Http';

    /**
     * @var TorrentGhostConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appConfiguration;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var FetchJob
     */
    private $subjectUnderTest;

    /**
     * @beforeClass
     */
    public static function definePHPMocks()
    {
        self::defineFunctionMock(self::SUT_NAMESPACE, "curl_getinfo");
        self::defineFunctionMock(self::SUT_NAMESPACE, "curl_init");
    }

    public function setUp()
    {
        $this->appConfiguration = $this->appConfiguration = $this->getMockBuilder(
            '\noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration'
        )->getMock();

        $this->request = $this->getMockBuilder(
            'Psr\Http\Message\RequestInterface'
        )->getMockForAbstractClass();

        $this->subjectUnderTest = new FetchJob($this->appConfiguration, $this->request);
    }

    public function testResponseIsRemovedOnExecution()
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);

        $execMock = $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec');
        $execMock->expects($this->at(0))->willReturn(true);
        $execMock->expects($this->at(1))->willReturn(false);

        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->any())->willReturn(true);

        $this->assertTrue($this->subjectUnderTest->execute(), 'First execution (preparation) failed');
        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $this->subjectUnderTest->getResponse());

        try {
            $this->subjectUnderTest->execute();
        } catch (\Exception $e) {
            //That exception is expected due to curl_exec() failure, but setExpectedException cannot be used due to
            // assertion below
        }

        $this->assertNull($this->subjectUnderTest->getResponse());
    }

    public function testWhenOutputStreamIsSetItIsUsedAndNotChangedOnExecution()
    {
        $resource = fopen('php://temp', 'w+');
        $this->assertInternalType('resource', $resource, 'Failed to create valid resource for test');

        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);

        $execMock = $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec');
        $execMock->expects($this->any())->willReturn(true);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->once())->with(
            $this->anything(),
            $this->callback(
                function ($arg) use ($resource) {
                    return isset($arg[CURLOPT_FILE]) && $arg[CURLOPT_FILE] === $resource;
                }
            )
        )->willReturn(true);

        $this->subjectUnderTest->setResponseStream($resource);
        $this->assertTrue($this->subjectUnderTest->execute(), 'Execution failed');
        $this->assertSame($resource, $this->subjectUnderTest->getResponseStream());
        $this->assertSame($resource, $this->subjectUnderTest->getResponse()->getBody()->detach());
    }

    public function testWhenOutputStreamIsNotSetTemporaryOneIsCreatedOnExecution()
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);

        $execMock = $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec');
        $execMock->expects($this->any())->willReturn(true);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->once())->with(
            $this->anything(),
            $this->callback(
                function ($arg) {
                    return isset($arg[CURLOPT_FILE]) && is_resource($arg[CURLOPT_FILE]);
                }
            )
        )->willReturn(true);

        $this->assertTrue($this->subjectUnderTest->execute(), 'Execution failed');
        $this->assertInternalType('resource', $this->subjectUnderTest->getResponseStream());
        $this->assertInternalType('resource', $this->subjectUnderTest->getResponse()->getBody()->detach());
    }

    public function testWhenOutputStreamIsNoLongerAValidResourceNewTemporaryOneIsCreatedOnExecution()
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);

        $execMock = $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec');
        $execMock->expects($this->any())->willReturn(true);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->any())->willReturn(true);

        $this->assertTrue($this->subjectUnderTest->execute(), '1st execution failed');
        $oldStream = $this->subjectUnderTest->getResponseStream();
        fclose($oldStream);

        $this->assertTrue($this->subjectUnderTest->execute(), '2nd execution failed');
        $this->assertInternalType('resource', $this->subjectUnderTest->getResponseStream());
        $this->assertInternalType('resource', $this->subjectUnderTest->getResponse()->getBody()->detach());
    }

    public function testRuntimeExceptionIsThrownIfCurlExecFailed()
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);

        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec')->expects($this->once())->willReturn(false);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->any())->willReturn(true);

        $this->setExpectedException('\RuntimeException', 'Job failed with error: ');
        $this->subjectUnderTest->execute();
    }

    public function testGeneratedResponseContainsHttpCodeReturnedByCurl()
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec')->expects($this->any())->willReturn(true);

        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->any())->willReturn(true);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_getinfo')->expects($this->exactly(3))->with(
            $this->anything(),
            CURLINFO_HTTP_CODE
        )->will($this->onConsecutiveCalls(200, 403, 418));


        $this->subjectUnderTest->execute();
        $this->assertSame(200, $this->subjectUnderTest->getResponse()->getStatusCode());

        $this->subjectUnderTest->execute();
        $this->assertSame(403, $this->subjectUnderTest->getResponse()->getStatusCode());

        $this->subjectUnderTest->execute();
        $this->assertSame(418, $this->subjectUnderTest->getResponse()->getStatusCode());
    }

    public function validUrisProvider()
    {
        return [
            ['ftp', 'ftp://example.com/aaa'],
            ['ftps', 'ftps://example.org/bbb'],
            ['http', 'http://example.tld/ccc'],
            ['https', 'https://example.net/ddd']
        ];
    }

    /**
     * @dataProvider validUrisProvider
     */
    public function testCurlIsInitializedWithUriSpecifiedInsideRequest($schema, $uri)
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn($schema);
        $uriMock->method('__toString')->willReturn($uri);
        $this->request->method('getUri')->willReturn($uriMock);
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getHeaders')->willReturn([]);

        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_init')->expects($this->once())->with($uri);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_exec')->expects($this->any())->willReturn(true);
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_getinfo')->expects($this->any());
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_setopt_array')->expects($this->any())->willReturn(true);

        $this->subjectUnderTest->execute();
    }

    public function testRuntimeExceptionIsThrownIfCurlInitFailed()
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn('ftp');
        $this->request->method('getUri')->willReturn($uriMock);

        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_init')->expects($this->once())->willReturn(false);

        $this->setExpectedException('\RuntimeException', 'Failed to initialize cURL - internal error');
        $this->subjectUnderTest->execute();
    }

    public function unknownSchemasProvider()
    {
        return [
            ['ft'],
            ['file'],
            ['php'],
            ['rss'],
            ['torrent']
        ];
    }

    /**
     * @dataProvider unknownSchemasProvider
     */
    public function testUnknownSchemasAreRejected($scheme)
    {
        $uriMock = $this->getMockForAbstractClass('\Psr\Http\Message\UriInterface');
        $uriMock->method('getScheme')->willReturn($scheme);
        $this->request->method('getUri')->willReturn($uriMock);

        $this->setExpectedException(
            '\RuntimeException',
            "Your request uses unknown scheme $scheme (available schemes: http/https/ftp/ftps)"
        );
        $this->subjectUnderTest->execute();
    }

    public function testResponseStreamIsNullOnFreshObject()
    {
        $this->assertNull($this->subjectUnderTest->getResponseStream());
    }

    public function testResponseStreamSetterAcceptsValidResource()
    {
        $resource = fopen('php://temp', 'w+');
        $this->assertInternalType('resource', $resource, 'Failed to create valid resource for test');

        $this->subjectUnderTest->setResponseStream($resource);
        $this->assertSame($resource, $this->subjectUnderTest->getResponseStream());
    }

    public function testResponseStreamSetterAcceptsNull()
    {
        $resource = fopen('php://temp', 'w+');
        $this->assertInternalType('resource', $resource, 'Failed to create valid resource for test');

        $this->subjectUnderTest->setResponseStream($resource);
        $this->subjectUnderTest->setResponseStream(null);
        $this->assertNull($this->subjectUnderTest->getResponseStream());
    }

    public function testGetResponseReturnsNullOnFreshObject()
    {
        $this->assertNull($this->subjectUnderTest->getResponse());
    }

    public function testDestructorWillNotCallCurlCloseOnFreshObject()
    {
        $this->getFunctionMock(self::SUT_NAMESPACE, 'curl_close')->expects($this->never());
        $this->subjectUnderTest->__destruct();
    }
}
