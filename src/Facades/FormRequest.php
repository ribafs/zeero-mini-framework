<?php

namespace Zeero\facades;

use Symfony\Component\HttpFoundation\Request;


class FormRequest
{
    protected static $data;
    private static Request $request;

    protected static function initialize()
    {
        $r = new Request(
            $_GET,
            $_POST,
            [],
            $_FILES,
            [],
            [],
            json_decode(file_get_contents("php://input"))
        );

        self::$data = array_merge($r->request->all(), $r->query->all());
        self::$request = $r;
    }

    public static function all()
    {
        self::initialize();
        return self::$data;
    }

    public static function getContent($asResource = false)
    {
        self::initialize();
        return self::$request->getContent($asResource);
    }

    public static function has($item)
    {
        self::initialize();
        return self::$request->query->has($item);
    }

    public static function all_get()
    {
        self::initialize();
        return self::$request->query->all();
    }

    public static function all_post()
    {
        self::initialize();
        return self::$request->request->all();
    }

    public static function get(string $key)
    {
        self::initialize();
        return self::$data[$key] ?? null;
    }

    public static function set_get(string $key, $value)
    {
        self::initialize();
        self::$request->query->set($key, $value);
    }

    public static function set_post(string $key, $value)
    {
        self::initialize();
        self::$request->request->set($key, $value);
    }

    public static function filter_get(string $key, int $filter = \FILTER_DEFAULT, $default = null, $options = [])
    {
        self::initialize();
        return self::$request->query->filter($key, $default, $filter, $options);
    }

    public static function filter_post(string $key, int $filter = \FILTER_DEFAULT, $default = null, $options = [])
    {
        self::initialize();
        return self::$request->request->filter($key, $default, $filter, $options);
    }
}
