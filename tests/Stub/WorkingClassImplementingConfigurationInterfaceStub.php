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

namespace noFlash\TorrentGhost\Test\Stub;

use noFlash\TorrentGhost\Configuration\ConfigurationInterface;

class WorkingClassImplementingConfigurationInterfaceStub implements ConfigurationInterface
{
    public    $publicField            = 'public';
    public    $publicFieldWithoutTrap = 'public2';
    protected $protectedField         = 'protected';
    private   $privateField           = 'private';
    private   $withSetter;
    private   $wEiRdCaSePaRaMeTeR;
    private   $tests                  = [];
    private   $items                  = [];

    public function getWithSetter()
    {
        return $this->withSetter;
    }

    public function setWithSetter($withSetter)
    {
        $this->withSetter = $withSetter;
    }

    /**
     * @return mixed
     */
    public function getWEiRdCaSePaRaMeTeR()
    {
        return $this->wEiRdCaSePaRaMeTeR;
    }

    /**
     * @param mixed $wEiRdCaSePaRaMeTeR
     */
    public function setWEiRdCaSePaRaMeTeR($wEiRdCaSePaRaMeTeR)
    {
        $this->wEiRdCaSePaRaMeTeR = $wEiRdCaSePaRaMeTeR;
    }

    //Some traps for tests ;)
    public function setPublicField()
    {
        throw new \LogicException(__METHOD__ . ' wasn\'t expected to be called.');
    }

    public function isValid()
    {
        return false;
    }

    public function addTests($test)
    {
        $this->tests[] = $test;
    }

    public function addTest()
    {
        //Plural form of add is available which should be called first since it's closer match
        throw new \LogicException(__METHOD__ . ' wasn\'t expected to be called.');
    }

    public function getTests()
    {
        return $this->tests;
    }

    public function addItem($item)
    {
        $this->items[] = $item;
    }

    public function getItems()
    {
        return $this->items;
    }

    protected function setProtectedSetterField()
    {
        throw new \LogicException(__METHOD__ . ' wasn\'t expected to be called.');
    }

    protected function setProtectedAdderField()
    {
        throw new \LogicException(__METHOD__ . ' wasn\'t expected to be called.');
    }

    private function setPrivateSetterField()
    {
        throw new \LogicException(__METHOD__ . ' wasn\'t expected to be called.');
    }

    private function setPrivateAdderField()
    {
        throw new \LogicException(__METHOD__ . ' wasn\'t expected to be called.');
    }
}
