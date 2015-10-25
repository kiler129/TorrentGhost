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

namespace noFlash\TorrentGhost\Http;

/**
 * Object of this class holds multiple HTTP cookies.
 * CookiesBag supports iterating or accessing like array.
 *
 * @todo Values & names validation
 */
class CookiesBag implements \ArrayAccess, \Iterator
{
    /**
     * @var array Internal array holding all cookies. Example: ['key' => ['kEy', 'vaLuE'], 'test' => ['TesT', 'abc']]
     */
    private $cookies = [];

    /**
     * @param array $cookies Array of two elements arrays containing key and value for cookies, example: [['c1'=>'v1'],
     *     ['c2'=>'v2']]
     *
     * @return CookiesBag
     * @throws \RuntimeException Will be thrown if mallformed $cookies array was passed.
     */
    public static function fromArray(array $cookies)
    {
        $cookiesBag = new self;
        foreach ($cookies as $key => $cookie) {
            if (count($cookie) !== 2) {
                throw new \RuntimeException("Unexpected value at offset $key - array length invalid");
            }

            try {
                $cookiesBag->add(reset($cookie), next($cookie));

            } catch(\Exception $e) {
                throw new \RuntimeException("Failed to add cookie from offset $key", 0, $e);
            }
        }

        return $cookiesBag;
    }

    /**
     * Adds new cookie to bag. Method behaves exactly like {set()}, but checks if cookie already exists.
     *
     * @param string $key Case insensitive cookie name.
     * @param string $val Cookie value. All types are internally casted to string.
     *
     * @throws \LogicException Thrown if cookie already exists.
     * @see set()
     */
    public function add($key, $val)
    {
        if ($this->has($key)) {
            throw new \LogicException("Cannot add $key - already exists");
        }

        $this->set($key, $val);
    }

    /**
     * Checks whatever cookie with given nam exists in current bag.
     *
     * @param string $key Case insensitive cookie name.
     *
     * @return bool True if found, false otherwise.
     */
    public function has($key)
    {
        return isset($this->cookies[strtolower($key)]);
    }

    /**
     * Sets cookie value. If cookie already exists it will be overwritten.
     *
     * @param string $key Case insensitive cookie name.
     * @param string $val Cookie value. All types are internally casted to string.
     */
    public function set($key, $val)
    {
        $this->cookies[strtolower($key)] = [$key, $val];
    }

    /**
     * Clears all cookies.
     */
    public function reset()
    {
        $this->cookies = [];
    }

    /**
     * Provides number of cookies inside a bag.
     *
     * @return int Number of cookies.
     */
    public function count()
    {
        return count($this->cookies);
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $current = current($this->cookies);

        return ($current === false) ? false : $current[1];
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        next($this->cookies);
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        $current = current($this->cookies);

        return ($current === false) ? false : $current[0];
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return (current($this->cookies) !== false);
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        reset($this->cookies);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException Unknown offset requested
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * Provides value for cookie by given name.
     *
     * @param string $key Case insensitive cookie name.
     *
     * @return string
     * @throws \InvalidArgumentException Thrown if cookie with given name does not exist in current bag.
     * @todo Optimize it to not execute strtolower() twice
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException("Cannot get $key - it does not exist");
        }

        return $this->cookies[strtolower($key)][1];
    }

    /**
     * Removes cookie from bag.
     * Method follows unset() convention and will not complain if you try to remove non-existent cookie.
     *
     * @param string $key Case insensitive cookie name.
     */
    public function delete($key)
    {
        unset($this->cookies[strtolower($key)]);
    }
}
