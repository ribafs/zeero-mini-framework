<?php

namespace Zeero\DataBase\QueryBuilder;

use Exception;

abstract class Utils
{

    /**
     * Semi Join
     *
     * @param string $field1
     * @param string $field2
     * @param boolean $anti
     * @param boolean $reverseTables
     * @return string
     */
    protected function semiJoin($field1, $field2, $anti = false, bool $reverseTables = false)
    {
        $anti = $anti ? "NOT" : "";
        // first table
        $table_a = $this->table;
        $fields_from_a = $this->fields;

        // second table
        $table_b = $this->join['table'] ?? '';

        if ($table_b == '')
            throw new Exception("The Table must be joined with another");

        $fields_from_b = $this->join['results'] ?? null;
        // fields
        if ($fields_from_b)
            $fields = "{$fields_from_a} , {$fields_from_b}";
        else $fields = $fields_from_a;

        if ($reverseTables) {
            list($table_a, $table_b) = [$table_b, $table_a];
        }

        $sql = "SELECT {$fields} FROM {$table_a} WHERE {$field1} {$anti} IN ( ";
        $sql .= "SELECT {$field2} FROM {$table_b}) ;";

        return $sql;
    }


    /**
     * Modifier
     *
     * @param string $sql
     * @return void
     */
    protected function modifiers(&$sql)
    {
        // sql select statment elements order
        $sql_order = ['GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT', 'OFFSET'];
        // put if isset , but in order 
        foreach ($sql_order as $value) {
            if (isset($this->vars[$value])) {
                $sql .= " {$value} {$this->vars[$value]} ";
            }
        }
    }
}
