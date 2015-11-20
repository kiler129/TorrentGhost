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

use noFlash\Shout\Shout;
use noFlash\TorrentGhost\Application;
use noFlash\TorrentGhost\Configuration\ConfigurationProvider;
use noFlash\TorrentGhost\Console\ConsoleApplication;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Starts TorrentGhost application
 */
class RunCommand extends AppCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        //@formatter:off
        $this
            ->setName('run')
            ->setDescription('Runs the application');
        //@formatter:on

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getLogger(); //If it crash here it will just crash without fancy-pancy error message, sorry!
        $logger->info('Starting ' . ConsoleApplication::NAME . ' v' . ConsoleApplication::VERSION);

        try {
            //TODO handle POSIX signal to reload config (and these two lines should be than extracted to method)
            $configurationProvider = ConfigurationProvider::fromYamlFile($input->getOption('config'), $logger);
            $application = new Application($configurationProvider, $logger);
            $application->run();

        } catch (\Exception $e) {
            $logger->emergency($this->getClassNameFromObject($e) . ': ' . $e->getMessage());

            return self::POSIX_EXIT_ERROR;
        }

        $logger->info(ConsoleApplication::NAME . ' finished');
        return self::POSIX_EXIT_OK;
    }

    /**
     * Configures logger under
     *
     * @param string $destination
     * @param int    $verbosityLevel
     *
     * @return LoggerInterface
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function getLogger($destination = 'php://stdout', $verbosityLevel = PHP_INT_MAX)
    {
        if ($verbosityLevel === OutputInterface::VERBOSITY_QUIET) {
            return new NullLogger();
        }

        $logger = new Shout($destination, Shout::FILE_APPEND); //By default Shout outputs every log message

        if ($verbosityLevel < OutputInterface::VERBOSITY_VERBOSE) {
            $logger->setMaximumLogLevel(6);
        }

        return $logger;
    }

    /**
     * Gets class name (without namespace if present) for given object.
     *
     * @param object $object
     *
     * @return string
     * @throws \LogicException In case you pass something other than object stupid :P
     */
    private function getClassNameFromObject($object)
    {
        if (!is_object($object)) {
            throw new \LogicException('Non-object passed - failed to extract its name');
        }

        $objectName = get_class($object);

        $namespacePosition = strrpos($objectName, '\\');

        return ($namespacePosition === false) ? $objectName : substr($objectName, $namespacePosition + 1);
    }
}
