<?php

namespace Zeero\Core\Utils;

use Exception;


/**
 * A Abstract Class for Regex Manipulation
 * 
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class RegexPatterns
{

    /**
     * Test a Pattern 
     *
     * @param string $key
     * @param mixed $value
     * @param array $map
     * @return int
     */
    private static function IterativeTest(string $key, $value, array $map)
    {
        $test = preg_match("/^" . $map[$key] . '$/', $value);

        // start from 2
        if (isset($map["{$key}-2"]) and $test == false) {
            // the max is 6
            for ($i = 2; $i < 6; $i++) {
                $_key = "{$key}-" . $i;
                // test the next key
                if (isset($map[$_key]))
                    $test = preg_match("/^" . $map[$_key] . '$/', $value);

                // if success stop the loop
                if ($test) break;
            }
        }

        return $test;
    }


    /**
     * test a regex
     *
     * @param string $key
     * @param mixed $value
     * @return int|null
     */
    public static function test(string $key, $value)
    {
        $map = self::getAll();

        if (!array_key_exists($key, $map))
            throw new Exception("Regexp Type: '$key' Not Found");

        return self::IterativeTest($key, $value, $map);
    }


    /**
     * get a regex
     *
     * @param string $key
     * @return string|null
     */
    public static function get(string $key)
    {
        $map = self::getAll();

        if (array_key_exists($key, $map)) return $map[$key];
    }

    /**
     * get all stored regex
     *
     * @return array
     */
    public static function getAll()
    {
        return [
            'number' => '\d+',
            'digit' => '\d',
            'p-number' => '[1-9]+[0-9]*',
            'p-decimal-number' => '(^\d*\.?\d*[0-9]+\d*$)|(^[0-9]+\d*\.\d*$)',
            'zip-code' => '[0-9]\{5\}(-[0-9]\{4\})?',
            'social-security-number' => '[0-9]\{3\}-[0-9]\{2\}-[0-9]\{4}',
            'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
            'date-str' => '[A-Z][a-z][a-z] [0-9][0-9]*, [0-9]\{4}',
            'date-2' => '(\d{1,2})\/(\d{1,2})\/(\d{2}|(19|20)\d{2})',
            'date-3' => '\b([0][1-9]|[1-9]|1[012])([\/-]|\s)([0][1-9]|[0-9]|1[0-9]|2[0-9]|3[0-1])([\/-]|\s)\d{2,4}',
            "pt_string" => "[a-zA-Z0-9\sáàâãêéèóòõôíìûúùç\-\_]+",
            "pt_alfanum" => "[a-zA-Z0-9\sáàâãêéèóòõôíìûúùç]+",
            "char" => "\w",
            'string' => '\w+',
            'alfa' => '[a-zA-Z]+',
            'alfa_text' => '[a-zA-Z\s]+',
            "alfa_str" => "[a-zA-Z\sáàâãêéèóòõôíìûúùç]+",
            'alfanum' => '[a-zA-Z0-9]+',
            'alfanum_text' => '[a-zA-Z0-9\s]+',
            'text' => '\w+\+?',
            "_text" => ".+",
            "datetime-local" => "\d{4}-\d{2}-\d{2}T\d{2}:\d{2}",
            "datetime" => "[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\} \d{2}:\d{2}:\d{2}",
            "url_title" => "[\w\-\_]+",
            'uuid' => '[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9\-]{12}',
            'email' => '[\\w\\-]+(\\.[\\w\\-]+)*@([A-Za-z0-9-]+\\.)+[A-Za-z]{2,4}',
            'email-2' => '[\w-]+(\.[\w-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)*?\.[a-z]{2,6}|(\d{1,3}\.){3}\d{1,3})(:\d{4})?',
            'email-3' => '([\w\.*\-*]+@([\w]\.*\-*)+[a-zA-Z]{2,9}(\s*;\s*[\w\.*\-*]+@([\w]\.*\-*)+[a-zA-Z]{2,9})*)',
            'email-4' => '([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})*',
            'email-5' => '([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})',
            'ip' => '((?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))*'
        ];
    }
} 
