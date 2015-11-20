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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Allows testing any rule whatever it matches or not.
 */
class TestRuleCommand extends AppCommand
{
    const RULE_ANY = null;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        //@formatter:off
        $this
            ->setName('test-rule')
            ->setDescription('Tests rule matching')
            ->setHelp('Regular expressions are pain in the ass. This command provides easy way to simulate input on any rule.')
            ->addOption('rule-name', 'r', InputOption::VALUE_REQUIRED, 'Rule name to test against', self::RULE_ANY)
            ->addArgument('names', InputArgument::IS_ARRAY, 'Name(s) to test');
        //@formatter:on

        parent::configure();
    }
}
