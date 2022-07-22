<?php

namespace Zeero\facades;

use Symfony\Component\HttpFoundation\Request;

class Cookie
{
    private static Request $request;

    protected static function initialize()
    {
        $r = new Request(
            [],
            [],
            [],
            $_COOKIE,
            [],
            []
        );

        self::$request = $r;
    }

    public static function get(string $key, $default = null)
    {
        self::initialize();
        return self::$request->cookies->get($key, $default);
    }

    public static function has(string $key)
    {
        self::initialize();
        return self::$request->cookies->has($key);
    }

    public static function remove(string $key)
    {
        self::initialize();
        return self::$request->cookies->remove($key);
    }

    public static function keys(string $key)
    {
        self::initialize();
        return self::$request->cookies->keys($key);
    }

    public static function all(string $key = null): array
    {
        self::initialize();
        return self::$request->cookies->all();
    }

    public static function replace(array $inputs = [])
    {
        self::initialize();
        self::$request->cookies->replace($inputs);
    }

    public static function add(array $inputs = [])
    {
        self::initialize();
        self::$request->cookies->add($inputs);
    }

    public static function set(string $key, $value)
    {
        self::initialize();
        self::$request->cookies->set($key, $value);
    }


    public static function  filter(string $key, $default = null, int $filter = \FILTER_DEFAULT, $options = [])
    {
        self::initialize();
        return self::$request->cookies->filter($key, $default, $filter, $options);
    }
}
