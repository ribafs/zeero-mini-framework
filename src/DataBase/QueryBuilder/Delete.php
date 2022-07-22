<?php

namespace Zeero\DataBase\QueryBuilder;


/**
 * Build DELETE Statments
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class Delete
{
    use traits\CondictionTrait;
    use traits\JoinTrait;

    private $table;
    private $fields;
    private $order;
    private $limit;
    private $join;

    public function __construct($table)
    {
        if (is_array($table)) $table = implode(" , ", $table);
        $this->table = $table;
    }

    /**
     * set the order
     *
     * @param string $field
     * @param string $mode
     * @return this
     */
    public function order(string $field, string $mode = 'ASC')
    {
        $this->order = "ORDER BY {$field} {$mode}";
        return $this;
    }


    /**
     * set the deletion limit
     *
     * @param int|string $n
     * @return this
     */
    public function limit($n)
    {
        $this->limit = "LIMIT {$n}";
        return $this;
    }


    /**
     * Get the results
     *
     * @return array
     */
    public function results()
    {
        if (isset($this->fields)) {
            if (str_word_count($this->table) != 1) {
                $this->fields .= explode(' ', $this->table)[1];
            }
        }

        // delete statment
        $sql = "DELETE {$this->fields} FROM {$this->table} ";

        if ($j = $this->getJoin()) {
            $sql .= $j;
        }

        // condicitions
        $sql .= $this->ProcessCond();
        if (isset($this->limit)) $sql .= $this->limit;
        $sql .= ";";
        // data to insert in parameters
        return [$sql, $this->map['PARAMS'] ?? []];
    }
}
