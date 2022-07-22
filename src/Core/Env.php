<?php

namespace Zeero\Core;

use Zeero\Core\Utils\Envfile;

/**
 * Abstract Class That Represent the application .env file
 * 
 * @author  carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class Env
{
    private static $EnvFile;

    /**
     * Set a new EnvFile instance
     *
     * @param Envfile $instance
     * @return void
     */
    public static function setEnvFile(Envfile $instance)
    {
        self::$EnvFile = $instance;
    }

    /**
     * Test if a Item is set in env file
     *
     * @param string $key
     * @return boolean
     */
    public static function has(string $key)
    {
        return self::getDictionary()->has($key);
    }

    /**
     * Get a Item in Env file
     *
     * @param string $key
     * @return string|null
     */
    public static function get(string $key)
    {
        return self::getDictionary()->get($key);
    }

    /**
     * Set a new Item in env file
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function set(string $key, string $value)
    {
        return self::$EnvFile->addItem($key, $value);
    }

    /**
     * Replace a Item value
     *
     * @param string $key the item
     * @param string $value the new value
     * @return boolean
     */
    public static function replace(string $key, string $value)
    {
        return self::$EnvFile->updateItem($key, $value);
    }

    /**
     * Remove a Item in env file
     *
     * @param string $key
     * @return void
     */
    public static function remove(string $key)
    {
        return self::$EnvFile->removeItem($key);
    }


    /**
     * Get the Dictionary Instance of all items in env file
     *
     * @return Zeero\Utils\Dictionary
     */
    public static function getDictionary()
    {
        return self::$EnvFile->getDictionary();
    }
}
