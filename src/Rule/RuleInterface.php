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

namespace noFlash\TorrentGhost\Rule;


use noFlash\TorrentGhost\Aggregator\AggregatorInterface;
use noFlash\TorrentGhost\Configuration\NameAwareConfigurationInterface;
use noFlash\TorrentGhost\Exception\InvalidSourceException;

/**
 * Interface is used for objects which defines download rules.
 */
interface RuleInterface extends NameAwareConfigurationInterface
{
    /**
     * Provides all sources (aggregators) configured for this rule.
     *
     * @return &AbstractAggregator[]
     */
    public function getSources();

    /**
     * Adds new source (aggregator) to current rule.
     *
     * @param AggregatorInterface &$aggregator
     *
     * @return bool
     * @throws InvalidSourceException Thrown if source already exists in current source.
     */
    public function addSource(AggregatorInterface &$aggregator);

    /**
     * Removes previously added source (aggregator) to current rule.
     *
     * @param AggregatorInterface &$aggregator
     *
     * @return bool
     * @throws InvalidSourceException Thrown if source doesn't exist in current source.
     */
    public function removeSource(AggregatorInterface &$aggregator);

    /**
     * Check if this rule uses source provided.
     *
     * @param AggregatorInterface $aggregator
     *
     * @return bool
     */
    public function hasSource(AggregatorInterface $aggregator);

    /**
     * Provides regex which name need to match to be considered matching whole rule.
     *
     * @return string|null Preg pattern or null if there's no rule for matching name.
     */
    public function getNameContainsPattern();

    /**
     * Provides regex which name must not match to be considered matching whole rule.
     *
     * @return string|null Preg pattern or null if there's no rule for matching name.
     */
    public function getNameNotContainsPattern();
}
