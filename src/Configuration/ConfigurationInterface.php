<?php

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
