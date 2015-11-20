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

use noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException;

/**
 * Builds configuration object based on parameters
 */
class ConfigurationFactory
{
    /**
     * @var string Class of object which should be created. Class must implement
     *      \noFlash\TorrentGhost\Configuration\ConfigurationInterface.
     */
    private $className;

    /**
     * @var array
     */
    private $instanceParameters = [];

    /**
     * ConfigurationFactory constructor.
     *
     * @param string $objectClass Class must implement \noFlash\TorrentGhost\Configuration\ConfigurationInterface
     * @param array  $instanceParameters
     *
     * @throws \InvalidArgumentException See setClassName() for details.
     */
    public function __construct($objectClass = null, array $instanceParameters = [])
    {
        if ($objectClass !== null) {
            $this->setClassName($objectClass);
        }

        $this->setInstanceParameters($instanceParameters);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className Class of object which should be created. Class must implement
     *                          \noFlash\TorrentGhost\Configuration\ConfigurationInterface.
     *
     * @throws \InvalidArgumentException Exception will be raised if given class name doesn't exists, doesn't implement
     *                                   correct interface or given class is not instantiable (e.g. abstract).
     */
    public function setClassName($className)
    {
        try {
            $classReflection = new \ReflectionClass($className);

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to get information about class ' . $className, 0, $e);
        }

        if (!in_array(
            'noFlash\TorrentGhost\Configuration\ConfigurationInterface',
            $classReflection->getInterfaceNames()
        )
        ) {
            throw new \InvalidArgumentException('Invalid class given - it should implement ConfigurationInterface');
        }

        if (!$classReflection->isInstantiable()) {
            throw new \InvalidArgumentException('Given class ' . $className . ' is not instantiable');
        }

        $this->className = $className;
    }

    /**
     * @return array
     */
    public function getInstanceParameters()
    {
        return $this->instanceParameters;
    }

    /**
     * @param array $instanceParameters
     */
    public function setInstanceParameters(array $instanceParameters)
    {
        $this->instanceParameters = $instanceParameters;
    }

    public function build()
    {
        if (empty($this->className)) {
            throw new \LogicException('You cannot build object without setting class name');
        }

        $instance = new $this->className;
        $this->setParametersOnInstance($instance);

        return $instance;
    }

    /**
     * Sets all parameters from $this->instanceParameters onto given object instance.
     *
     * @param ConfigurationInterface $instance
     *
     * @throws UnknownConfigurationParameterException
     */
    private function setParametersOnInstance(ConfigurationInterface $instance)
    {
        $classReflection = new \ReflectionObject($instance);

        foreach ($this->instanceParameters as $paramName => $paramValue) {
            if ($classReflection->hasProperty($paramName) && $classReflection->getProperty($paramName)->isPublic()) {
                $instance->{$paramName} = $paramValue;
                continue;
            }

            $setterName = 'set' . $paramName;
            if ($classReflection->hasMethod($setterName) && $classReflection->getMethod($setterName)->isPublic()) {
                $instance->{$setterName}($paramValue);
                continue;
            }

            throw new UnknownConfigurationParameterException(
                'Failed to locate public "' . $paramName . '" property or public setter named ' . $setterName . '()',
                $paramName
            );
        }
    }
}
