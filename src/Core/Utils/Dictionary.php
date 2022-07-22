<?php
namespace Zeero\Core\Utils;


/**
 * The Dictionary class
 * 
 * @author  carlos bumba <carlosbumbanio@gmail.com>
 */
class Dictionary
{
    /**
     * the dictionary data
     *
     * @var array
     */
    protected array $data = [];


    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * get all items in dictionary
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * get all items keys
     *
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * check if a item exists in dictionary
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * get the value of a item
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): string|null
    {
        if ($this->has($key)) return $this->data[$key];

        return null;
    }

    
    /**
     * Replace a value in dictionary
     *
     * @param string $key
     * @param string $value
     * @return boolean|null
     */
    public function replace(string $key, string $value): bool|null
    {
        if ($this->has($key)) {
            $this->data[$key] = $value;
            return true;
        }
    }

    /**
     * add a new pair in dictionary
     *
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public function set(string $key, string $value): bool
    {
        $this->data[$key] = $value;
        return true;
    }



    /**
     * Remove a item in dictionary
     *
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public function remove(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->data[$key]);
            return true;
        }

        return false;
    }
    
}

