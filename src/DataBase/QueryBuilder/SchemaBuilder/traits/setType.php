<?php

namespace Zeero\DataBase\QueryBuilder\SchemaBuilder\traits;

trait setType
{
    // process
    private function setsTypes(array $info)
    {
        $type = $info['type'];
        $name = $info['name'];
        $values = $info['values'];

        $values = array_map(function ($item) {
            return "\"{$item}\"";
        }, $values);

        $s =  " `{$name}` " . strtoupper($type) . " (" . implode(",", $values) . ")";

        $info['mod']['not null'] = '';

        if (isset($info['mod'])) {
            $this->modifiers($s, $info['mod']);
        }
        
        return $s;
    }


    // SET
    public function enum(string $name, array $values)
    {
        $this->dataTypes[]["enum"] = [$name, $values];
        return $this;
    }

    public function set(string $name, array $values)
    {
        $values = array_unique($values);
        $this->dataTypes[]["set"] = [$name, $values];
        return $this;
    }
}
