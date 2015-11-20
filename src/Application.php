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
use noFlash\TorrentGhost\Rule\RuleInterface;
use Psr\Log\LoggerInterface;

/**
 * Core application responsible for sourcing all other elements with data
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

    /**
     * @return int|null
     */
    public function getMainLoopInterval()
    {
        return $this->mainLoopInterval;
    }

    public function run()
    {
        //BOOO!
    }

    /**
     * Calculates GCD of two given numbers using resursive Euclid algorithm.
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
