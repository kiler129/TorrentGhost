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
use noFlash\TorrentGhost\Exception\RegexException;

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
     * @param AggregatorInterface $aggregator
     *
     * @return bool
     * @throws InvalidSourceException Thrown if source doesn't exist in current source.
     */
    public function removeSource(AggregatorInterface $aggregator);

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
     * Sets regex which name need to match to be considered matching whole rule.
     *
     * @param string|null $nameContainsPattern Any valid regex. First group will be used while matching. Null if
     *                                         there's no rule that should name match.
     *
     * @throws RegexException
     */
    public function setNameContainsPattern($nameContainsPattern);

    /**
     * Provides regex which name must not match to be considered matching whole rule.
     *
     * @return string|null Preg pattern or null if there's no rule for matching name.
     */
    public function getNameNotContainsPattern();

    /**
     * Sets regex which name need must not match to be considered matching whole rule.
     *
     * @param string|null $nameNotContainsPattern Any valid regex. First group will be used while matching. Null if
     *                                            there's no rule that should not name match.
     *
     * @throws RegexException
     */
    public function setNameNotContainsPattern($nameNotContainsPattern);

    /**
     * Verifies if given name matches rule patterns.
     *
     * @param string $name Name to check
     *
     * @return bool
     */
    public function checkName($name);
}
