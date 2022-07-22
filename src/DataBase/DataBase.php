<?php

namespace Zeero\Database;

use Exception;
use PDO;
use PDOStatement;
use Zeero\Core\Env;


/**
 * DataBase Abstract Class
 * 
 * this class is used in the entire framework
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class DataBase
{
    private static PDO $currentConnection;

    /**
     * get the current Connection
     *
     * @throws Exception if the connection not exists
     * @return PDO
     */
    public static function getCurrentConnection(): PDO
    {
        if (!isset(self::$currentConnection)) {
            throw new Exception("DataBase Connection Not Found");
        }

        return self::$currentConnection;
    }

    /**
     * create the database connection
     *
     * @return void
     */
    public static function createConnection(): void
    {
        // get information from the .env file
        $db_connection = Env::get('DB_CONNECTION') ?? 'mysql';
        $db_name = Env::get('DB_NAME');
        $host = Env::get('DB_HOST');
        $charset = Env::get('DB_CHARSET');
        $port = Env::get('DB_PORT');
        $user = Env::get('DB_USER');
        $password = Env::get('DB_PASSWORD');

        if ($db_name and $host and $user) {
            // connection with MySQL
            if ($db_connection == 'mysql') {

                $dsn =  $db_connection . ":dbname={$db_name};host={$host};";

                if ($port) {
                    $dsn .= "port=" . $port . ';';
                }

                if ($charset) {
                    $charset = "charset=" . $charset;
                } else {
                    $charset = "charset=utf8";
                }

                $dsn .= $charset;

                $connection =  new PDO($dsn, $user, $password);
                $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                self::$currentConnection = $connection;
            }
        }
    }

    /**
     * make a query
     *
     * @param string $sql
     * @param string|null $fetch_class
     * @return PDOStatement
     */
    public static function query(string $sql, string $fetch_class = null): PDOStatement
    {
        $db = self::getCurrentConnection();
        $smt = $db->query($sql);

        if (isset($fetch_class)) {
            $smt->setFetchMode(PDO::FETCH_CLASS, $fetch_class);
        }

        return $smt;
    }

    /**
     * execute a prepared statement
     *
     * @param string $sql
     * @param array|null $params
     * @param string $fetch_class
     * @return PDOStatement|null
     */
    public static function PreparedStatment(string $sql, array $params, string $fetch_class = null): PDOStatement|null
    {
        $db = self::getCurrentConnection();
        $smt = $db->prepare($sql);

        if ($smt === false) {
            if (DEV)
                exit($db->errorInfo());
            else
                return null;
        }

        if ($smt->execute($params)) {
            if (isset($fetch_class)) {
                $smt->setFetchMode(PDO::FETCH_CLASS, $fetch_class);
            }
            return $smt;
        }
    }



    /**
     * execute a prepared statement and return the boolean of result
     *
     * @param string $sql
     * @param array|null $params
     * @return bool
     */
    public static function executeQuery(string $sql, array $params): bool
    {
        $db = self::getCurrentConnection();
        $smt = $db->prepare($sql);

        if ($smt === false) {
            if (DEV)
                exit($db->errorInfo());
            else
                return null;
        }

        return $smt->execute($params);
    }

    /**
     * execute a sql statement
     *
     * @param string $sql
     * @return integer|false
     */
    public static function executeSQL(string $sql): int|false
    {
        return self::getCurrentConnection()->exec($sql);
    }

    public static function execute(array $data , $fetch_class = null)
    {
        $sql = $data[0];
        $params = $data[1] ?? [];
        return self::PreparedStatment($sql, $params , $fetch_class);
    }

    
    public static function getLastId()
    {
        return self::getCurrentConnection()->lastInsertId();
    }


    public static function setVar(string $name, $value)
    {
        self::executeSQL("SET {$name} = {$value} ;");
    }

    public static function BeginWork()
    {
        self::executeSQL('BEGIN WORK ;');
    }

    public static function RollBack()
    {
        self::executeSQL('ROLLBACK ;');
    }

    public static function AutoCommit(bool $state = false)
    {
        self::setVar("AUTOCOMMIT", $state ? 1 : 0);
    }

    public static function Commit()
    {
        self::executeSQL('COMMIT ;');
    }
}
