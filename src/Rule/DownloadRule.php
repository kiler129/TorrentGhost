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
use noFlash\TorrentGhost\Configuration\NamedConfigurationTrait;
use noFlash\TorrentGhost\Exception\InvalidSourceException;
use noFlash\TorrentGhost\Exception\RegexException;

/**
 * Represents single download rule with patterns which must be matched.
 */
class DownloadRule implements RuleInterface
{
    use NamedConfigurationTrait;

    /**
     * @var &AbstractAggregator[]
     */
    private $sources = [];

    /**
     * @var string|null Regex which name must match to be considered matching whole rule. Null means this check will be
     *      skipped.
     */
    private $nameContainsPattern;

    /**
     * @var string|null Regex which name must NOT match to be considered matching whole rule. Null means this check
     *      will be skipped.
     */
    private $nameNotContainsPattern;

    /**
     * Provides all sources (sources) configured for this rule.
     *
     * @return &AbstractAggregator[]
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Adds new source (aggregator) to current rule.
     *
     * @param AggregatorInterface &$aggregator
     *
     * @return bool
     * @throws InvalidSourceException Thrown if source already exists in current source.
     */
    public function addSource(AggregatorInterface &$aggregator)
    {
        if (in_array($aggregator, $this->sources, true)) {
            throw new InvalidSourceException(
                'Cannot add source ' . $aggregator->getName() . ' - it is already in ' . $this->getName() . ' rule'
            );
        }

        $this->sources[] = &$aggregator;

        return true;
    }

    /**
     * Removes previously added source (aggregator) to current rule.
     *
     * @param AggregatorInterface $aggregator
     *
     * @return bool
     * @throws InvalidSourceException Thrown if source doesn't exist in current source.
     */
    public function removeSource(AggregatorInterface $aggregator)
    {
        $sourceKey = array_search($aggregator, $this->sources, true);

        if ($sourceKey === false) {
            throw new InvalidSourceException(
                'Cannot remove Source ' . $aggregator->getName() . ' - it was not added to ' . $this->getName() .
                ' rule before'
            );
        }

        unset($this->sources[$sourceKey]);

        return true;
    }

    /**
     * Check if this rule uses source provided.
     *
     * @param AggregatorInterface $aggregator
     *
     * @return bool
     */
    public function hasSource(AggregatorInterface $aggregator)
    {
        return in_array($aggregator, $this->sources, true);
    }

    /**
     * Provides regex which name need to match to be considered matching whole rule.
     *
     * @return string|null Preg pattern or null if there's no rule for matching name.
     */
    public function getNameContainsPattern()
    {
        return $this->nameContainsPattern;
    }

    /**
     * Sets regex which name need to match to be considered matching whole rule.
     *
     * @param string|null $nameContainsPattern Any valid regex. First group will be used while matching. Null if
     *                                         there's no rule that should name match.
     *
     * @throws RegexException
     */
    public function setNameContainsPattern($nameContainsPattern)
    {
        if ($nameContainsPattern !== null && @preg_match($nameContainsPattern, null) === false) {
            throw new RegexException('Name contains pattern invalid', $nameContainsPattern);
        }

        $this->nameContainsPattern = $nameContainsPattern;
    }

    /**
     * Provides regex which name must not match to be considered matching whole rule.
     *
     * @return string|null Preg pattern or null if there's no rule for matching name.
     */
    public function getNameNotContainsPattern()
    {
        return $this->nameNotContainsPattern;
    }

    /**
     * Sets regex which name need must not match to be considered matching whole rule.
     *
     * @param string|null $nameNotContainsPattern Any valid regex. First group will be used while matching. Null if
     *                                            there's no rule that should not name match.
     *
     * @throws RegexException
     */
    public function setNameNotContainsPattern($nameNotContainsPattern)
    {
        if ($nameNotContainsPattern !== null && @preg_match($nameNotContainsPattern, null) === false) {
            throw new RegexException('Name not contains pattern invalid', $nameNotContainsPattern);
        }

        $this->nameNotContainsPattern = $nameNotContainsPattern;
    }

    /**
     * Informs whatever current configuration is complete and valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->sources);
    }
}
