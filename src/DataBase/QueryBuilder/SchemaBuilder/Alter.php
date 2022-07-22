<?php

namespace Zeero\DataBase\QueryBuilder\SchemaBuilder;

use Closure;
use Exception;



/**
 * Alter table
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class Alter
{
    private $alter = [];

    /**
     * add a new table field
     *
     * build a 'add column' sql string
     *
     * @return void
     **/
    public function add(Closure $actions)
    {
        $table = new Table;
        $actions($table);
        $sql = $table->results(true);
        $sql = substr($sql, 2, strlen($sql) - 4);

        if (!$sql) return null;

        if (strpos($sql, "+")) {
            $list = explode("+", $sql);
            foreach ($list as $value) {
                $value = trim($value);

                if (stripos($value, 'PRIMARY') === 0) {
                    $this->alter[] =  " ADD {$value} ";
                } else {
                    $this->alter[] =  " ADD COLUMN {$value} ";
                }
            }
        } else
            $this->alter[] =  "ADD COLUMN {$sql}";
    }


    /**
     * Add a Index to the table
     * 
     * @param column array|string
     * @return void
     */

    public function addIndex(string $type, string $column, $name = '')
    {
        $type = strtolower($type);
        $types = ['index', 'unique', 'primary', 'fulltext', 'spatial'];

        if (!in_array($type, $types)) {
            throw new Exception("Invalid key type: {$type}");
        }

        if (is_array($column) && $type == 'spatial') {
            throw new Exception("index: Spatial require only one field");
        }

        if (is_array($column)) {

            $column = array_map(function ($item) {
                return "`{$item}`";
            }, $column);

            $column = implode(" , ", $column);
        } else {
            $column = "`{$column}`";
        }

        $name = !empty($name) ? "`{$name}`" : '';
        $type = strtoupper($type);
        if($type == 'PRIMARY') $type .= ' KEY';

        $this->alter[] =  "ADD {$type} {$name} ({$column})";
    }


    /**
     * modify a table field
     *
     * build a 'modify' sql string
     *
     * @return void
     **/

    public function modify(Closure $actions)
    {
        $table = new Table;
        $actions($table);
        $sql = $table->results(true);
        $sql = substr($sql, 2, strlen($sql) - 4);

        if (!$sql) return null;

        if (strpos($sql, "+")) {
            $list = explode("+", $sql);
            $count = 0;
            foreach ($list as $value) {
                $value = trim($value);

                if (stripos($value, 'PRIMARY') === 0) {
                    $this->alter[$count - 1] .= ' primary key';
                } else {
                    $this->alter[] =  " MODIFY {$value} ";
                }

                $count += 1;
            }
        } else
            $this->alter[] =  "MODIFY {$sql}";
    }


    /**
     * change a column definition
     *
     * build a 'change' sql string
     *
     * @return void
     **/
    public function change(Closure $actions, $newname)
    {
        $table = new Table;
        $actions($table);
        $sql = $table->results(true);
        $sql = substr($sql, 2, strlen($sql) - 4);

        $item_regexp = "/\`(\w+)\`/";

        if (!$sql) return null;

        if (strpos($sql, "+")) {
            $list = explode("+", $sql);
            $i = 0;

            if (!is_array($newname)) {
                throw new Exception("The Columns Names must be a array");
            }

            if (count($newname) != count($list)) {
                throw new Exception("Invalid Columns Names number");
            }

            foreach ($list as $value) {
                preg_match($item_regexp, $value, $matches);
                $item = $matches[1];
                $name = $newname[$i];
                $value = str_replace("`{$item}`", "", $value);
                $this->alter[] .=  " CHANGE `{$item}` `{$name}` {$value} ";
                $i++;
            }
        } else {
            if (is_array($newname)) $newname = $newname[0];

            preg_match($item_regexp, $sql, $matches);
            $item = $matches[1];

            $sql = str_replace("`{$item}`", "", $sql);
            $this->alter[] =  "CHANGE `{$item}` `{$newname}` {$sql}";
        }
    }



    /**
     * drop one or more column
     *
     * build a 'drop column' sql string
     *
     * @return void
     **/

    public function dropColumn($column)
    {
        if (is_array($column)) {
            foreach ($column as $value) {
                $this->alter[] =  " DROP COLUMN {$value} ";
            }
        } else
            $this->alter[] =  " DROP COLUMN {$column} ";
    }

    /**
     * drop table constraint
     *
     * build a 'drop constraint' sql string
     *
     * @return void
     **/

    public function dropConstraint(string $name)
    {
        $this->alter[] =  " DROP CONSTRAINT `{$name}` ";
    }


    /**
     * rename the current table
     *
     * build a 'rename to' sql string
     *
     * @return void
     **/

    public function rename($newname)
    {
        $this->alter[] =  " RENAME TO {$newname} ";
    }


    /**
     * drop a index in current table
     *
     * build a 'drop index' sql string
     *
     * @return void
     **/

    public function dropIndex(string $index)
    {
        $this->alter[] =  " DROP INDEX `{$index}` ";
    }


    /**
     * drop the primary key in current table
     *
     * build a 'drop primary key' sql string
     *
     * @return void
     **/

    public function dropPrimary()
    {
        $this->alter[] =  " DROP PRIMARY KEY ";
    }

    
    /**
     * Build the ALTER array
     *
     * @throws Exception if no actions
     * @return array
     */
    public function results()
    {
        if (!isset($this->alter))
            throw new Exception("Empty Alter table actions");

        $this->alter = array_map('trim' , $this->alter);

        return $this->alter;
    }
}
