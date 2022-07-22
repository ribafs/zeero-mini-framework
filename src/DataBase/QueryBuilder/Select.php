<?php

namespace Zeero\DataBase\QueryBuilder;

use Zeero\DataBase\QueryBuilder\traits\CondictionTrait;
use Zeero\DataBase\QueryBuilder\traits\JoinTrait;
use Zeero\DataBase\QueryBuilder\traits\CaseTrait;

/**
 * Build Select Statments
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
final class Select extends Utils
{
    use CondictionTrait;
    use JoinTrait;
    use CaseTrait;

    protected $table;
    protected $distinct;
    protected $fields;
    protected $vars;
    protected $join;

    public function __construct(string $table, bool $distinct = false)
    {
        $this->table = $table;
        $this->distinct = $distinct ? 'DISTINCT' : '';
    }

    public function getTable()
    {
        return $this->table;
    }

    // 
    public function having(string $str, array $params = [])
    {
        $this->vars['HAVING'] = $str;
        $this->map['PARAMS'] = array_merge($this->map['PARAMS'] ?? [],  $params);
    }

    public function limit($n)
    {
        $this->vars['LIMIT'] = $n;
        return $this;
    }

    public function order($value)
    {
        $this->vars['ORDER BY'] = $value;
        return $this;
    }

    public function group($value)
    {
        $this->vars['GROUP BY'] = $value;
        return $this;
    }

    /**
     * Left Semi Join
     *
     * @param string $field1
     * @param string $field2
     * @param boolean $anti
     * @return string
     */
    public function leftSemiJoin(string $field1, string $field2, bool $anti = false)
    {
        return $this->semiJoin($field1, $field2, $anti);
    }


    /**
     * Right Semi Join
     *
     * @param string $field1
     * @param string $field2
     * @param boolean $anti
     * @return string
     */
    public function rightSemiJoin(string $field1, string $field2, bool $anti = false)
    {
        return $this->semiJoin($field1, $field2, $anti, true);
    }

    /**
     * get SELECT result
     *
     * @return array
     */
    public function results()
    {
        // condictions
        $conds = $this->ProcessCond();

        // if already was builded a sql return it
        if (isset($this->sql)) {
            $this->sql .= $conds;
            $this->modifiers($this->sql);
            return [$this->sql, $this->map['PARAMS'] ?? null];
        }

        if (count($this->joinSentences)) {
            $join = $this->getJoin();
        }

        // result fields
        if (!isset($this->fields))
            $fields = '*';
        else
            $fields = $this->fields;

        // case condictions
        if (isset($this->map['WHEN'])) {

            if (isset($this->fields)) {
                $fields = " {$this->caseCond()} , {$fields}";
            } else {
                $fields = $this->caseCond();
            }
        }

        $sql = "SELECT {$this->distinct} {$fields} FROM {$this->table} ";

        if (isset($join)) $sql .= $join;

        // append the condictions
        $sql .= $conds;
        // modifiers
        $this->modifiers($sql);

        // result
        return [$sql . " ;", $this->map['PARAMS'] ?? null];
    }


    /**
     * Add a SubSelect instance
     *
     * this method merge the instance params with the current params
     * 
     * @param Select $instance
     * @return void
     */
    public function appendSubSelect(Select $instance)
    {
        $params = $instance->getParams();
        $this->map['PARAMS'] = array_merge($this->map['PARAMS'] ?? [], $params ?? []);
    }


    /**
     * 
     * add a subselect string
     *
     * @return string
     */
    public function __toString()
    {
        return substr($this->results()[0], 0, strlen($this->results()[0]) - 1);
    }
}
