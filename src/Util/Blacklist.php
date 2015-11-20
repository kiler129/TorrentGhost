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

namespace noFlash\TorrentGhost\Util;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This class was extracted from RSS aggregator, but can be used with other sources in future.
 * Downloading RSS second time you will probably get some new entries but also some old ones. To prevent
 * re-matching and re-downloading already downloaded files blacklist was introduced.
 * Example: for 1st download you've got A B C, for 2nd you've got A B C D, for 3rd you've got C D E. Logical &
 * expected behaviour will be to process A B and C first time, D second time and only E third time.
 */
class Blacklist
{
    /**
     * @var array
     */
    private $blacklist = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Blacklist constructor.
     *
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Retrieves whole blacklist as array.
     *
     * @return array
     */
    public function getBlacklist()
    {
        return $this->blacklist;
    }

    /**
     * Overwrites blacklist with provided one.
     * You should probably never use that method ;)
     *
     * @param array $blacklist
     */
    public function setBlacklist(array $blacklist)
    {
        $this->blacklist = $blacklist;
    }

    /**
     * Set value on given entry. If entry does not exists it will be added.
     *
     * @param string|int $key
     * @param            $value
     */
    public function setBlacklistEntry($key, $value)
    {
        $this->blacklist[$key] = $value;
    }

    /**
     * Adds entry to blacklist. If entry already exists exception will be thrown.
     *
     * @param string|int $key
     * @param            $value
     *
     * @throws \LogicException
     */
    public function addBlacklistEntry($key, $value)
    {
        if (isset($this->blacklist[$key])) {
            throw new \LogicException(
                'Cannot add blacklist entry with key "' . $key .
                '" - entry already exists. If you want to overwrite it anyway use setBlacklistEntry() instead.'
            );
        }

        $this->blacklist[$key] = $value;
    }

    /**
     * Removes entry from blacklist. If entry doesn not exists exception will be thrown.
     *
     * @param $key
     *
     * @throws \LogicException
     */
    public function removeBlacklistEntry($key)
    {
        if (!isset($this->blacklist[$key])) {
            throw new \LogicException(
                'Cannot remove blacklist entry identified by key "' . $key . '" - entry does not exist.'
            );
        }

        unset($this->blacklist[$key]);
    }

    /**
     * Checks if entry is blacklisted.
     *
     * @param      $key
     * @param null $value If value is present it will also check is value matches.
     *
     * @return bool
     */
    public function hasBlacklistEntry($key, $value = null)
    {
        if (!isset($this->blacklist[$key])) {
            return false;
        }

        if ($value !== null) {
            return ($this->blacklist[$key] === $value);
        }

        return true;
    }

    /**
     * Removes all blacklist entries.
     */
    public function clearBlacklist()
    {
        $this->blacklist = [];
    }

    /**
     * This method filter given entries and removes all previously seen. It also maintains blacklist by removing non
     * existing entries (to prevent from memory leak).
     *
     * Example: 1st time you've got A B C, 2nd time you've got A B C D, 3rd time you've got C D E.
     *
     * So giving data above this method will add A B C to the blacklist first time and will not
     * remove anything from given array. For the second time it will leave only D in given array adding it to
     * blacklist. For data from third call A and B will be removed from blacklist, given array will contain only E
     * after call.
     *
     * @param array &$entriesToFilter Single dimension array with key and value. =>Value is taken by reference.<=
     */
    public function applyBlacklist(array &$entriesToFilter)
    {
        foreach ($this->blacklist as $key => $value) {
            if (isset($entriesToFilter[$key]) && $entriesToFilter[$key] === $value) {
                $this->logger->debug(
                    'Entry "' . $key . '" is on auto-blacklist - removing from data array'
                );

                unset($entriesToFilter[$key]);

            } else {
                $this->logger->debug(
                    'Entry "' . $key . '" is not present in new data - removing from auto-blacklist'
                );

                unset($this->blacklist[$key]);
            }
        }

        $this->blacklist += $entriesToFilter;
        if (!empty($entriesToFilter)) {
            $this->logger->debug(
                'Added new entries to auto-blacklist',
                $entriesToFilter
            );
        }
    }
}
