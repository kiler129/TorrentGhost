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
 * Class holding configuration for
 */
class RssAggregatorConfiguration extends AggregatorAbstractConfiguration
{
    /**
     * @var string|null URL of RSS feed
     */
    protected $url;

    /**
     * @var int Specified, in seconds, how often feed should be fetched. By default 300s.
     */
    protected $interval = 300;

    /**
     * @var string Name of tag where torrent name is located, by default it's "title".
     */
    protected $nameTagName = 'title';

    /**
     * @var string Name of tag where torrent link is located, by default it's "link".
     */
    protected $linkTagName = 'link';

    /**
     * Provides URL of RSS feed to fetch.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets URL of RSS feed to fetch.
     *
     * @param string $url Full URL with one of the following schemas: http, https, ftp, ftps.
     *
     * @throws \InvalidArgumentException Thrown in case of invalid URL.
     */
    public function setUrl($url)
    {
        if (!preg_match("
              /^                                                      # Start at the beginning of the text
              (?:https?|ftps?):\/\/                                   # Look for http, https, ftp or ftps schema
              (?:                                                     # Userinfo (optional) which is typically
                (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
                (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
              )?
              (?:
                (?:[a-z0-9\-\.]|%[0-9a-f]{2}|[\p{L}])+                # A domain name or a IPv4 address
                |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
              )
              (?::[0-9]+)?                                                  # Server port number (optional)
              (?:[\/|\?]
                (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2}|\p{L})   # The path and query (optional)
              *)?
            $/xiu", $url)
        ) {
            throw new \InvalidArgumentException('Invalid RSS feed URL');
        }

        $this->url = $url;
    }

    /**
     * Provides RSS fetch interval.
     *
     * @return int Number of seconds.
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Sets number of seconds between each RSS fetch.
     *
     * @param int $interval Number of seconds.
     *
     * @throws \InvalidArgumentException Thrown if interval is smaller than 1 or not integer.
     */
    public function setInterval($interval)
    {
        if (!is_int($interval) || $interval < 1) {
            throw new \InvalidArgumentException('Specified interval value cannot be used - it should be integer not smaller than 1');
        }

        $this->interval = $interval;
    }

    /**
     * Provides tag name where name is located.
     *
     * @return string
     */
    public function getNameTagName()
    {
        return $this->nameTagName;
    }

    /**
     * Sets tag name where to look for name.
     *
     * @param string $nameTagName Any valid XML tag.
     *
     * @throws \InvalidArgumentException Thrown if invalid tag name was specified.
     */
    public function setNameTagName($nameTagName)
    {
        if (!preg_match($this->getTagNameRegex(), $nameTagName)) {
            throw new \InvalidArgumentException('Invalid tagName for name');
        }

        $this->nameTagName = $nameTagName;
    }

    /**
     * Provides regex for use with preg_match() to check given XML tag name.
     *
     * @return string
     */
    private function getTagNameRegex()
    {
        return '~
                    # XML 1.0 Name symbol PHP PCRE regex <http://www.w3.org/TR/REC-xml/#NT-Name>
                    (?(DEFINE)
                        (?<NameStartChar> [:A-Z_a-z\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\x{2FF}\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}])
                        (?<NameChar>      (?&NameStartChar) | [.\\-0-9\\xB7\\x{0300}-\\x{036F}\\x{203F}-\\x{2040}])
                        (?<Name>          (?&NameStartChar) (?&NameChar)*)
                    )
                    ^(?&Name)$
                ~ux';
    }

    /**
     * Provides tag name where link is located.
     *
     * @return string
     */
    public function getLinkTagName()
    {
        return $this->linkTagName;
    }

    /**
     * Sets tag name where to look for link.
     *
     * @param $linkTagName
     *
     * @throws \InvalidArgumentException Thrown if invalid tag name was specified.
     * @internal param string $nameTagName Any valid XML tag.
     */
    public function setLinkTagName($linkTagName)
    {
        if (!preg_match($this->getTagNameRegex(), $linkTagName)) {
            throw new \InvalidArgumentException('Invalid tagName for link');
        }

        $this->linkTagName = $linkTagName;
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        if ($this->url === null) {
            return false;
        }

        return parent::isValid();
    }
}
