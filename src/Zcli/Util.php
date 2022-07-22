<?php

namespace Zeero\Zcli;



/**
 * Util Class
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
final class Util
{

    /**
     * Transform a Associative array to a string 
     *
     * @param array $data
     * @return string
     */
    public static function DataToArray(array $data)
    {
        // remove the property *attributes
        array_shift($data);

        $data = array_map(function ($i) {

            // ignore objects
            if (!is_object($i)) {

                if (is_null($i)) $i = '';

                $i = trim($i);

                if ($i != '') {
                    return "'{$i}'  ";
                }
            }
            // 
        }, $data);

        $result = implode(', ', $data);
        // remove the last comma
        $result = substr($result, 0, strlen($result) - 4);

        return "\t\t\t[" . $result . '],';
    }

}
