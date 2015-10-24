<?php
/*
 * This file is part of TorrentGhost project.
 * You are using it at your own risk and you are fully responsible
 *  for everything that code will do.
 *
 * (c) Grzegorz Zdanowski <grzegorz@noflash.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace noFlash\TorrentGhost\Configuration;

/**
 * This object holds general application configuration.
 */
class TorrentGhostConfiguration implements ConfigurationInterface
{
    /**
     * @var string Path to directory where downloaded files should be stored.
     */
    private $filesSavePath;

    /**
     * @var int Limit for downloaded file size *in bytes*. By default it's set to equivalent of 2MB.
     */
    private $fileSizeLimit = 25000000;

    /**
     * Provides patch where downloaded torrent files should be saved.
     *
     * @return string Absolute path to directory without leading slash.
     */
    public function getFilesSavePath()
    {
        return $this->filesSavePath;
    }

    /**
     * Sets patch to save downloaded torrent files.
     *
     * @param string $filesSavePath Any valid & accessible directory path.
     *
     * @throws \LogicException Given path doesn't direct to directory.
     * @throws \RuntimeException Given directory cannot be accessed.
     */
    public function setFilesSavePath($filesSavePath)
    {
        $absolutePath = realpath($filesSavePath);

        if ($absolutePath === false) {
            throw new \RuntimeException("Path $filesSavePath cannot be used as files target directory - it's invalid or inaccessible.");
        }

        if (!is_dir($absolutePath)) {
            throw new \LogicException("Given save path $filesSavePath doesn't represent directory");
        }

        $this->filesSavePath = $absolutePath;
    }

    /**
     * Provides human-readable form of file size limit.
     * Method assumes that 1K = 1000, 1M = 1000K, 1G = 1000M.
     * Zero indicates that there's no limit.
     *
     * @return string
     */
    public function getFileSizeLimit()
    {
        static $units = ['', 'K', 'M', 'G'];
        $power = $this->fileSizeLimit > 0 ? floor(log($this->fileSizeLimit, 1000)) : 0;

        return ($this->fileSizeLimit / pow(1000, $power)) . $units[$power];
    }

    /**
     * Allows you to set human readable file size limit.
     * Zero is treated as no limit.
     *
     * @param int|float|string $fileSizeLimit Raw value in bytes (e.g. 999), 1K or 3M, 2.5G. Half-byte values are
     *     rounded.
     *
     * @throws \OutOfRangeException
     */
    public function setFileSizeLimit($fileSizeLimit)
    {
        static $units = ['K' => 1000, 'M' => 1000000, 'G' => 1000000000];
        $postfix = substr($fileSizeLimit, -1);
        if (isset($units[$postfix])) { //Value contains postfix - it should be converted fist
            $fileSizeLimit *= $units[$postfix];
        }

        if ($fileSizeLimit < 0 || $fileSizeLimit > 2147483647) {
            throw new \OutOfRangeException('File size limit should be withing 0-2147483647 range.');
        }

        $this->fileSizeLimit = (int)round($fileSizeLimit);
    }

    /**
     * Provides raw (in bytes) file size limit.
     * Zero indicates no limit.
     *
     * @return int Non-negative integer value not larger than 2^31-1.
     */
    public function getRawFileSizeLimit()
    {
        return $this->fileSizeLimit;
    }

    /**
     * Allows you to set raw (in bytes) file size limit.
     * Zero is treated as no limit.
     *
     * @param int $fileSizeLimit Non-negative integer value not larger than 2^32-1.
     *
     * @throws \InvalidArgumentException
     * @throws \OutOfRangeException
     */
    public function setRawFileSizeLimit($fileSizeLimit)
    {
        if (!is_integer($fileSizeLimit)) {
            throw new \InvalidArgumentException('Non-integer value passed for raw file size limit.');
        }

        if ($fileSizeLimit < 0 || $fileSizeLimit > 2147483647) {
            throw new \OutOfRangeException('File size limit should be withing 0-2147483647 range.');
        }

        $this->fileSizeLimit = (int)$fileSizeLimit;
    }

    /**
     * Informs whatever current configuration is complete and valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return ($this->filesSavePath !== null);
    }
}
