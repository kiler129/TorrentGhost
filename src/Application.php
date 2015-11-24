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

namespace noFlash\TorrentGhost;

use noFlash\TorrentGhost\Aggregator\AggregatorInterface;
use noFlash\TorrentGhost\Configuration\ConfigurationProvider;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use noFlash\TorrentGhost\Console\ConsoleApplication;
use noFlash\TorrentGhost\Exception\ConfigurationException;
use noFlash\TorrentGhost\Http\FetchJob;
use noFlash\TorrentGhost\Rule\RuleInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Core application responsible for sourcing all other elements with data
 *
 * @todo That core requires refactor :(
 */
class Application
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigurationProvider
     */
    private $configurationProvider;

    /**
     * @var TorrentGhostConfiguration
     */
    private $appConfiguration;

    /**
     * @var AggregatorInterface[]
     */
    private $sources = [];

    /**
     * @var RuleInterface[]
     */
    private $downloadRules = [];

    /**
     * @var int|null Every aggregator can specify time interval for pinging it. This value represents calculated value
     *               for every sources (using GCD algorithm).
     *               It can be null if every aggregator decide to not specify any interval.
     */
    private $mainLoopInterval = null;

    /**
     * @var int
     */
    private $lastLoopTime = 0;


    /**
     * Application constructor.
     *
     * @param array           $configuration Whole configuration (I know it's horrible, it's gonna be changed)
     * @param LoggerInterface $logger
     */
    public function __construct(ConfigurationProvider $configurationProvider, LoggerInterface $logger)
    {
        $this->configurationProvider = $configurationProvider;
        $this->appConfiguration = $this->configurationProvider->getApplicationConfiguration();
        $this->logger = $logger;

        $this->logger->debug('Core init finished');
    }

    /**
     * Returns all loaded sources.
     *
     * @return AggregatorInterface[]
     * @internal
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Returns all download rules.
     *
     * @return RuleInterface[]
     * @internal
     */
    public function getDownloadRules()
    {
        return $this->downloadRules;
    }

    /**
     * @return int|null
     */
    public function getMainLoopInterval()
    {
        return $this->mainLoopInterval;
    }

    public function run()
    {
        $this->initializeSources();
        $this->initializeRules();
        $this->recalculateMainLoopInterval();
        $this->runMainLoop();
    }

    private function initializeSources()
    {
        $this->logger->debug('Initialization of sources started');
        $this->sources = [];

        $sources = $this->configurationProvider->getAggregatorsNames();
        $this->logger->debug('Found ' . count($sources) . ' aggregator(s)');
        foreach ($sources as $sourceName) {
            $this->logger->debug("Initializing $sourceName aggregator");

            //TODO ConfigurationProvider should be passed to aggregator and than aggregator should configure itself
            $config = $this->configurationProvider->getAggregatorConfiguration(
                $sourceName
            );

            if ($config === false) {
                throw new ConfigurationException(
                    'Failed to configure ' . $sourceName . ' aggregator. Did you defined type for it?'
                );
            }

            $sourceClass = $this->configurationProvider->getAggregatorClassNameByAggregatorName($sourceName);
            if ($sourceClass === false) {
                throw new \RuntimeException("Failed to locate object for $sourceName aggregator! (THIS IS A BUG)");
            }

            $this->sources[] = new $sourceClass($this->appConfiguration, $config, $this->logger);
            $this->logger->debug("$sourceName aggregator initialized");
        }
        $this->logger->debug('All aggregators ready');
    }

    private function initializeRules()
    {
        $this->logger->debug('Initialization of rules started');
        $this->downloadRules = [];

        $rules = $this->configurationProvider->getRulesNames();
        $this->logger->debug('Found ' . count($rules) . ' rule(s)');
        foreach ($rules as $ruleName) {
            $this->logger->debug("Initializing $ruleName rule");

            $this->downloadRules[] = $this->configurationProvider->getRule(
                $ruleName
            ); //In that case it will never return false since names are sourced from getRulesNames()

            $this->logger->debug("$ruleName rule initialized");
        }
        $this->logger->debug('All rules ready');
    }

    /**
     * @internal
     */
    public function recalculateMainLoopInterval()
    {
        $times = [];
        foreach ($this->sources as $aggregator) {
            $pingInterval = $aggregator->getPingInterval();
            if ($pingInterval < 1) {
                $this->logger->warning(
                    'Aggregator ' . $aggregator->getName() .
                    " returned invalid interval ($pingInterval), ignoring (but it need to be reported as bug!)"
                );
            } elseif ($pingInterval !== AggregatorInterface::NO_PING_INTERVAL) {
                $times[] = $pingInterval;
            }
        }

        if (empty($times)) {
            $this->logger->debug(
                'Calculation of main loop interval abandoned - no sources returned numeric interval'
            );
            $this->mainLoopInterval = null;

            return;
        }

        $this->mainLoopInterval = array_reduce($times, [$this, 'calculateGcd'], $times[0]);

        $this->logger->debug("Calculated main loop interval of {$this->mainLoopInterval}s");
    }

    private function runMainLoop()
    {
        $except = null;

        while (true) {
            $read = $write = [];
            $this->buildStreamsArrays($read, $write);

            $this->lastLoopTime = time();

            if (empty($read)) { //It's useless to check if write isn't empty since stream is always added to read first
                $this->logger->debug('No streams present - using sleep() workaround');
                sleep(1);

            } else {
                $affectedStreams = stream_select($read, $write, $except, $this->mainLoopInterval, 200000);
                if ($affectedStreams === false) {
                    $this->logger->warning(
                        'stream_select() failed. It may be caused by operating system internal problem or sending signal to ' .
                        ConsoleApplication::NAME . ' process.'
                    );
                    continue;
                }

                foreach ($read as $sourceName => $sourceStream) {
                    $this->sources[$sourceName]->onRead();
                }

                foreach ($write as $sourceName => $sourceStream) {
                    $this->sources[$sourceName]->onWrite();
                }
            }

            foreach ($this->sources as $source) {
                $sourceLinks = $source->getLinks();

                if (!empty($sourceLinks)) {
                    $sourceName = $source->getName();
                    $this->logger->debug($sourceName . ' source returned ' . count($sourceLinks) . ' new links');

                    $source->flushLinksPool();
                    $this->matchDownloadLinks($sourceName, $sourceLinks);
                }
            }
        }
    }

    private function buildStreamsArrays(&$read, &$write)
    {
        $currentTime = time();

        foreach ($this->sources as $sourceName => $sourceObj) {
            $pingInterval = $sourceObj->getPingInterval();

            if ($pingInterval !== null && $currentTime - $this->lastLoopTime >= $pingInterval) {
                $sourceObj->ping();
            }

            $stream = $sourceObj->getStream();
            if ($stream !== null) {
                $read[$sourceName] = $stream;

                if ($sourceObj->isWriteReady()) {
                    $write[$sourceName] = $stream;
                }
            }
        }
    }

    private function matchDownloadLinks($sourceName, array $links)
    {
        $this->logger->debug("Matching links from source $sourceName against all rules");

        $linksToDownload = [];
        foreach ($this->downloadRules as $rule) {
            $ruleName = $rule->getName();

            if (!$rule->hasSource($sourceName)) {
                $this->logger->debug("Skipping $ruleName rule - it should not be used against $sourceName source");
                continue;
            }

            foreach ($links as $entryId => $entry) {
                if ($rule->checkName($entry['name'])) {
                    $linksToDownload[$entry['name']] = $entry['link'];
                    unset($links[$entryId]);

                    $this->logger->debug(
                        'Name "' . $entry['name'] . '" matched ' . $ruleName . '" rule - removing from pool'
                    );

                } else {
                    $this->logger->debug('Name ' . $entry['name'] . ' not matched');
                }
            }

            if (empty($links)) {
                $this->logger->debug('Skipping other rules - no links left (all matched by previous rules)');
                break;
            }
        }

        if (!empty($linksToDownload)) {
            $this->downloadLinks($linksToDownload);
        }
    }

    private function downloadLinks(array $links)
    {
        $this->logger->info('Begin downloading ' . count($links) . ' files...');

        foreach ($links as $name => $link) {
            //TODO check if filename exists!
            $filePath = $this->appConfiguration->getFilesSavePath() . '/' . $name . '_' . md5(rand()) . '.torrent';

            $this->logger->debug("Downloading $name => $link to $filePath");
            $fp = @fopen($filePath, 'w');
            if (!$fp) {
                $this->logger->error("Downloading of $link failed - unable to create file $filePath");
                continue;
            }

            $request = new Request('GET', $link);
            $job = new FetchJob($this->appConfiguration, $request);
            $job->setResponseStream($fp);

            $this->logger->debug("Executing job for $link");
            try {
                if (!$job->execute()) {
                    $this->logger->error("Downloading of $link failed - unknown error occurred");
                    continue;
                }

            } catch (\Exception $e) {
                $type = get_class($e);
                $this->logger->error("Downloading of $link failed - $type: " . $e->getMessage());
                continue;
            }

            fclose($fp);
            $this->logger->info("Downloaded $link to $filePath");
        }
    }

    /**
     * Calculates GCD of two given numbers using recursive Euclid algorithm.
     * If anyone forget basic math: https://en.wikipedia.org/wiki/Greatest_common_divisor#Using_Euclid.27s_algorithm
     *
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    private function calculateGcd($a, $b)
    {
        return ($b == 0) ? $a : $this->calculateGcd($b, $a % $b);
    }
}
