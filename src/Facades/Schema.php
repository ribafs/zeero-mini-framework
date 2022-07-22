<?php

namespace Zeero\facades;

use Closure;
use mysqli;
use Zeero\Core\Env;
use Zeero\Database\DataBase;
use Zeero\DataBase\QueryBuilder\SchemaBuilder\Schema as BuilderSchema;
use Zeero\Database\SchemaInfo;
use Zeero\Kernel;
use Zeero\Zcli\MigrationChangeChecker;
use Zeero\Zcli\SqlToArrayParser;

/**
 * Schema Facade
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class Schema
{
    private static BuilderSchema $schema;

    private static function init()
    {
        self::$schema = new BuilderSchema;
    }

    /**
     * execute a sql string
     *
     * @param string $sql
     * @return boolean|null
     */
    private static function execute(string $sql)
    {
        return DataBase::executeSQL($sql);
    }

    /**
     * Create a Schema if Not Exists
     *
     * @param string $name
     * @param string $charset
     * @return void
     */
    public static function CreateSchemaIfNotExists(string $name, string $charset = 'utf8')
    {
        self::init();
        $sql = self::$schema->createSchema($name, $charset);

        $mysqli = new mysqli(
            Env::get('DB_HOST'),
            Env::get('DB_USER'),
            Env::get('DB_PASSWORD')
        );

        // run the sql
        $mysqli->query($sql);
        // update the db name
        Env::replace("DB_NAME", $name);

        Kernel::DataBaseBoot();
    }


    /**
     * Create a Table
     *
     * @param string $name
     * @param Closure $actions
     * @param boolean $dropIfExists
     * @return boolean|null
     */
    public static function create(string $name, Closure $actions, bool $dropIfExists = false)
    {
        self::init();

        if (strpos($name, '.')) $name = explode('.', $name)[1];

        $sql = self::$schema->create($name, $actions);

        if (SchemaInfo::tableExists($name)) {
            list($columns_list, $fks, $columns, $uniques) = SqlToArrayParser::getParsedColumns($sql);

            // append current table name
            foreach ($fks as &$info) {
                $info['table_name'] = $name;
            }

            $migrationChecker = new MigrationChangeChecker(
                $name,
                $columns_list,
                $fks,
                $columns,
                $uniques
            );

            if ($migrationChecker->verify()) {
                list($c_a, $c_d, $c_m) = $migrationChecker->getColumnsStates();
                list($f_a, $f_d, $f_m) = $migrationChecker->getConstraintsStates();

                self::execute($migrationChecker->getAlterSQL());

                _dump($migrationChecker->getAlterSQL());
                $status = "--> [ columns: a => {$c_a} | m => {$c_m} | d => {$c_d} ]    [ constraints: a => {$f_a} | m => {$f_m} | d => {$f_d} ]\n";
                echo $status;
            } else {
                echo " --> already exists ( no changes detected )\n";
            }
        } else {
            echo "--> successfully\n";

            return self::execute($sql);
        }
    }


    /**
     * Alter a table structure
     *
     * @param string $name
     * @param Closure $actions
     * @return boolean|null
     */
    public static function alter(string $name, Closure $actions)
    {
        self::init();
        $sql = self::$schema->alter($name, $actions);
        return self::execute($sql);
    }


    /**
     * Drop a table
     *
     * @param string $table
     * @param boolean $ingore_keys
     * @return boolean
     */
    public static function drop(string $table, bool $ingore_keys = false)
    {
        self::init();
        $str = " SET foreign_key_checks = ";
        $sql = self::$schema->drop($table);

        if ($ingore_keys)
            self::execute($str . 0);

        self::execute($sql);

        if ($ingore_keys)
            self::execute($str . 1);

        return true;
    }


    /**
     * Drop a schema if exists
     *
     * @param string $name
     * @return boolean|null
     */
    public static function dropSchema(string $name)
    {
        self::execute('DROP SCHEMA IF EXISTS ' . $name);
    }

    /**
     * Drop a table if exists
     *
     * @param string $table
     * @param boolean $ingore_keys
     * @return boolean|null
     */
    public static function dropIfExists(string $table, bool $ingore_keys = false)
    {
        self::init();
        $str = " SET foreign_key_checks = ";
        $sql = self::$schema->dropIfExists($table);

        if ($ingore_keys)
            self::execute($str . 0);

        $result = self::execute($sql);

        if ($ingore_keys)
            self::execute($str . 1);

        return $result;
    }


    /**
     * Truncate a Table
     *
     * @param string $table
     * @param boolean $ingore_keys
     * @return boolean|null
     */
    public static function truncate(string $table, bool $ingore_keys = false)
    {
        self::init();
        $str = " SET foreign_key_checks = ";
        $sql = self::$schema->truncate($table);

        if ($ingore_keys)
            self::execute($str . 0);

        $result = self::execute($sql);

        if ($ingore_keys)
            self::execute($str . 1);

        return $result;
    }
}
