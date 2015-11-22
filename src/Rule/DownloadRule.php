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
     * @var array Aggregators names. For optimal performance names are hold in keys while values are just "true"
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
     * @inheritdoc
     */
    public function getSources()
    {
        return array_keys($this->sources);
    }

    /**
     * @inheritdoc
     */
    public function addSource($aggregatorName)
    {
        if (!is_string($aggregatorName)) {
            $errorClass = '\\' . ((PHP_MAJOR_VERSION >= 7) ? 'TypeError' : 'InvalidArgumentException');
            throw new $errorClass('Expected string - got ' . gettype($aggregatorName));
        }

        if (isset($this->sources[$aggregatorName])) {
            throw new InvalidSourceException(
                'Cannot add source ' . $aggregatorName . ' - it is already in ' . $this->getName() . ' rule'
            );
        }

        $this->sources[$aggregatorName] = true;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeSource($aggregatorName)
    {
        if (!is_string($aggregatorName)) {
            throw new \InvalidArgumentException('Expected string - got ' . gettype($aggregatorName));
        }

        if (!isset($this->sources[$aggregatorName])) {
            throw new InvalidSourceException(
                'Cannot remove source ' . $aggregatorName . ' - it was not added to ' . $this->getName() .
                ' rule before'
            );
        }

        unset($this->sources[$aggregatorName]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasSource($aggregatorName)
    {
        if (!is_string($aggregatorName)) {
            throw new \InvalidArgumentException('Expected string - got ' . gettype($aggregatorName));
        }

        return isset($this->sources[$aggregatorName]);
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
     * Verifies if given name matches rule patterns.
     *
     * @param string $name Name to check
     *
     * @return bool
     */
    public function checkName($name)
    {
        if ($this->nameContainsPattern !== null && preg_match($this->nameContainsPattern, $name) < 1) {
            return false;
        }

        if ($this->nameNotContainsPattern !== null && preg_match($this->nameNotContainsPattern, $name) > 0) {
            return false;
        }

        return true;
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
