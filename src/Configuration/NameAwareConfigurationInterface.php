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

/**
 * Provides interface to use along with ConfigurationInterface to denote extended configuration which contains element
 * name.
 */
interface NameAwareConfigurationInterface extends ConfigurationInterface
{
    /**
     * Provides name of configuration instance item. Name is unique across whole application for this type of object.
     *
     * @return string
     */
    public function getName();

    /**
     * Provides name of configuration instance item. Name is unique across whole application for this type of object.
     *
     * @param string $name
     */
    public function setName($name);
}
