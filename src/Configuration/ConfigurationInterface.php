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
 * Common interface for all configuration DTOs.
 */
interface ConfigurationInterface
{
    /**
     * Informs whatever current configuration is complete and valid.
     *
     * @return bool
     */
    public function isValid();
}
