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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides some information about the project.
 */
class AboutCommand extends AppCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('about')->setDescription('Information about ' . ConsoleApplication::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            '<info>' . ConsoleApplication::NAME . " - Automagic Torrent Downloader</info>\n" .
            '<comment>This application will help you with your laziness. ' .
            'For more information see https://github.com/kiler129/TorrentGhost/blob/master/README.md</comment>'
        );
    }
}
