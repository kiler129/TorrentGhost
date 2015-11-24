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

use noFlash\TorrentGhost\Configuration\AggregatorAbstractConfiguration;
use noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration;
use noFlash\TorrentGhost\Exception\RegexException;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractAggregator
 */
abstract class AbstractAggregator implements AggregatorInterface
{
    /**
     * @var TorrentGhostConfiguration
     */
    protected $appConfiguration;

    /**
     * @var AggregatorAbstractConfiguration
     */
    protected $configuration;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * AbstractAggregator constructor.
     *
     * @param TorrentGhostConfiguration       $appConfiguration
     * @param AggregatorAbstractConfiguration $configuration
     * @param LoggerInterface                 $logger
     */
    public function __construct(
        TorrentGhostConfiguration $appConfiguration,
        AggregatorAbstractConfiguration $configuration,
        LoggerInterface $logger)
    {
        $this->appConfiguration = $appConfiguration;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function ping()
    {
    }

    /**
     * @inheritDoc
     */
    public function getPingInterval()
    {
        return self::NO_PING_INTERVAL;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->configuration->getName();
    }

    /**
     * @return bool
     */
    public function isReady()
    {
        return $this->configuration->isValid();
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function isWriteReady()
    {
        $this->logger->error(__METHOD__ . '() was called but it was not expected (bug?)');

        return false;
    }

    /**
     * @inheritDoc
     */
    public function onWrite()
    {
        $this->logger->error(__METHOD__ . '() was called but it was not expected (bug?)');
    }

    /**
     * @inheritDoc
     */
    public function onRead()
    {
        $this->logger->error(__METHOD__ . '() was called but it was not expected (bug?)');
    }

    /**
     * This method will try to extract title according to rules defined in config.
     *
     * @param $string
     *
     * @return false|string Will return title or false if extraction didn't produced any result.
     * @throws RegexException Supplied extraction regex is invalid.
     */
    public function extractTitle($string)
    {
        $matchResult = @preg_match($this->configuration->getNameExtractPattern(), $string, $matches);

        if ($matchResult === false) {
            throw new RegexException(
                'Pattern was configured for ' . $this->getName() . ' aggregator to extract name',
                $this->configuration->getNameExtractPattern()
            );

        } elseif ($matchResult === 0 || !isset($matches[1])) { //No matches or empty match
            return false;

        } elseif (isset($matches[2])) {
            $this->logger->warning(
                'Pattern ' . $this->configuration->getNameExtractPattern() . ' configured in ' . $this->getName() .
                ' aggregator to extract title resulted in ' . (count($matches) - 1) .
                ' matches instead of one for input string "' . $string . '". Only first one will be used.'
            );
        }


        return $matches[1];
    }

    /**
     * This method will try to extract link & transform it according to rules defined in config.
     *
     * @param $string
     *
     * @return bool|mixed $string false|string Will return title or false if extraction didn't produced any result.
     * @throws RegexException Supplied extraction/transform regex is invalid.
     */
    public function extractLink($string)
    {
        $matchResult = @preg_match($this->configuration->getLinkExtractPattern(), $string, $matches);
        if ($matchResult === false) {
            throw new RegexException(
                'Pattern was configured for ' . $this->getName() . ' aggregator to extract link',
                $this->configuration->getLinkExtractPattern()
            );

        } elseif ($matchResult === 0 || !isset($matches[1])) {
            return false;

        } elseif (isset($matches[2])) {
            $this->logger->warning(
                'Pattern ' . $this->configuration->getLinkExtractPattern() . ' configured in ' . $this->getName() .
                ' aggregator to extract link resulted in ' . (count($matches) - 1) .
                ' matches instead of one for input string "' . $string . '". Only first one will be used.'
            );
        }

        $transformPattern = $this->configuration->getLinkTransformPattern();
        if ($transformPattern !== null) { //Check if transformation is needed
            $matches[1] = @preg_replace($transformPattern[0], $transformPattern[1], $matches[1]);

            if ($matches[1] === null) { //PHP weirdo - null will be returned for error
                throw new RegexException(
                    'Pattern was configured for ' . $this->getName() . ' aggregator to transform link',
                    "[{$transformPattern[0]}, {$transformPattern[0]}]"
                );
            }
        }


        return $matches[1];
    }
}
