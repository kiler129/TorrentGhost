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

namespace noFlash\TorrentGhost\Command;

use noFlash\TorrentGhost\Aggregator\AggregatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use noFlash\TorrentGhost\Rule\RuleInterface;

class RunCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TorrentGhostConfiguration
     */
    private $appConfiguration;

    /**
     * @var AggregatorInterface[]
     */
    private $aggregators = [];

    /**
     * @var RuleInterface[]
     */
    private $downloadRules = [];

    /**
     * @var int|null Every aggregator can specify time interval for pinging it. This value represents calculated value
     *               for every aggregators (using GCD algorithm).
     *               It can be null if every aggregator decide to not specify any interval.
     */
    private $mainLoopInterval = null;

    /**
     * @inheritDoc
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->debug('Initializing ' . __CLASS__);

        parent::__construct(null);
    }

    /**
     * @internal
     */
    public function recalculateMainLoopInterval()
    {
        $times = [];
        foreach ($this->aggregators as $aggregator) {
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
                'Calculation of main loop interval abandoned - no aggregators returned numeric interval'
            );
            $this->mainLoopInterval = null;

            return;
        }

        //If anyone forget basic math this is a Euclid algorithm
        // https://en.wikipedia.org/wiki/Greatest_common_divisor#Using_Euclid.27s_algorithm
        static $gcd = function ($a, $b) use (&$gcd) {
            return ($b == 0) ? $a : $gcd($b, $a % $b);
        };
        $this->mainLoopInterval = array_reduce($times, $gcd, $times[0]);

        $this->logger->debug("Calculated main loop interval of {$this->mainLoopInterval}s");
    }

    /**
     * @return int|null
     */
    public function getMainLoopInterval()
    {
        return $this->mainLoopInterval;
    }
}
