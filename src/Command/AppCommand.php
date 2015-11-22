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

namespace noFlash\TorrentGhost\Command;

use noFlash\TorrentGhost\Console\ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Abstract command used by all other commands inside application
 */
abstract class AppCommand extends Command
{
    /**
     * POSIX exit code returned when everything went well
     *
     * @see {http://tldp.org/LDP/abs/html/exitcodes.html}
     */
    const POSIX_EXIT_OK = 0;

    /**
     * POSIX exit code returned for general error
     *
     * @see {http://tldp.org/LDP/abs/html/exitcodes.html}
     */
    const POSIX_EXIT_ERROR = 1;

    protected function configure()
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Configuration file location',
            ConsoleApplication::DEFAULT_CONFIG_FILE
        );

        $this->addOption(
            'log',
            'l',
            InputOption::VALUE_REQUIRED,
            'Log file location. By defaults app logs to stdout.',
            ConsoleApplication::DEFAULT_LOG_FILE
        );

    }
}
