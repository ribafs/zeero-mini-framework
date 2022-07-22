<?php

namespace Zeero\Database\Resource;

use Exception;
use stdClass;

/**
 * Resource class
 * 
 * used to register and return resource for models
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class Resource
{
    private static $resources = [];


    /**
     * register a resource
     *
     * @param string $resource_name
     * @param callable $factoryAction
     * @return void
     */
    public static function factory(string $resource_name, callable $factoryAction)
    {
        if (!array_key_exists($resource_name, self::$resources)) {
            self::$resources[$resource_name] = $factoryAction;
        }
    }

    /**
     * return a resource as array
     *
     * @param string $resource_name
     * @param object $object
     * @param mixed $extraInfo
     * @return array
     */
    public static function single(string $resource_name, object $object, $extraInfo = null)
    {
        if (!array_key_exists($resource_name, self::$resources))
            throw new Exception("Undefined Resource: '{$resource_name}' ");

        $callable = self::$resources[$resource_name];
        return $callable($object, (new ResourceHelper), $extraInfo);
    }


    /**
     * return a resource collection
     *
     * @param string $resource_name
     * @param array $data
     * @return array
     */
    public static function collection(string $resource_name, array $data, array $options = null)
    {
        if (!array_key_exists($resource_name, self::$resources))
            throw new Exception("Undefined Resource: '{$resource_name}' ");

        // teste if is a paginated data
        if (isset($data['data']) and isset($data['total']) and isset($data['pages'])) {
            $paginated = $data;
            $data = $data['data'];
        }

        $collection = [];

        if ($options) {
            if (isset($options['each'])) {
                $key = $options['each'];
            }
        }

        foreach ($data as $obj) {
            $single = self::single($resource_name, $obj);
            // 
            // custom key
            if (isset($key)) {

                if (strpos($key, ':') === 0) {
                    $item = substr($key, 1);

                    if (array_key_exists($item, $single)) {
                        $_key = $single[$item];
                        unset($single[$item]);
                    }
                }

                $single = [$_key ?? $key => $single];
            }

            // 
            $collection[] = $single;
        }

        if ($options) {
            if (isset($options['key'])) {
                $collection = [$options['key'] => $collection];
            }
        }

        if (isset($paginated)) {
            $paginated['data'] = $collection;
            return $paginated;
        }

        return $collection;
    }


    /**
     * 
     * return a model object to array
     *
     * @param object|array $object
     * @return array|null
     */
    public static function toArray($object)
    {
        // only accept array or single object
        if (!is_object($object) and !is_array($object)) return null;
        // get the array
        $object = is_array($object) ? $object : [$object];
        for ($i = 0; $i < count($object); $i++) {
            $obj = $object[$i];
            // check if is defined *only attribute
            // this attribute define the acceptable columns
            if (isset($obj->only)) {
                // get the array
                $only_info = is_array($obj->only) ? $obj->only : [$obj->only];
                $new_object = new stdClass;
                foreach ($only_info as $attr) {
                    // test if the attr exists in object
                    if (property_exists($obj, $attr)) {
                        $new_object->$attr = $obj->$attr;
                    }
                }

                $object[$i] = $new_object;
                unset($obj->only);
            } else {
                // check if need hide some properties
                if (isset($obj->hidden)) {
                    // get the array
                    $hidden_info = is_array($obj->hidden) ? $obj->hidden : [$obj->hidden];
                    foreach ($hidden_info as $attr) {
                        // test if the attr exists in object
                        if (property_exists($obj, $attr)) {
                            unset($obj->$attr);
                        }
                    }
                    // unset the *hidden attribute
                    unset($obj->hidden);
                }
            }
        }

        return $object;
    }
}
