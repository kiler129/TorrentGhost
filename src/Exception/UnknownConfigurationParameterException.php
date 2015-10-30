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

namespace noFlash\TorrentGhost\Exception;

use Exception;

class UnknownConfigurationParameterException extends \RuntimeException
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * @inheritDoc
     */
    public function __construct($message, $parameterName, Exception $previous = null)
    {
        $this->parameterName = (string)$parameterName;

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string
     */
    public function getParameterName()
    {
        return $this->parameterName;
    }
}
