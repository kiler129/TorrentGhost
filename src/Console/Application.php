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

namespace noFlash\TorrentGhost\Console;

use Symfony\Component\Console\Application as SymfonyApplication;

/**
 * Main entry-point for application.
 */
class Application extends SymfonyApplication
{
    /**
     * Full application name
     */
    const NAME = 'TorrentGhost';

    /**
     * Global application version
     */
    const VERSION = '1.0.0-dev';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        //Two if's stolen from Composer code to prevent weird errors ;)
        if (function_exists('ini_set') && extension_loaded('xdebug')) {
            ini_set('xdebug.show_exception_trace', false);
            ini_set('xdebug.scream', false);
        }

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        parent::__construct(static::NAME, static::VERSION);
    }


}
