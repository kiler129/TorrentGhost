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

namespace noFlash\TorrentGhost\Test\Command;

use noFlash\TorrentGhost\Command\TestRuleCommand;
use Symfony\Component\Console\Input\InputOption;

class TestRuleCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestRuleCommand
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new TestRuleCommand();
    }

    public function testCommandIsNamedRun()
    {
        $this->assertSame('test-rule', $this->subjectUnderTest->getName());
    }

    public function testCommandHaveCorrectDescription()
    {
        $this->assertSame('Tests rule matching', $this->subjectUnderTest->getDescription());
    }

    public function testCommandHaveCorrectHelp()
    {
        $this->assertSame(
            'Regular expressions are pain in the ass. This command provides easy way to simulate input on any rule.',
            $this->subjectUnderTest->getHelp()
        );
    }

    public function testCommandDefinesRuleNameOption()
    {
        $definition = $this->subjectUnderTest->getDefinition();

        $this->assertTrue($definition->hasOption('rule-name'), 'Command does not have rule-name option');

        $option = $definition->getOption('rule-name');
        $this->assertSame('r', $option->getShortcut(), 'Invalid shortcut for option');
        $this->assertTrue($option->isValueRequired(), 'Option value should be required');
        $this->assertSame('Rule name to test against', $option->getDescription(), 'Invalid option description');
        $this->assertSame(TestRuleCommand::RULE_ANY, $option->getDefault(), 'Invalid option default value');
    }
}
