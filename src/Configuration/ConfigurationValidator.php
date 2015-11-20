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

namespace noFlash\TorrentGhost\Configuration;

class ConfigurationValidator
{
    public function __construct(ConfigurationProvider $configurationProvider)
    {
    }

    public function validate()
    {
        return true; //stub -> validate eg required sections here
    }
}
