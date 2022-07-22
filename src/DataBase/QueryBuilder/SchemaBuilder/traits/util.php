<?php

namespace Zeero\DataBase\QueryBuilder\SchemaBuilder\traits;

use Exception;

trait util
{

    /**
     * get all datatypes 
     *
     * @return array
     */
    public static function __datatypes()
    {
        $textual = ["varchar", "char", "text", "blob" , 'tinytext' , 'longtext' , 'mediumtext'];
        $integers = ["tinyint", "int", "smallint", "mediumint", "bigint"];
        $floats = ["real", "float", "double"];
        $dates = ["time", "year", "datetime", "date", "timestamp"];
        $sets = ["set", "enum"];

        $types = [$textual, $integers, $floats, $dates, $sets];
        return $types;
    }

    /**
     * get the datatype class fullname
     * 
     * @return string
     */
    private function getClass(string $type)
    {
        $types = self::__datatypes();
        $classnames = ['textual', 'integer', 'float', 'date', 'set'];
        $classname = '';

        foreach ($types as $key => $value) {
            if (in_array($type, $value)) $classname = $classnames[$key];
        }

        return $classname;
    }


    /**
     * get the information about the datatype item
     *
     * @param array $item
     * @return array
     */
    private function getInfo(array $item)
    {

        $key = array_keys($item)[0];
        $data = $item[$key];
        $info = ['type' => $key, 'name' => $data[0], 'size' => $data[1] ?? null];
        $info['class'] = $this->getClass($info['type']);
        // float type require a third value
        if ($info['class'] == "float")
            $info['digits'] = $data[2] ?? null;
        // 
        if ($data[1] && $info['class'] == 'set') {
            $info['values'] = $data[1];
            unset($info['size']);
        }

        // slice the array
        $data = array_slice($data, 2);
        // mege with info array
        $info = array_merge($info, $data);

        return $info;
    }


    /**
     * get the current field
     *
     * @return array
     */
    private function currentField()
    {
        if (!$this->dataTypes) {
            throw new Exception("No field selected");
        }

        $count = count($this->dataTypes);
        $field = $this->dataTypes[$count - 1];
        $key = array_keys($field)[0];
        $info = $field[$key];
        return [$info, $key, $count - 1];
    }


    /**
     * update a field
     *
     * @param int $index
     * @param string $key
     * @param array $info
     * @return void
     */
    private function updateField($index, $key, $info)
    {
        $this->dataTypes[$index][$key] = $info;
    }
}
