<?php

namespace Zeero\Database;

use Exception;
use Zeero\Core\Env;


/**
 * Retrieve information about the current database schema
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
final class SchemaInfo
{

    /**
     * Get informations about tables in current schema
     *
     * @throws Exception if the database was not selected
     * @return array
     */
    public static function getTables()
    {
        $dbname = Env::get('DB_NAME');

        if (is_null($dbname)) {
            throw new Exception("DATABASE NOT SELECTED");
        }

        $query = "SELECT TABLE_NAME, CREATE_TIME, UPDATE_TIME, ENGINE FROM INFORMATION_SCHEMA.tables where table_schema = ?  order by CREATE_TIME";
        return DataBase::execute([$query, [$dbname]])->fetchAll();
    }


    /**
     * Table Exists
     *
     * @param string $name
     * @return bool
     */
    public static function tableExists(string $name)
    {
        $tables = self::getTables();
        $name = strtolower($name);

        foreach ($tables as $key => $value) {
            if (strtolower($value['TABLE_NAME']) == $name) return true;
        }

        return false;
    }


    /**
     * Get informations about columns in table
     *
     * @param string $table the table name
     * @throws Exception if the database was not selected
     * @return array
     */
    public static function getColumns(string $table)
    {
        $dbname = Env::get('DB_NAME');

        if (is_null($dbname)) {
            throw new Exception("DATABASE NOT SELECTED");
        }

        $sql = "SELECT COLUMN_NAME , IS_NULLABLE, COLUMN_DEFAULT , COLUMN_TYPE, DATA_TYPE,  EXTRA, COLUMN_KEY, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH 
		           FROM INFORMATION_SCHEMA.columns
		            WHERE table_schema = ? AND table_name = ?;";

        return DataBase::execute([$sql, [$dbname, $table]])->fetchAll();
    }


    /**
     * get table foreign_keys
     *
     * @param string $table
     * @return array
     */
    public static function getTableForeignKeyConstraint(string $table)
    {
        $dbname = Env::get('DB_NAME');

        if (is_null($dbname)) {
            throw new Exception("DATABASE NOT SELECTED");
        }

        $sql = "SELECT DISTINCT raints.constraint_name AS fk, 
        ref.table_name,
        ref.delete_rule, ref.update_rule, cols.for_col_name as foreign_col,
        cols.ref_col_name as ref_col,
        ref.REFERENCED_TABLE_NAME as ref_table
        FROM
        INFORMATION_SCHEMA.table_constraints raints
            INNER JOIN
        INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS ref ON ref.constraint_name = raints.constraint_name
            INNER JOIN INFORMATION_SCHEMA.INNODB_SYS_FOREIGN_COLS cols on cols.ID = CONCAT( '{$dbname}/' , raints.constraint_name ) 
        WHERE
        table_schema = '{$dbname}' and ref.table_name = '{$table}'
        ORDER BY table_name";

        return DataBase::execute([$sql])->fetchAll();
    }

    public static function getColumnIndexName(string $table, string $column)
    {
        $dbname = Env::get('DB_NAME');

        if (is_null($dbname)) {
            throw new Exception("DATABASE NOT SELECTED");
        }

        $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE table_schema = '{$dbname}' AND table_name = '{$table}'  AND column_name = '{$column}';";

        return DataBase::execute([$sql, []])->fetch()['CONSTRAINT_NAME'] ?? '';
    }
}
