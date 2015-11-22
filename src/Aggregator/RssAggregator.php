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

namespace noFlash\TorrentGhost\Aggregator;


use GuzzleHttp\Psr7\Request;
use noFlash\TorrentGhost\Configuration\AggregatorAbstractConfiguration;
use noFlash\TorrentGhost\Configuration\RssAggregatorConfiguration;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use noFlash\TorrentGhost\Http\FetchJob;
use noFlash\TorrentGhost\Util\Blacklist;
use Psr\Log\LoggerInterface;

/**
 * Fetches data from RSS feed.
 */
class RssAggregator extends AbstractAggregator
{
    /**
     * @inheritDoc
     */
    const TYPE = 'rss';

    /**
     * @var RssAggregatorConfiguration
     */
    protected $configuration;

    /**
     * @var int Unix timestamp where RSS was downloaded last time
     */
    private $lastSuccessfulDownload = -1;

    /**
     * @var Blacklist
     */
    private $blacklist;

    /**
     * @var array
     */
    private $links;

    /**
     * @inheritDoc
     */
    public function __construct(
        TorrentGhostConfiguration $appConfiguration,
        AggregatorAbstractConfiguration $configuration,
        LoggerInterface $logger)
    {
        parent::__construct($appConfiguration, $configuration, $logger);

        if (!function_exists('simplexml_load_string')) {
            throw new \RuntimeException(
                'Your PHP interpreter was compiled without SimpleXML - you need it for RSS aggregator'
            );
        }
        libxml_disable_entity_loader(true); //Disable XXE for security reasons, by default it's disabled since 5.3.23
        libxml_use_internal_errors(true); //Prevent printing XML errors as E_WARNING

        $this->blacklist = new Blacklist($logger);
    }

    /**
     * @inheritDoc
     */
    public function ping()
    {
        if (time() - $this->lastSuccessfulDownload < $this->configuration->getInterval()) {
            $this->logger->debug($this->getName() . ' got ping but it is too early to do something, skipping');

            return;
        }

        if (!$this->configuration->isValid()) {
            $this->logger->warning($this->getName() . ' configuration is not valid - skipping');

            return;
        }

        $this->getNewDataFromOrigin();

        $this->logger->debug('Pool in ' . $this->getName() . ' now contains ' . count($this->links) . ' entries');
    }

    /**
     * @inheritDoc
     */
    public function getPingInterval()
    {
        return $this->configuration->getInterval();
    }

    /**
     * @inheritDoc
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @inheritDoc
     */
    public function flushLinksPool()
    {
        $this->links = [];
    }

    /**
     * @inheritDoc
     */
    public function extractLink($string)
    {
        return parent::extractLink(htmlspecialchars_decode($string));
    }

    /**
     * Triggers downloading new data from origin and parsing it.
     * Normally this method is executed by ping(), but you may decide to call it manually at any time.
     */
    public function getNewDataFromOrigin()
    {
        //TODO maybe add checksum to skip all operations if it's the same as previous?
        $feed = $this->fetchFeed($this->configuration->getUrl());
        if ($feed === false) {
            $this->logger->error($this->getName() . ': Failed to fetch feed from ' . $this->configuration->getUrl());

            return;
        }

        $this->logger->debug('Downloaded RSS for ' . $this->configuration->getUrl() . ' (' . strlen($feed) . ' bytes)');
        $this->lastSuccessfulDownload = time();

        $feed = $this->parseFeedXml($feed);
        $this->blacklist->applyBlacklist($feed);

        if (empty($feed)) {
            $this->logger->debug($this->getName() . ': No new links in feed');

            return;
        }

        foreach ($feed as $name => $link) {
            $this->links[] = ['name' => $name, 'link' => $link];
        }
    }

    /**
     * @param $url
     *
     * @return bool|string Returns false on error or downloaded RSS file content
     */
    private function fetchFeed($url)
    {
        $rssRequest = new Request('GET', $url);
        $fetchJob = new FetchJob($this->appConfiguration, $rssRequest);
        //TODO: Implement RSS size limit when FetchJob start supporting it

        try {
            if (!$fetchJob->execute()) {
                throw new \Exception('RSS download job failed for unknown reason');
            }

        } catch (\Exception $e) {
            $type = get_class($e);
            $this->logger->error($type . ': ' . $e->getMessage());

            return false;
        }

        return $fetchJob->getResponse()->getBody()->getContents();
    }

    /**
     * Parses given XML and returns array with titles & processed links.
     *
     * @param string $feed Valid RSS XML.
     *
     * @return array|false Key contains title, value contains processed link. It can also return bool false on error.
     *
     * @todo This method sucks...
     */
    public function parseFeedXml($feed)
    {
        $feed = simplexml_load_string($feed);
        if ($feed === false) {
            $this->logger->error('RSS XML parsing failed in ' . $this->getName(), libxml_get_errors());

            return false;
        }

        if (!isset($feed->channel->item)) {
            $this->logger->error($this->getName() . ' failed to extract items from XML');

            return false;
        }

        $nameTag = $this->configuration->getNameTagName();
        $linkTag = $this->configuration->getLinkTagName();
        $nameExtract = $this->configuration->getNameExtractPattern();
        $linkExtract = $this->configuration->getLinkExtractPattern();
        $linkTransform = $this->configuration->getLinkTransformPattern();

        $result = [];
        $counter = 0;
        foreach ($feed->channel->item as $item) {
            if (!isset($item->{$nameTag})) {
                $this->logger->warning(
                    'Unable to locate name tag <' . $nameTag . '> in ' . $this->getName() . ' for item#' . $counter .
                    '. Skipping item.'
                );
                continue;
            }

            if (!isset($item->{$linkTag})) {
                $this->logger->warning(
                    'Unable to locate name tag <' . $linkTag . '> in ' . $this->getName() . ' for item#' . $counter .
                    '. Skipping item.'
                );
                continue;
            }

            $nameMatches = [];
            if (preg_match($nameExtract, $item->{$nameTag}, $nameMatches) < 1 || !isset($nameMatches[1])) {
                $this->logger->warning(
                    'Extracting name from "' . $item->{$nameTag} . '" failed in ' . $this->getName() . ' for item#' .
                    $counter . '. Skipping item.'
                );
                continue;
            }

            if (isset($result[$nameMatches[1]])) {
                $this->logger->warning(
                    'Duplicate name "' . $item->{$nameTag} . '" in ' . $this->getName() . ' for item#' . $counter .
                    '. Skipping item.'
                );
                continue;
            }

            $linkMatches = [];
            if (preg_match($linkExtract, $item->{$linkTag}, $linkMatches) < 1 || !isset($linkMatches[1])) {
                $this->logger->warning(
                    'Extracting link from "' . $item->{$linkTag} . '" failed in ' . $this->getName() . ' for item#' .
                    $counter . '. Skipping item.'
                );
                continue;
            }

            if ($linkTransform !== null) {
                $linkMatches[1] = preg_replace($linkTransform[0], $linkTransform[1], $linkMatches[1]);
            }

            $result[$nameMatches[1]] = $linkMatches[1];
            $counter++;
        }

        return $result;
    }
}
