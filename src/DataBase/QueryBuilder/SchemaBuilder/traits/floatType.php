<?php

namespace Zeero\DataBase\QueryBuilder\SchemaBuilder\traits;

trait floatType
{
    // process
    private function floatType(array $info)
    {
        $type = $info['type'];
        $name = $info['name'];
        $size = $info['size'];
        $digits = $info['digits'];
        $info['mod']['not null'] = '';

        $str = " `{$name}` {$type}({$size} , {$digits}) ";
        
        if (isset($info['mod'])) {
            $this->modifiers($str, $info['mod']);
        }

        return $str;
    }


    // FLOATS
    private function _f_register(string $key, $name, int $size, int $digits = null)
    {
        $size = 25;
        $digits ??= 3;
        $this->dataTypes[][$key] = [$name, $size, $digits];
        return $this;
    }

    public function float(string $name, int $digits = null)
    {
        $size = 25;
        return $this->_f_register('float', $name, $size, $digits);
    }

    public function double(string $name, int $digits = null)
    {
        $size = 25;
        return $this->_f_register('double', $name, $size, $digits);
    }

    public function real(string $name, int $digits = null)
    {
        $size = 25;
        return $this->_f_register('real', $name, $size, $digits);
    }
}
