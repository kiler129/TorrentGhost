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

namespace noFlash\TorrentGhost\Aggregator;

/**
 * Specified generic interface for each aggregator to communicate with core.
 */
interface AggregatorInterface
{
    /**
     * Aggregator type, e.g. RSS or IRC
     */
    const TYPE = null;

    /**
     * Specifies special interval time which denotes current aggregator does not care about ping interval.
     */
    const NO_PING_INTERVAL = -1;

    /**
     * Some sources operates on chitinous stream of data rather than on source fetched in predefined intervals.
     * This method can return stream to be watched by core.
     *
     * @return resource|null PHP stream.
     */
    public function getStream();

    /**
     * Tells the core if stream given by getStream() is expected to be watched for writeability. Since streams are mosly
     * ready for writing this method should return true only when aggregator really wants to write data and already
     * have it prepared.
     *
     * Note: Method is called only if aggregator returned a stream from getStream().
     *
     * @return bool
     */
    public function isWriteReady();

    /**
     * This method is called to notify aggregator that socket is ready to write.
     *
     * Note: Method is called only if aggregator returned bool(true) from isWriteReady() early on.
     *
     * @return void
     */
    public function onWrite();

    /**
     * This method is called to notify aggregator that given socket contains some data to be read or it was closed by
     * remote side.
     *
     * @return void
     */
    public function onRead();

    /**
     * Method called in regular time basis to allow aggregator to perform routine tasks.
     *
     * @return void
     */
    public function ping();

    /**
     * Every aggregator instance can define time, in seconds, between calls to it's ping() method.
     * This value is only a suggestion - not a precise timer. Your ping() method can be called more or less ofter.
     *
     * @return int Value in seconds. NO_PING_INTERVAL means aggregator doesn't need pinging.
     */
    public function getPingInterval();

    /**
     * Provides unique name of current aggregator.
     *
     * @return string
     */
    public function getName();

    /**
     * Tries to get new entries from aggregator. If where
     *
     * @param bool $flush Whatever to flush entries after retrieval (true by default).
     *
     * @return array[] Numeric indexed array containing two-element arrays with name & link entries.
     */
    public function getLinks($flush = true);

    /**
     * Aggregator may not be ready just after creation, this method tells the core if it's ready to operate.
     *
     * @return bool
     */
    public function isReady();
}
