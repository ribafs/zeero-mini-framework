<?php

namespace Zeero\DataBase\QueryBuilder;

use Zeero\Core\Exceptions\EmptyException;

class Join
{
    // 
    protected string $table;
    // 
    protected string $onString;
    // 
    protected $fields = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function setFields(array $list)
    {
        $table = $this->table;

        if (str_word_count($table) > 1) {
            $parts = explode(' ', $table);
            $table = end($parts);
        }

        $list = array_map(function ($i) use ($table) {
            return $table . '.' . $i;
        }, $list);

        $this->fields = $list;
        return $this;
    }

    public function getFields()
    {
        return implode(',' , $this->fields);
    }

    public function on(string $left, string $right)
    {
        $this->onString = "{$left} = {$right}";
        return $this;
    }

    public function __toString()
    {

        if (!isset($this->onString)) {
            throw new EmptyException("The JOIN require the equality comparison");
        }

        $sql = " JOIN {$this->table} ON {$this->onString} ";

        return $sql;
    }
}
