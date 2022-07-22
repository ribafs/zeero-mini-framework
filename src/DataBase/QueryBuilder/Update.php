<?php

namespace Zeero\DataBase\QueryBuilder;

use Exception;
use Zeero\DataBase\QueryBuilder\traits\CondictionTrait;

/**
 * Build UPDATE Statments
 * 
 * @author Carlos Bumba git:@CarlosNio
 */
class Update
{
    use CondictionTrait;

    private $table;
    private $limit;
    private $data;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * set the data to update
     *
     * @param array $data
     * @return void
     */
    public function set(array $data)
    {
        $this->data = $data;
    }


    /**
     * set the limit
     *
     * @param mixed $limit
     * @return void
     */
    public function limit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Bulk update
     * 
     * When updating multiple rows with diﬀerent values it is much quicker to use a bulk update.
     * 
     * By bulk updating only one query can be sent to the server instead of one query for each row to update.
     * 
     * @author Carlos Bumba git@CarlosNio
     */
    public function bulk_update(string $column, array $data)
    {

        if (count($data) < 3) {
            throw new Exception("Argument array *data must contain 3 items");
        }

        if (!is_array($data[1]) || !is_array($data[2])) {
            throw new Exception("Argument *data must contain two arrays in index 1 and 2 ");
        }

        if (count($data[1]) != count($data[2])) {
            throw new Exception("Argument *data must contain two arrays in index 1 and 2 with same items number");
        }

        // start
        $sql = "UPDATE {$this->table} SET {$column} = ( CASE ";
        // condiction item
        $cond_item = $data[0];
        $sql .= $cond_item;
        // insert placeholders
        foreach ($data[1] as $index => $value)
            $sql .= " WHEN {$value} THEN ? ";
        // finish
        $sql .= " END ) WHERE {$cond_item} IN (" . implode(",", $data[1]);
        $sql .= ");";
        // store the data
        $this->params = $data[2];
        $this->sql = $sql;
    }

    /**
     * Build the sql string
     *
     */
    public function results()
    {
        if (isset($this->sql)) {
            return [$this->sql, $this->params];
        }

        if (!isset($this->data)) return null;

        // update statment
        $sql = "UPDATE `{$this->table}` ";
        $data = $this->data;
        // fields to update
        $keys = array_keys($data);
        $sql .= 'SET ';
        // set parameters
        foreach ($keys as $index => $key) {
            $sql .= " `{$key}` = ? ,";
        }
        // remove the last comma
        $sql = substr($sql, 0, strlen($sql) - 1);
        // condicitions
        $sql .= $this->ProcessCond();

        if (isset($this->limit)) $sql .= ' LIMIT ' . $this->limit;

        $sql .= ";";
        // data to insert in parameters
        $data = array_values($data);
        return [$sql, array_merge($data, $this->map['PARAMS'] ?? [])];
    }
}
