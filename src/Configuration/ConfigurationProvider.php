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

use noFlash\TorrentGhost\Exception\ConfigurationException;
use noFlash\TorrentGhost\Rule\RuleInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration
 *
 * @TODO Every configuration should be returned as reference + cfg. objects should not be instantiated more once
 */
class ConfigurationProvider implements ConfigurationInterface
{
    /**
     * @var array
     */
    private $rawConfiguration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $isConfigurationValid = false;

    /**
     * ConfigurationProvider constructor.
     *
     * @param array           $configuration
     * @param LoggerInterface $logger
     *
     * @throws ConfigurationException
     */
    public function __construct(array $configuration, LoggerInterface $logger)
    {
        $this->rawConfiguration = $configuration;
        $this->logger = $logger;

        if (!$this->isValid()) {
            throw new ConfigurationException('Configuration is invalid!');
        }
    }

    /**
     * @inheritdoc
     */
    public function isValid()
    {
        if (!$this->isConfigurationValid) {
            $validator = new ConfigurationValidator($this);
            $this->isConfigurationValid = $validator->validate();
        }

        return $this->isConfigurationValid;
    }

    /**
     * Creates object from yaml configuration file
     *
     * @param string $filePath Patch to configuration file
     *
     * @return static
     * @throws ConfigurationException
     */
    public static function fromYamlFile($filePath, LoggerInterface $logger)
    {
        $realFilePath = realpath($filePath);

        if ($realFilePath === false) {
            throw new ConfigurationException("Failed to access configuration file $filePath");
        }

        $fileContents = @file_get_contents($realFilePath);
        if ($fileContents === false) {
            throw new ConfigurationException(
                "Failed to read configuration file $realFilePath (resolved from $filePath)"
            );
        }

        try {
            $configuration = (new Yaml())->parse($fileContents);

            return new static($configuration, $logger);

        } catch (ParseException $e) {
            throw new ConfigurationException(
                'Failed to parse configuration: ' . $e->getMessage() . ' at line ' . $e->getParsedLine(), 0, $e
            );
        }
    }

    /**
     * Returns application configuration
     *
     * @return TorrentGhostConfiguration
     * @throws \LogicException
     */
    public function getApplicationConfiguration()
    {
        //We can assume "torrentGhost" index exists due to isValid() call in constructor
        $configuration = new ConfigurationFactory(
            '\noFlash\TorrentGhost\Configuration\TorrentGhostConfiguration', $this->rawConfiguration['torrentGhost']
        );


        return $configuration->build();
    }

    /**
     * Returns all defined aggregator names.
     *
     * @return array
     */
    public function getAggregatorsNames()
    {
        //We can assume "dataSources" index exists due to isValid() call in constructor
        return array_keys($this->rawConfiguration['dataSources']);
    }

    /**
     * Returns specified aggregator configuration
     *
     * @param string $name
     *
     * @return AggregatorAbstractConfiguration|bool Returns aggregator configuration or false on failure
     * @throws ConfigurationException
     * @throws \LogicException
     */
    public function getAggregatorConfiguration($name)
    {
        if (!isset($this->rawConfiguration['dataSources'][$name]['type'])) {
            return false;
        }

        //We can assume "type" index exists due to isValid() call in constructor
        $className = $this->getAggregatorConfigurationClassNameByAggregatorType(
            $this->rawConfiguration['dataSources'][$name]['type']
        );
        if ($className === false) { //In theory this should never happen because ConfigurationValidator should catch it
            throw new ConfigurationException(
                'Invalid type "' . $this->rawConfiguration['dataSources'][$name]['type'] . '" defined for "' . $name .
                '" source'
            );
        }

        $config = $this->rawConfiguration['dataSources'][$name];
        $config['name'] = $name;
        unset($config['type']);

        return (new ConfigurationFactory($className, $config))->build();
    }

    /**
     * Returns configuration class name for given aggregator name
     *
     * @param string $type Aggregator type name
     *
     * @return bool|string Returns class name or false if class cannot be found.
     */
    public function getAggregatorConfigurationClassNameByAggregatorType($type)
    {
        $className = sprintf(
            '\noFlash\TorrentGhost\Configuration\%sAggregatorConfiguration',
            ucfirst(strtolower($type))
        );

        if (!class_exists($className)) {
            return false;
        }

        return $className;
    }

    /**
     * Returns configuration class name for given aggregator name
     *
     * @param string $name Aggregator name
     *
     * @return bool|string Returns class name or false if class cannot be found.
     */
    public function getAggregatorClassNameByAggregatorName($name)
    {
        if (!isset($this->rawConfiguration['dataSources'][$name]['type'])) {
            return false;
        }

        $className = sprintf(
            '\noFlash\TorrentGhost\Aggregator\%sAggregator',
            ucfirst(strtolower($this->rawConfiguration['dataSources'][$name]['type']))
        );

        if (!class_exists($className)) {
            return false;
        }

        return $className;
    }

    /**
     * Returns all defined rule names.
     *
     * @return array
     */
    public function getRulesNames()
    {
        //We can assume "downloadRules" index exists due to isValid() call in constructor
        return array_keys($this->rawConfiguration['downloadRules']);
    }

    /**
     * Provides configured rule
     *
     * @param string $name
     *
     * @return RuleInterface|bool Returns configured rule or false on failure
     * @throws \LogicException
     */
    public function getRule($name)
    {
        if (!isset($this->rawConfiguration['downloadRules'][$name])) {
            return false;
        }

        $config = $this->rawConfiguration['downloadRules'][$name];
        $config['name'] = $name;

        return (new ConfigurationFactory('\noFlash\TorrentGhost\Rule\DownloadRule', $config))->build();
    }
}
