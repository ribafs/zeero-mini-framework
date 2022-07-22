<?php

namespace Zeero\facades;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

abstract class Flash
{
    public static function initialize(array $flashes)
    {
        $b = self::getFlashBag();
        $b->initialize($flashes);
    }

    public static function has(string  $type)
    {
        $b = self::getFlashBag();
        return $b->has($type);
    }

    public static function keys()
    {
        $b = self::getFlashBag();
        return  $b->keys();
    }

    public static function getStorageSkey()
    {
        $b = self::getFlashBag();
    }

    public static function clear()
    {
        $b = self::getFlashBag();
        $b->clear();
    }

    //
    public static function set(string $type, $messages)
    {
        $b = self::getFlashBag();
        $b->set($type, $messages);
    }
    //
    public static function setAll(array $messages)
    {
        $b = self::getFlashBag();
        $b->setAll($messages);
    }



    public static function add(string $type, $message)
    {
        $b = self::getFlashBag();
        $b->add($type, $message);
    }
    //

    public static function all()
    {
        $b = self::getFlashBag();
        return $b->all();
    }

    public static function get(string  $type,  $default = [])
    {
        $b = self::getFlashBag();
        return $b->get($type,  $default);
    }

    public static function peek(string  $type,  $default = [])
    {
        $b = self::getFlashBag();
        return $b->peek($type,  $default);
    }

    public static function peekAll()
    {
        $b = self::getFlashBag();
        return $b->peekAll();
    }

    public static function getFlashBag(): FlashBag
    {
        return app("session")->getFlashBag();
    }
}
