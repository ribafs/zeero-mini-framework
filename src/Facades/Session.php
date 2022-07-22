<?php

namespace Zeero\facades;


abstract class Session
{

    public static function count()
    {
        return app("session")->count();
    }

    public static function isEmpty()
    {
        return app("session")->isEmpty();
    }

    //
    public static function all()
    {
        return app("session")->all();
    }

    public static function has(string $name)
    {
        return app("session")->has($name);
    }


    public static function get(string $name, $default = null)
    {
        return app("session")->get($name, $default);
    }


    public static function set(string $name, $value)
    {
        return app("session")->set($name, $value);
    }

    public static function replace(array $list)
    {
        return app("session")->replace($list);
    }


    public static function clear()
    {
        return app("session")->clear();
    }

    public static function remove(string $name)
    {
        return app("session")->remove($name);
    }


    //
    public static function invalidate(int $lifetime = null)
    {
        return app("session")->invalidate($lifetime);
    }

    public static function migrate(bool $destroy = false, int $lifetime = null)
    {
        return app("session")->migrate($destroy, $lifetime);
    }

    //


    // INFO
    public static function getId()
    {
        return app("session")->getId();
    }

    public static function setId(string $id)
    {
        return app("session")->setId($id);
    }

    public static function getName()
    {
        return app("session")->getName();
    }

    public static function setName(string $name)
    {
        return app("session")->setName($name);
    }
}
