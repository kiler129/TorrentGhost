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


use noFlash\TorrentGhost\Exception\RegexException;
use noFlash\TorrentGhost\Http\CookiesBag;

/**
 * Generic configuration class common for all content aggregators.
 */
abstract class AggregatorAbstractConfiguration implements ConfigurationInterface
{
    /**
     * @var string Unique, human-readable, name of current aggregator instance.
     */
    protected $name = 'UnknownAggregator';

    /**
     * @var string Regex used to extract name. By default matches whole string.
     */
    protected $nameExtractPattern = '/^(.*?)$/';

    /**
     * @var string Regex used to extract link. By default matches whole string.
     */
    protected $linkExtractPattern = '/^(.*?)$/';

    /**
     * @var null|array Array of two arguments used while calling preg_replace() on matched link. If null link will not
     *     be transformed by preg_replace().
     */
    protected $linkTransformPattern = null;

    ///**
    // * @not-implemented
    // * @var null|string Location to save .torrent files downloaded from links obtained by this aggregator instance. If null parent value will be used.
    // */
    //protected $filesSavePath = null;

    /**
     * @var null|CookiesBag Cookies which should be used while downloading links obtained by this aggregator. If null
     *     no cookies will be used.
     */
    protected $linkCookies = null;

    /**
     * Provides name of current aggregator instance. Name is unique across whole application.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Provides name of current aggregator instance. Name is unique across whole application.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * Provides regex to extract name.
     *
     * @return string Regex used to extract name.
     */
    public function getNameExtractPattern()
    {
        return $this->nameExtractPattern;
    }

    /**
     * Sets regex used to extract name.
     *
     * @param string $nameExtractPattern Any valid regex. First group will be used while matching.
     */
    public function setNameExtractPattern($nameExtractPattern)
    {
        if (@preg_match($nameExtractPattern, null) === false) {
            throw new RegexException('Name extract pattern invalid', $nameExtractPattern);
        }

        $this->nameExtractPattern = $nameExtractPattern;
    }

    /**
     * Provides regex to extract link.
     *
     * @return string Regex used to extract link.
     */
    public function getLinkExtractPattern()
    {
        return $this->linkExtractPattern;
    }

    /**
     * Sets regex used to extract link.
     *
     * @param string $linkExtractPattern Any valid regex. First group will be used while matching.
     */
    public function setLinkExtractPattern($linkExtractPattern)
    {
        if (@preg_match($linkExtractPattern, null) === false) {
            throw new RegexException('Link extract pattern invalid', $linkExtractPattern);
        }

        $this->linkExtractPattern = $linkExtractPattern;
    }

    /**
     * Method provides two arguments used while calling preg_replace() on matched link to transform it (e.g. add
     * passkey).
     *
     * @return array|null
     */
    public function getLinkTransformPattern()
    {
        return $this->linkTransformPattern;
    }

    /**
     * Method allows to set two arguments used while calling preg_replace() on matched link to transform it (e.g. add
     * passkey). If null link will not be transformed by preg_replace().
     *
     * @param array|null $linkTransformPattern Numeric array with exactly two fields containing regex pattern and
     *     replacement.
     *
     * @throws RegexException
     * @throws \RuntimeException Thrown if invalid array was passed.
     */
    public function setLinkTransformPattern($linkTransformPattern)
    {
        if ($linkTransformPattern === null) {
            $this->linkTransformPattern = null;

            return;
        }

        if (!isset($linkTransformPattern[0], $linkTransformPattern[1])) {
            throw new \RuntimeException('Invalid array passed.');
        }

        if (@preg_match($linkTransformPattern[0], null) === false) {
            throw new RegexException('Link transform pattern invalid', $linkTransformPattern[0]);
        }

        $this->linkTransformPattern = [$linkTransformPattern[0], $linkTransformPattern[1]];
    }

    /**
     * Returns cookies which should be used while downloading links obtained by this aggregator.
     * If null no cookies will be used.
     *
     * @return CookiesBag|null
     */
    public function getLinkCookies()
    {
        return $this->linkCookies;
    }

    /**
     * Allows setting cookies which should be used while downloading links obtained by this aggregator.
     * If null no cookies will be used.
     *
     * @param CookiesBag|null $linkCookies
     */
    public function setLinkCookies(CookiesBag $linkCookies = null)
    {
        $this->linkCookies = $linkCookies;
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        return true; //Default values are sufficient as configuration.
    }
}
