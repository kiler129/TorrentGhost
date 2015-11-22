<?php
/*
 * This file is part of TorrentGhost project.
 * You are using it at your own risk and you are fully responsible
 *  for everything that code will do.
 *
 * (c) Grzegorz Zdanowski <grzegorz@noflash.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace noFlash\TorrentGhost\Http;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use noFlash\TorrentGhost\Console\ConsoleApplication;
use noFlash\TorrentGhost\Exception\UnsupportedFeatureException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents file download job executed for each torrent
 *
 * @todo Implement file size limit for real ;)
 */
class FetchJob
{
    /**
     * @var TorrentGhostConfiguration
     */
    private $appConfig;

    /**
     * @var RequestInterface
     */
    private $fetchRequest;

    /**
     * @var resource cURL handle
     */
    private $cUrl;

    /**
     * PHP standard stream. StreamInterface wasn't used because it's incompatible with stream_select().
     *
     * @var resource
     */
    private $responseStream;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * FetchJob constructor.
     *
     * @param TorrentGhostConfiguration $appConfig
     * @param RequestInterface          $fileRequest
     */
    public function __construct(TorrentGhostConfiguration $appConfig, RequestInterface $fileRequest)
    {
        $this->appConfig = $appConfig;
        $this->fetchRequest = $fileRequest;
    }

    /**
     * Executes job.
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function execute()
    {
        $this->response = null;
        if ($this->responseStream === null ||
            !is_resource($this->responseStream)
        ) { //Temporary stream need to be created
            $this->responseStream = @fopen('php://temp/maxmemory:8388608', 'w'); //up to 8MiB is kept in memory

            if ($this->responseStream === false) {
                throw new \RuntimeException('Failed to open temporary stream for output - job cannot complete');
            }
        }

        $this->prepareCUrl();

        if (curl_exec($this->cUrl) === false) {
            throw new \RuntimeException('Job failed with error: ' . curl_error($this->cUrl));
        }

        $stream = new Stream($this->responseStream);
        $this->response = new Response(curl_getinfo($this->cUrl, CURLINFO_HTTP_CODE), [], $stream);

        return true;
    }

    /**
     * Prepares cUrl to execute the job
     *
     * @throws UnsupportedFeatureException
     * @throws \RuntimeException
     */
    private function prepareCUrl()
    {
        $this->cUrl = curl_init((string)$this->fetchRequest->getUri());
        if ($this->cUrl === false) {
            throw new \RuntimeException('Failed to initialize cURL - internal error');
        }

        $schemeType = substr($this->fetchRequest->getUri()->getScheme(), 0, 4);
        if ($schemeType === 'http') {
            $this->prepareHttpCUrl();

        } elseif ($schemeType !== 'ftp' && $schemeType !== 'ftps') {
            throw new \RuntimeException(
                'Your request uses unknown scheme ' . $this->fetchRequest->getUri()->getScheme() .
                ' (available schemes: http/https/ftp/ftps)'
            );
        }

        $acceptUnsafeCertsFlag = ($this->appConfig->isAcceptUnsafeCertificates()) ? 0 : 2; //I fucking love curl...
        curl_setopt_array(
            $this->cUrl,
            [
                CURLOPT_RETURNTRANSFER => 1, //Note: It have to be set BEFORE CURLOPT_FILE
                CURLOPT_FILE           => $this->responseStream,
                CURLOPT_HEADER         => 0,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => $acceptUnsafeCertsFlag,
                CURLOPT_SSL_VERIFYHOST => $acceptUnsafeCertsFlag,
            ]
        );

        $userInfo = $this->fetchRequest->getUri()->getUserInfo();
        if (!empty($userInfo)) {
            curl_setopt_array($this->cUrl, [CURLOPT_HTTPAUTH => CURLAUTH_ANY, CURLOPT_USERPWD => $userInfo]);
        }
    }

    /**
     * Assigns options specific to handling HTTP requests.
     * NOTE: THIS METHOD SHOULD BE CALLED ONLY FROM prepareCUrl()!
     *
     * @throws UnsupportedFeatureException Thrown if POST method was requested.
     */
    private function prepareHttpCUrl()
    {
        if ($this->fetchRequest->getMethod() !== 'GET') {
            throw new UnsupportedFeatureException('Request other than GET are not supported');
        }

        $headers = [];
        foreach ($this->fetchRequest->getHeaders() as $name => $values) {
            $headers[] = $name . ": " . implode(", ", $values);
        }

        curl_setopt_array(
            $this->cUrl,
            [
                CURLOPT_AUTOREFERER    => 1,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_FAILONERROR    => 1,
                CURLOPT_HTTP_VERSION   => ($this->fetchRequest->getProtocolVersion() ===
                                           '1.0') ? CURL_HTTP_VERSION_1_0 : CURL_HTTP_VERSION_1_1,
                CURLOPT_USERAGENT      => ($this->fetchRequest->getHeader('User-Agent') ?: $this->getDefaultUserAgent(
                )),
                CURLOPT_HTTPHEADER     => $headers,
            ]
        );
    }

    /**
     * @return string User agent string, e.g. Mozilla/5.0 (CLI; WINNT) TorrentGhost/1.0.0-dev
     */
    private function getDefaultUserAgent()
    {
        return 'Mozilla/5.0 (CLI; ' . PHP_OS . ') TorrentGhost/' . ConsoleApplication::VERSION;
    }

    /**
     * Every execution of job possibly produce some output which is saved to stream. This method will return that
     * stream. Null means that there's currently no stream and one will be generated upon execution of the job.
     *
     * @return resource|null PHP standard stream. StreamInterface wasn't used because it's incompatible with
     *     stream_select().
     */
    public function getResponseStream()
    {
        return $this->responseStream;
    }

    /**
     * Every execution of job possibly produce some output which is saved to stream. This method will set desired
     * stream. If called with null stream will be generated at runtime.
     *
     * @param resource|null $responseStream PHP standard stream. StreamInterface wasn't used because it's incompatible
     *                                      with stream_select().
     *
     * @throws \InvalidArgumentException Will be thrown if invalid argument type was passed.
     */
    public function setResponseStream($responseStream)
    {
        if (!is_resource($responseStream) && $responseStream !== null) {
            throw new \InvalidArgumentException('Response stream need to be resource or null');
        }

        $this->responseStream = $responseStream;
    }

    /**
     * Returns job response.
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Clears all mess the job made ;)
     */
    public function __destruct()
    {
        if (is_resource($this->cUrl)) {
            curl_close($this->cUrl);
        }
    }
}
