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

namespace noFlash\TorrentGhost\Test\Configuration;

use noFlash\TorrentGhost\Configuration\ConfigurationFactory;

class ConfigurationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationFactory
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->subjectUnderTest = new ConfigurationFactory();
    }

    public function testFreshObjectIsCreatedWithNullClassName()
    {
        $this->assertNull($this->subjectUnderTest->getClassName());
    }

    public function testFreshObjectIsCreatedWithEmptyParametersArray()
    {
        $this->assertSame([], $this->subjectUnderTest->getInstanceParameters());
    }

    public function testTryingToUseNonExistingClassNameInConstructorProducesException()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Failed to get information about class UnknownClass');
        new ConfigurationFactory('UnknownClass');
    }

    public function testTryingToUseClassNameWhichDoesNotImplementConfigurationInterfaceInConstructorProducesException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Invalid class given - it should implement ConfigurationInterface'
        );
        new ConfigurationFactory('\stdClass');
    }

    public function testTryingToUseAbstractClassNameInConstructorProducesException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Given class \noFlash\TorrentGhost\Test\Stub\AbstractClassImplementingConfigurationInterfaceStub is not instantiable'
        );
        new ConfigurationFactory(
            '\noFlash\TorrentGhost\Test\Stub\AbstractClassImplementingConfigurationInterfaceStub'
        );
    }

    public function testClassSetInConstructorCanBeRetrieved()
    {
        $configurationObject = $this->getMockForAbstractClass(
            '\noFlash\TorrentGhost\Configuration\ConfigurationInterface'
        );
        $mockClassName = get_class($configurationObject);

        $this->subjectUnderTest = new ConfigurationFactory($mockClassName);
        $this->assertSame($mockClassName, $this->subjectUnderTest->getClassName());
    }


    public function testTryingToUseNonExistingClassNameUsingSetterProducesException()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Failed to get information about class FooBarClass');
        $this->subjectUnderTest->setClassName('FooBarClass');
    }

    public function testTryingToUseClassNameWhichDoesNotImplementConfigurationInterfaceUsingSetterProducesException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Invalid class given - it should implement ConfigurationInterface'
        );
        $this->subjectUnderTest->setClassName('\stdClass');
    }

    public function testTryingToUseAbstractClassNameInClassNameSetterProducesException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Given class \noFlash\TorrentGhost\Test\Stub\AbstractClassImplementingConfigurationInterfaceStub is not instantiable'
        );

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\AbstractClassImplementingConfigurationInterfaceStub'
        );
    }

    public function testClassSetUsingSetterCanBeRetrieved()
    {
        $configurationObject = $this->getMockForAbstractClass(
            '\noFlash\TorrentGhost\Configuration\ConfigurationInterface'
        );
        $mockClassName = get_class($configurationObject);

        $this->subjectUnderTest->setClassName($mockClassName);
        $this->assertSame($mockClassName, $this->subjectUnderTest->getClassName());
    }

    public function testTryingToSetNonArrayValueOfInstanceParametersInConstructorLeadsToError()
    {
        if (PHP_MAJOR_VERSION < 7) {
            /*
             * For explanation refer to links below:
             * - http://stackoverflow.com/questions/25570786/how-to-unit-test-type-hint-with-phpunit
             * - https://github.com/sebastianbergmann/phpunit/issues/178
             */
            $this->setExpectedException(get_class(new \PHPUnit_Framework_Error("", 0, "", 1)));

        } else {
            $this->setExpectedException('\TypeError');
        }

        new ConfigurationFactory(null, 'fooooo');
    }

    public function testInstanceParametersSetInConstructorCanBeRetrieved()
    {
        $params = ['foo' => 'bar', 'example' => 'parameter'];

        $factory = new ConfigurationFactory(null, $params);
        $this->assertSame($params, $factory->getInstanceParameters());
    }

    public function testTryingToSetNonArrayValueOfInstanceParametersUsingSetterLeadsToError()
    {
        if (PHP_MAJOR_VERSION < 7) {
            /*
             * For explanation refer to links below:
             * - http://stackoverflow.com/questions/25570786/how-to-unit-test-type-hint-with-phpunit
             * - https://github.com/sebastianbergmann/phpunit/issues/178
             */
            $this->setExpectedException(get_class(new \PHPUnit_Framework_Error("", 0, "", 1)));

        } else {
            $this->setExpectedException('\TypeError');
        }

        $this->subjectUnderTest->setInstanceParameters(123);
    }

    public function testInstanceParametersSetUsingSetterCanBeRetrieved()
    {
        $params = ['zzz' => 'foo', 'moew' => 'cat'];

        $this->subjectUnderTest->setInstanceParameters($params);
        $this->assertSame($params, $this->subjectUnderTest->getInstanceParameters());
    }

    public function testExceptionIsRaisedWhileTryingToBuiltWithoutClassNameSet()
    {
        $this->setExpectedException('\LogicException', 'You cannot build object without setting class name');
        $this->subjectUnderTest->build();
    }

    public function testBuildingObjectWithoutParametersProducesCorrectResult()
    {
        $validObjectExample = new \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub;

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $builtObject = $this->subjectUnderTest->build();
        $this->assertEquals($validObjectExample, $builtObject);
    }

    public function testParameterTargetingPrivateFieldWithoutSetterAbortsBuild()
    {
        $parameters = ['privateField' => 'and-its-value'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "privateField" property or any of the following methods: setprivateField, setPrivateField'
        );
        $this->subjectUnderTest->build();
    }

    public function testParameterTargetingProtectedFieldWithoutSetterAbortsBuild()
    {
        $parameters = ['protectedField' => 'foo-value'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "protectedField" property or any of the following methods: setprotectedField, setProtectedField'
        );
        $this->subjectUnderTest->build();
    }

    public function testParameterTargetingFieldWithPrivateSetterAbortsBuild()
    {
        $parameters = ['privateSetterField' => 'its-a-trap'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "privateSetterField" property or any of the following methods: setprivateSetterField, setPrivateSetterField'
        );
        $this->subjectUnderTest->build();
    }

    public function testParameterTargetingFieldWithProtectedSetterAbortsBuild()
    {
        $parameters = ['protectedSetterField' => 'moew-moew'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "protectedSetterField" property or any of the following methods: setprotectedSetterField, setProtectedSetterField'
        );
        $this->subjectUnderTest->build();
    }

    public function testParameterTargetingPublicFieldIsSet()
    {
        $parameters = ['publicField' => 'exampleValue'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        /** @var \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub $createdObject */
        $createdObject = $this->subjectUnderTest->build();
        $this->assertSame($parameters['publicField'], $createdObject->publicField);
    }

    public function testParameterTargetingFieldWithSetterIsSet()
    {
        $parameters = ['withSetter' => 'grump-grump'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        /** @var \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub $createdObject */
        $createdObject = $this->subjectUnderTest->build();
        $this->assertSame($parameters['withSetter'], $createdObject->getWithSetter());
    }

    //TODO all parameters should be case sensitive
    public function testParameterNamesTargetingFieldWithSetterAreNotCaseSensitive()
    {
        $parameters = ['weirdCaseParameter' => 'weird-value'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        /** @var \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub $createdObject */
        $createdObject = $this->subjectUnderTest->build();
        $this->assertSame($parameters['weirdCaseParameter'], $createdObject->getWEiRdCaSePaRaMeTeR());
    }

    public function testParameterNamesTargetingPublicFieldsHaveToBeUsedCaseSensitive()
    {
        $parameters = ['PuBlIcFieldWitHoutTrAp' => 'dummy-val'];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "PuBlIcFieldWitHoutTrAp" property or any of the following methods: setPuBlIcFieldWitHoutTrAp, setPuBlIcFieldWitHoutTrAp'
        );
        $this->subjectUnderTest->build();
    }

    public function testArrayParameterIsSetAsAnyOtherIfSetterOrPublicFieldIsAvailable()
    {
        $parameters = ['publicField' => ['exampleValue'], 'withSetter' => ['grump-grump']];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        /** @var \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub $createdObject */
        $createdObject = $this->subjectUnderTest->build();
        $this->assertSame($parameters['publicField'], $createdObject->publicField);
        $this->assertSame($parameters['withSetter'], $createdObject->getWithSetter());
    }

    public function testArrayParameterIsIteratedAndPassedToAddMethodNamedAfterCollectionFiledName()
    {
        $parameters = ['tests' => ['1', 'test', '3']];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        /** @var \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub $createdObject */
        $createdObject = $this->subjectUnderTest->build();
        $this->assertSame($parameters['tests'], $createdObject->getTests());
    }

    public function testArrayParameterIsIteratedAndPassedToAddMethodNamedAfterSingularCollectionFiledNameIfNoPluralAddMethodIsAvailable()
    {
        $parameters = ['items' => ['i1', 'i2', 'i99']];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        /** @var \noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub $createdObject */
        $createdObject = $this->subjectUnderTest->build();
        $this->assertSame($parameters['items'], $createdObject->getItems());
    }

    public function testParameterWithArrayValueWhichDoesNotHaveSetterNorAddedAbortsBuild()
    {
        $parameters = ['non-existing-fields' => ['val1', 'val2']];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "non-existing-fields" property or any of the following methods: setnon-existing-fields, setNon-existing-fields, addnon-existing-fields, addNon-existing-fields, addnon-existing-field, addNon-existing-field'
        );
        $this->subjectUnderTest->build();
    }

    public function testParameterWithArrayValueWhichHaveOnlyPrivateAdderAbortsBuild()
    {
        $parameters = ['privateAdderField' => ['x', 'y']];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "privateAdderField" property or any of the following methods: setprivateAdderField, setPrivateAdderField, addprivateAdderField, addPrivateAdderField'
        );
        $this->subjectUnderTest->build();
    }

    public function testParameterWithArrayValueWhichHaveOnlyProtectedAdderAbortsBuild()
    {
        $parameters = ['protectedAdderField' => ['a', 'b', 'c']];

        $this->subjectUnderTest->setClassName(
            '\noFlash\TorrentGhost\Test\Stub\WorkingClassImplementingConfigurationInterfaceStub'
        );
        $this->subjectUnderTest->setInstanceParameters($parameters);

        $this->setExpectedException(
            '\noFlash\TorrentGhost\Exception\UnknownConfigurationParameterException',
            'Failed to locate public "protectedAdderField" property or any of the following methods: setprotectedAdderField, setProtectedAdderField, addprotectedAdderField, addProtectedAdderField'
        );
        $this->subjectUnderTest->build();
    }
}
