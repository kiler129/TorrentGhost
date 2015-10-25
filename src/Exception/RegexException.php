<?php
/*
 * This file is part of TorrentGhost project.
 * You are using it at your own risk and you are fully responsible
 *  for everything that code will do.
 *
 * (c) Grzegorz Zdanowski <grzegorz@noflash.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace noFlash\TorrentGhost\Exception;


use Exception;

/**
 * This exception should be used in case of invalid regular expression.
 */
class RegexException extends \RuntimeException
{
    /**
     * @var array Maps PREG_* codes to human-readable strings
     */
    private $pregErrors = [
        PREG_INTERNAL_ERROR => 'internal error',
        PREG_BACKTRACK_LIMIT_ERROR => 'backtrack limit exceeded',
        PREG_RECURSION_LIMIT_ERROR => 'recursion limit exceeded',
        PREG_BAD_UTF8_ERROR => 'invalid UTF-8 sequence',
        PREG_BAD_UTF8_OFFSET_ERROR => 'invalid UTF-8 offset'
    ];

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     *
     * @param string $message Description where regex error occurred.
     * @param string $regex Pattern which caused error.
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     */
    public function __construct($message, $regex, Exception $previous = null)
    {
        $lastError = preg_last_error();
        $lastErrorText = (isset($this->pregErrors[$lastError])) ? $this->pregErrors[$lastError] : "preg error #$lastError";

        $message .= ". Pattern $regex cannot be used" . (($lastError === PREG_NO_ERROR) ? '.' : ": $lastErrorText.");

        parent::__construct($message, 0, $previous);
    }
}
