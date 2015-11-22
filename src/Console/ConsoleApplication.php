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

use noFlash\TorrentGhost\Command\AboutCommand;
use noFlash\TorrentGhost\Command\RunCommand;
use noFlash\TorrentGhost\Command\TestConfigurationCommand;
use noFlash\TorrentGhost\Command\TestRuleCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * Main entry-point for application.
 */
class ConsoleApplication extends SymfonyApplication
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
     * Default configuration file location
     * Path may be relative to directory where command is executed
     */
    const DEFAULT_CONFIG_FILE = './config.yml';

    /**
     * Default log file location
     * Path may be relative to directory where command is executed
     */
    const DEFAULT_LOG_FILE = 'php://stdout';

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

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new AboutCommand();
        $commands[] = new RunCommand();
        $commands[] = new TestConfigurationCommand();
        $commands[] = new TestRuleCommand();

        return $commands;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(
            [
                new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

                new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
                new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
                new InputOption(
                    '--verbose',
                    '-v|vv',
                    InputOption::VALUE_NONE,
                    'Increase the verbosity of messages: 1 for normal output, 2 for debug'
                ),
                new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            ]
        );
    }
}
