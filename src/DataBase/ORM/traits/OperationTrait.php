<?php

namespace Zeero\DataBase\ORM\traits;

use PDOStatement;
use Zeero\Core\Exceptions\DbNotFoundException;
use Zeero\Database\DataBase;
use Zeero\DataBase\QueryBuilder\Select;

/**
 * Trait
 * 
 * contains all CRUD operation in ORM
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
trait OperationTrait
{
    private static $only = null;
    private static $limit = null;
    private static $skip = null;
    private static $db;


    /**
     * Execute a Query
     *
     * @param string|array $data
     * @param array $params
     * @param integer $normalFetch
     * @return PDOStatement
     */
    private static function executeQuery($data, int $normalFetch = 1): PDOStatement
    {
        $fetch_class = null;
        $sql = '';
        $params = [];

        if ($normalFetch) $fetch_class = get_called_class();

        // check if is a querybuilder result
        if (is_array($data)) {
            $sql = $data[0];
            $params = $data[1] ?? [];
        } elseif (is_string($data)) {
            $sql = $data;
        }

        return DataBase::PreparedStatment($sql, $params, $fetch_class);
    }


    private static function execute($data)
    {
        $sql = '';
        $params = [];

        // check if is a querybuilder result
        if (is_array($data)) {
            $sql = $data[0];
            $params = $data[1] ?? [];
        } elseif (is_string($data)) {
            $sql = $data;
        }

        return DataBase::executeQuery($sql, $params);
    }


    /**
     * set the result field
     *
     * @param array $items
     * @return self
     */
    public static function only(array $items)
    {
        self::$only = $items;
        $class = get_called_class();
        return new $class;
    }


    /**
     * set the records limit
     *
     * @param int|string $n
     * @return $this
     */
    public static function limit($n)
    {
        self::$limit = $n;
        $class = get_called_class();
        return new $class;
    }


    /**
     * filter result fields
     *
     * @param array|string $items
     * @return self
     */
    public static function skip($items)
    {
        if (!is_array($items))
            $items = [$items];

        self::$skip = $items;
        $class = get_called_class();
        return (new $class);
    }



    /**
     * return a uuid value
     * 
     * @return string
     */

    public static function getUuid()
    {
        $sql = 'Select uuid() as uuid';
        $result = self::executeQuery($sql)->fetch();
        return $result->uuid;
    }


    /**
     * return all data
     * 
     * @return array
     */
    public static function all(string $orderBy = null)
    {
        $builder = self::BuilderFactory('select');

        if (self::$only) {
            $builder->fields(self::$only);
            self::$only = null;
        }

        if (self::$skip) {
            $attrs = self::getProperty('attributes');

            foreach (self::$skip as $key => $value) {
                if (in_array($value, $attrs)) {
                    unset($attrs[array_search($value, $attrs)]);
                }
            }

            $builder->fields($attrs);
            self::$skip = null;
        }

        if ($orderBy) $builder->order($orderBy);

        if (self::$limit) {
            $builder->limit(self::$limit);
            self::$limit = null;
        }

        $smt = self::executeQuery($builder->results());

        return self::bringsAllForeignObjects($smt->fetchAll());
    }


    /**
     * 
     * Paginate Select QueryBuilder Results
     *
     * @param integer $limit the records per page
     * @param integer $page the page number
     * @param Select $builder the select instance
     * @param array|null $orderBy the result order
     * @return array
     */
    public static function paginateResult(
        int $limit,
        int $page,
        Select $builder,
        array $orderBy = null
    ) {

        // avoid invalid page number
        if ($page <= 0) $page  = 1;

        $pages = ceil(self::count() / $limit);
        $start = $limit * ($page - 1) . "," . $limit;

        if ($orderBy and count($orderBy) == 2) {
            $builder->order($orderBy[0] . ' ' . strtoupper($orderBy[1]));
        }

        $builder->limit($limit);

        if ($page > 1) $builder->limit($start);

        $result = self::executeQuery($builder->results(), 1)->fetchAll();

        // pagination meta data
        $data = [];
        $data['pages'] = (int) $pages;
        $data['per_page'] = $limit;
        $data['current_page'] = $page;
        $data['next_page'] = $page < $pages ? $page + 1 : null;
        $data['prev_page'] = $page > 1 ? $page - 1 : null;
        $data['data'] = self::bringsAllForeignObjects($result);
        $data['total'] = count($data['data']);

        return $data;
    }


    /**
     * 
     * Paginate Results
     *
     * @param integer $limit the records per page
     * @param integer $page the page number
     * @param string|null $where the WHERE condiction
     * @param array $params the WHERE placeholder params
     * @param array|null $orderBy the result order
     * @param integer $asObject
     * @return array
     */
    public static function paginate(
        int $limit,
        int $page = 1,
        string $where = null,
        array $params = [],
        array $orderBy = null,
        int $asObject = 1
    ) {
        $builder = self::BuilderFactory('select');

        // avoid invalid page number
        if ($page <= 0) $page  = 1;

        $pages = ceil(self::count() / $limit);
        $start = $limit * ($page - 1) . "," . $limit;

        if (self::$only) {
            $builder->fields(self::$only);
            self::$only = null;
        }

        if ($where) {

            $builder->where($where, $params ?? null);

            $pages = ceil(self::countBy($where, $params ?? null) / $limit);

            if ($orderBy and count($orderBy) == 2) {
                $builder->order($orderBy[0] . ' ' . strtoupper($orderBy[1]));
            }
        }

        $builder->limit($limit);

        if ($page > 1) $builder->limit($start);

        $result = self::executeQuery($builder->results(), $asObject)->fetchAll();

        // pagination meta data
        $data = [];
        $data['pages'] = (int) $pages;
        $data['per_page'] = $limit;
        $data['current_page'] = $page;
        $data['next_page'] = $page < $pages ? $page + 1 : null;
        $data['prev_page'] = $page > 1 ? $page - 1 : null;
        $data['data'] = self::bringsAllForeignObjects($result);
        $data['total'] = count($data['data']);

        return $data;
    }


    /**
     * Get the Records Number
     *
     * @return int
     */
    public static function count()
    {
        $builder = self::BuilderFactory('select');
        $builder->fields(["count(*)"]);
        $result = self::executeQuery($builder->results(), 0)->fetch();
        return $result["count(*)"];
    }


    /**
     * Get the Records Number with a WHERE condiction
     *
     * @param string $where
     * @param array $params
     * @return int
     */
    public static function countBy(string $where, array $params = [])
    {
        $builder = self::BuilderFactory('select');
        $builder->fields(["count(*)"]);
        $builder->where($where, $params);
        $result = self::executeQuery($builder->results(), 0)->fetch();
        return (int) $result["count(*)"];
    }


    /**
     * Return a boolean if a record exists
     *
     * @param string|null $where
     * @return boolean
     */
    public static function has(string $where = null, array $params = [])
    {
        $count = self::countBy($where, $params);
        return $count >= 1;
    }


    /**
     * Get the first record
     *
     * @param string|null $where
     * @param array $params
     * @param string|null $orderBy
     * @return object|int
     */
    public static function first(string $where = null, $params = [], string $orderBy = null)
    {
        $builder = self::BuilderFactory('select');
        $builder->limit(1);

        if ($orderBy) $builder->order($orderBy);

        if ($where) {
            if (!is_array($params)) $params = array($params);
            $builder->where($where, $params);
        }

        $result = self::executeQuery($builder->results())->fetchAll();

        if (is_bool($result)) return 0;

        $result = self::bringsAllForeignObjects(end($result));

        return $result;
    }


    /**
     * Get the last record
     *
     * @param string|null $where
     * @param array $params
     * @param string|null $orderBy
     * @return self|int
     */
    public static function last(string $where = null, array $params = [], string $orderBy = null)
    {
        $builder = self::BuilderFactory('select');

        if ($orderBy) $builder->order($orderBy);

        if ($where) $builder->where($where, $params);

        $result = self::executeQuery($builder->results())->fetchAll();

        if (is_bool($result)) return 0;

        return self::bringsAllForeignObjects(end($result));
    }


    /**
     * Get a Record by her position
     *
     * @param integer $index
     * @param string|null $where
     * @param array $params
     * @return self|int
     */
    public static function atPosition(int $index, string $where = null, ...$params)
    {
        $builder = self::BuilderFactory('select');

        if ($where) $builder->where($where, $params);

        $builder->limit($index - 1 . ',1');

        $result = self::executeQuery($builder->results())->fetch();

        if (is_bool($result)) return 0;

        return self::bringsAllForeignObjects($result);
    }



    /**
     * Find the first record if not found create a new
     *
     * @param string $where
     * @param array $params
     * @param array $data
     * 
     * @return object|int
     */
    public static function firstOrCreate(string $where, array $params = [], array $data = [])
    {
        $record = self::find_where($where, $params);

        if (!$record) {
            self::create($data);
            return DataBase::getLastId();
        }

        return $record[0] ?? 0;
    }


    /**
     * Find Records
     *
     * @param string $where
     * @param mixed $params
     * @param string|null $orderBy
     * 
     * @return array|int
     */
    public static function find(string $where, mixed $params = [], string $orderBy = null)
    {
        if (empty($where)) return null;

        return self::find_where($where, $params, 1, $orderBy);
    }


    /**
     * Find The First Record
     *
     * @param string $where
     * @param mixed $params
     * @param string|null $orderBy
     * 
     * @return self|int
     */
    public static function findOne(string $where, $params = [], string $orderBy = null)
    {
        return self::first($where, $params, $orderBy);
    }


    /**
     * Find Or Fail
     *
     * @param string $where
     * @param array $params
     * @param string $orderBy
     * @throws DbNotFoundException if record not found
     * 
     * @return array|null
     */
    public static function findOrFail(string $where, array $params = [], string $orderBy = null)
    {
        $data = self::find($where, $params, $orderBy);

        if (!$data) {
            throw new DbNotFoundException('record not found');
            return;
        } else {
            return $data;
        }
    }



    /**
     * Create a New Record
     *
     * @param array $params
     * @return boolean|null
     */
    public static function create(array $params)
    {
        $params = self::onlyFillableArrayStatic($params);

        if (count($params) == 0) {
            return null;
        }

        $insert = self::BuilderFactory('insert');
        $insert->add($params);

        if (!self::executeQuery($insert->results())) {
            return null;
        }

        return true;
    }


    /**
     * create a new record with the current attributes or params
     * return the last inserted ID
     * 
     * @param array $data
     * @return int|boolean
     */
    public function save(array $data = [])
    {
        if (empty($data))
            $params = $this->getCurrentProperties();
        else
            $params = $this->onlyFillableArray($data);

        if (count($params) == 0) {
            return false;
        }

        $insert = $this->BuilderFactory('insert');
        $insert->add($params);

        if (!$this->executeQuery($insert->results())) {
            return false;
        }

        return DataBase::getLastId();
    }



    /**
     * Update Records
     *
     * @param array $set
     * @param string $where
     * @param array $params
     * @param integer $ignore_keys
     * @return boolean|null
     */
    public static function UpdateWhere(array $set, string $where, array $params = [], $ignore_keys = 1)
    {
        $data = self::onlyFillableArrayStatic($set);
        $update = self::BuilderFactory('update');

        if (empty($data)) return null;

        $update->set($data);

        if ($where)
            $update->where($where, $params);

        $result = $update->results();

        if ($ignore_keys)
            self::ignore_keys_helper(0);

        $r = self::execute($result);

        if ($ignore_keys)
            self::ignore_keys_helper(1);

        return $r;
    }



    /**
     * Update Data in current record
     *
     * @param array|null $data
     * @param integer $ignore_keys
     * 
     * @return boolean
     */
    public function update(array $data, $ignore_keys = 1)
    {
        $params = $this->onlyFillableArray($data);

        if (count($params) == 0) {
            return false;
        }

        $cond = $this->getUniqueID();

        if (is_null($cond)) return false;

        $update = $this->BuilderFactory('update');
        $update->set($params);
        $update->where("`{$cond[0]}` = '{$cond[1]}'");

        if (self::$limit) {
            $update->limit(self::$limit);
            self::$limit = null;
        } else {
            $update->limit(1);
        }

        if ($ignore_keys)
            self::ignore_keys_helper(0);

        $r = self::execute($update->results());

        if ($ignore_keys)
            self::ignore_keys_helper(1);

        return $r;
    }



    /**
     * Delete Records
     *
     * @param string $where
     * @param array $params
     * @param integer $limit
     * @param integer $ignore_keys
     * 
     * @return boolean
     */
    public static function DeleteWhere(string $where, array $params = [], int $limit = 0, $ignore_keys = 0)
    {
        $delete = self::builderFactory('delete');
        $delete->where($where, $params);

        if ($limit)
            $delete->limit($limit);

        $result = $delete->results();

        if ($ignore_keys)
            self::ignore_keys_helper(0);

        $r = self::execute($result);

        if ($ignore_keys)
            self::ignore_keys_helper(1);

        return $r;
    }


    /**
     * Get The Primary Key
     *
     * @return array|null
     */
    private function getUniqueID()
    {
        if ($pk = $this->getPrimaryKey()) {
            if (preg_match('@^\d+$@', $this->$pk)) $this->$pk = intval($this->$pk);
            return [$pk, $this->$pk];
        }
    }



    /**
     * Delete the current record
     *
     * @param integer $ignore_keys
     * @return boolean
     */
    public function delete($ignore_keys = 0)
    {
        $params = $this->getUniqueID();

        if (!$params) return null;

        $delete = $this->BuilderFactory('delete');
        $delete->where("`{$params[0]}` = ?", [$params[1]]);
        $result = $delete->results();

        if ($ignore_keys)
            self::ignore_keys_helper(0);

        $r = self::execute($result);

        if ($ignore_keys)
            self::ignore_keys_helper(1);

        return $r;
    }
}
