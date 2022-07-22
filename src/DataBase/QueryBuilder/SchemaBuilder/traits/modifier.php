<?php

namespace Zeero\DataBase\QueryBuilder\SchemaBuilder\traits;

trait modifier
{

    /**
     * set a modifier
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    private function change($key, $value)
    {
        $current_info = $this->currentField();
        $current_info[0]['mod'][$key] = $value;
        $this->updateField($current_info[2], $current_info[1], $current_info[0]);
        return $this;
    }

    /**
     * apply modifiers to a field
     *
     * @param string $str
     * @param array $mods
     * @return void
     *      */
    private function modifiers(&$str, array $mods)
    {
        // avoid the default 'not null'
        if (isset($mods['not null']) and isset($mods['null'])) unset($mods['not null']);

        // sql select statment elements order
        $sql_order = ['null', 'not null', 'default', 'after', 'before', 'unique'];
        
        // put if isset , but in order 
        foreach ($sql_order as $value) {
            if (isset($mods[$value])) {
                $str .= " {$value} {$mods[$value]} ";
            }
        }
    }

    public function size($size)
    {
        return $this->change('size', $size);
    }

    public function nullable()
    {
        return $this->change('null', '');
    }

    public function unsigned()
    {
        return $this->change('unsigned', '');
    }

    public function after(string $field)
    {
        return $this->change('after', "`{$field}`");
    }

    public function before(string $field)
    {
        return $this->change('before', "`{$field}`");
    }

    public function default($value)
    {
        return $this->change('default', "'{$value}'");
    }
}
