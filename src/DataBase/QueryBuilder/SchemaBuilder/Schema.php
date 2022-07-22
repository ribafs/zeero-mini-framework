<?php

namespace Zeero\Database\QueryBuilder\SchemaBuilder;

use Closure;



/**
 * Schema Builder
 * 
 * used to create table manipulation string
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
final class Schema
{

    /**
     * Get a CREATE SCHEMA string
     *
     * @param string $name
     * @param string $charset
     * @return string
     */
    public static function createSchema(string $name, string $charset = 'utf8')
    {
        return "CREATE SCHEMA IF NOT EXISTS `{$name}` DEFAULT CHARACTER SET {$charset}";
    }

    /**
     * Create a new table and the fields
     *
     * return a full 'create table' sql string
     *
     * @param string $name
     * @param Closure $action
     * @return string
     **/

    public static function create(string $name, Closure $action)
    {
        $table_obj = new Table;
        $action($table_obj);
        $definitions = $table_obj->results();
        $definitions = str_replace("+", ',', $definitions);
        $sql = "CREATE TABLE {$name} {$definitions}";
        return $sql;
    }


    /**
     * alter a table
     *
     * return a full 'alter table' sql string
     *
     * @param string $name
     * @param Closure $action
     * @return string
     **/

    public static function alter(string $name, Closure $action)
    {
        $table_obj = new Alter;
        $action($table_obj);
        $alter = $table_obj->results();
        $sql = [];

        foreach ($alter as $value) {
            $value = trim($value);
            $sql[] = "ALTER TABLE {$name} {$value};";
        }

        return implode(' ', $sql);
    }

    /**
     * Get one or more DROP TABLE string
     *
     * @param string|array $table
     * @return string
     */
    public static function drop($table)
    {
        if (is_array($table)) {
            $sql = '';

            foreach ($table as $value) {
                $sql .= " DROP TABLE {$value}; ";
            }

            return $sql;
        }

        $sql = "DROP TABLE {$table}";

        return $sql;
    }


    /**
     * Get one or more DROP TABLE IF EXISTS string
     *
     * @param string|array $table
     * @return string
     */
    public static function dropIfExists($table)
    {
        if (is_array($table)) {
            $sql = [];

            foreach ($table as $value) {
                $sql[] = "DROP TABLE IF EXISTS {$value};";
            }

            return implode(' ', $sql);
        }

        $sql =  "DROP TABLE IF EXISTS {$table}";

        return $sql;
    }


    /**
     * Get one or more TRUNCATE TABLE string
     *
     * @param string|array $table
     * @return string
     */
    public static function truncate($table)
    {
        if (is_array($table)) {
            $sql = [];
            foreach ($table as $value)
                $sql[] = "TRUNCATE TABLE {$value};";
            //
            return implode(' ', $sql);
        }

        $sql =  "TRUNCATE TABLE {$table};";

        return $sql;
    }
}
