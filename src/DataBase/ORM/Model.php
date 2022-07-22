<?php

namespace Zeero\Database\ORM;

use Exception;
use ReflectionClass;
use Zeero\Database\DataBase;
use Zeero\DataBase\ORM\traits\OperationTrait;
use Zeero\DataBase\QueryBuilder\Select;
use Zeero\DataBase\QueryBuilder\Insert;
use Zeero\DataBase\QueryBuilder\Delete;
use Zeero\DataBase\QueryBuilder\Update;



/**
 * 
 * Model
 * 
 * the base of Models in ORM
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class Model
{
    use OperationTrait;

    private static $models_attributes = [];
    private static $models_tables = [];
    private static $models_foreign_keys = [];
    private static $models_primarys = [];

    // helpers
    private static $pk_values = [];
    private static $tbl_values = [];

    public function __construct()
    {
        $classname = self::getAbsoluteClass();

        /** register the model *attributes property */
        if (!isset(self::$models_attributes[$classname]) && isset($this->attributes))
            self::$models_attributes[$classname] = $this->attributes;


        /** register the model *foreign_keys property */
        if (!isset(self::$models_foreign_keys[$classname]) && isset($this->foreign_keys))
            self::$models_foreign_keys[$classname] = $this->foreign_keys;


        /** delete the *foreign_keys property for this instance */
        if (isset($this->foreign_keys))
            unset($this->foreign_keys);

        /** register the model *primary_key property */
        if (!isset(self::$models_primarys[$classname]))
            self::$models_primarys[$classname] = $this->primary_key;

        /** delete the *primary_key property for this instance */
        if (isset($this->primary_key))
            unset($this->primary_key);

        /** register the model *table property */
        if (!isset(self::$models_tables[$classname])) {
            if (isset($this->table)) {
                self::$models_tables[$classname] = $this->table;
            }
        }

        if (isset($this->table))
            unset($this->table);
    }


    /**
     * 
     * A helper method to execute SQL FOREIGN_KEY_CHECK
     *
     * @param integer $pos
     * @return void
     */
    public static function ignore_keys_helper(int $pos)
    {
        $cmd = "";
        $cmd2 = "";
        $str = " SET foreign_key_checks = ";

        $cmd = $str . 0 . ";";
        $cmd2 = $str . 1 . ";";

        $cmds =  [$cmd, $cmd2];
        self::ExecuteQuery($cmds[$pos]);
    }



    /**
     * 
     * Get the object current properties
     *
     * @return array
     */
    private function getCurrentProperties()
    {
        $params = [];
        $object = (array) $this;
        $properties = self::$models_attributes[self::getAbsoluteClass()];

        for ($i = 0; $i < count($properties); $i++) {
            $key = $properties[$i];
            $v = $object[$key] ?? null;

            if (!is_null($v))
                $params[$key] = $v;
        }


        return $params;
    }



    /**
     * Bring all foreign objects
     *
     * @param object|object[] $object
     * @return void
     */
    private static function bringsAllForeignObjects($object)
    {
        if (!is_array($object)) $object = [$object];

        $abs_name = self::getAbsoluteClass($object[0]::class);
        $foreign_keys = self::$models_foreign_keys[$abs_name] ?? [];

        foreach ($object as $obj) {

            foreach ($foreign_keys as $pk => $info) {
                $model = "App\Models\\" . ucfirst($info['table']);

                if (!class_exists($model) or empty($obj->$pk ?? '')) return $object;

                $sql = "SELECT * FROM {$info['table']} WHERE {$info['column']} = ?";
                $sub = DataBase::execute([$sql, [$obj->$pk]], $model)->fetch();

                if (is_object($sub)) {
                    $table = $info['table'];
                    $obj->$table = $sub;
                }
            }
        }

        if (count($object) == 1) {
            return $object[0];
        }

        return $object;
    }




    /**
     * 
     * Find a Record By a Where Condiction
     *
     * @param string $where
     * @param mixed $params
     * @param integer $normal
     * @param string|null $orderBy
     * 
     * @return array|int
     */
    private static function find_where(string $where, mixed $params = [], int $normal = 1, string $orderBy = null)
    {
        $select = self::BuilderFactory('select');
        if (!is_array($params)) $params = array($params);

        $regexp = "@\s?([a-z0-9_]+)\s+?=\s+?@i";
        preg_match_all($regexp, $where, $matches);

        $select->where($where, $params);

        if ($orderBy) $select->order($orderBy);

        if (self::$only) {
            $select->fields(self::$only);
            self::$only = null;
        }

        $smt = self::ExecuteQuery($select->results(), $normal);
        return self::bringsAllForeignObjects($smt->fetchAll(), $matches[1]);
    }


    /**
     * Receive a list of data and filter only the allowed 
     *
     * this method is used in instancies
     * @param array|null $list
     * @return array
     */
    private function onlyFillableArray($list)
    {
        if ($list == null) return null;
        $attrs =  self::$models_attributes[self::getAbsoluteClass()];
        return self::FillableArrayHelper($attrs, $list);
    }

    /**
     * Test if the current class has a property
     *
     * @param string $name the property name
     * @return boolean
     */
    private static function hasProperty(string $name)
    {
        $c = new ReflectionClass(get_called_class());
        return $c->hasProperty($name);
    }

    /**
     * Get a property in the current class
     *
     * @param string $name the property name
     * @return mixed
     */
    private static function getProperty(string $name)
    {
        if (self::hasProperty($name)) {
            $c = new ReflectionClass(get_called_class());
            $p = $c->getProperty($name);
            $p->setAccessible(true);
            return $p->getValue(new (get_called_class()));
        }
    }




    /**
     * Receive a list of data and filter only the allowed 
     *
     * this method is used in static calls
     * @param array|null $list
     * @return array
     */
    private static function onlyFillableArrayStatic(array $params)
    {
        $attrs = self::getProperty('attributes');

        if (is_array($attrs))
            return self::FillableArrayHelper($attrs, $params);
    }


    /**
     * A Helper Method for *onlyFillable functionality
     *
     * @param array $attributes
     * @param array $params
     * @return array
     */
    private static function FillableArrayHelper(array $attributes, array $params)
    {
        $clean = [];

        if (count($attributes) >= 1) {
            foreach ($attributes as $key) {
                if (isset($params[$key])) {
                    $clean[$key] = $params[$key];
                }
            }
        }

        return $clean;
    }



    /**
     * Get the *primary_key attribute
     *
     * @return string|null
     */
    private static function getPrimaryKey()
    {
        return self::$models_primarys[self::getAbsoluteClass()] ?? null;
    }


    /**
     * Get the Model class withou the Namespace
     *
     * @param string $classname
     * @return string
     */
    private static function getAbsoluteClass(string $classname = null)
    {
        $fullname = str_replace("\\", "/", $classname ?? get_called_class());
        $regexp = "/.+\/(\w+)/";
        preg_match($regexp, $fullname, $m);
        return $m[1] ?? $fullname;
    }


    private static function getTable()
    {
        new (get_called_class());
        return self::$models_tables[self::getAbsoluteClass()] ??  self::getAbsoluteClass();
    }

    /**
     * Factory Method for QueryBuilder
     *
     * @param string $type the queryBuilder name
     * @throws Exception if the querybuilder name is invalid
     * @return Select|Insert|Update|Delete
     */
    public static function BuilderFactory(string $type): Select | Insert | Update | Delete
    {
        $table = self::getTable();
        $builder = null;

        switch ($type) {
            case 'select':
                $builder = new Select($table);
                break;

            case 'insert':
                $builder = new Insert($table);
                break;

            case 'update':
                $builder = new Update($table);
                break;

            case 'delete':
                $builder = new Delete($table);
                break;

            default:
                throw new Exception("Undefined QueryBuilder Type '{$type}' ");
                break;
        }

        return $builder;
    }
}
