<?php
namespace Zeero\DataBase\QueryBuilder;


/**
 * Build INSERT Statments
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class Insert
{
    private $table;
    private $data;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function add(array $data)
    {
        $this->data = $data;
    }

    /**
     * get the INSERT string
     *
     * @return string|null
     */
    public function results()
    {
        if (!isset($this->data)) return null;

        $data = $this->data;
        // fields to insert
        $fields = implode(" , ", array_keys($data));
        //    placehlders for the parameters
        $placesholders = array_fill(0, count($data), "?");
        $placesholders = implode(" , ", $placesholders);
        //   values to insert
        $data = array_values($data);
        // insert statment
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placesholders}) ;";

        return [$sql, $data];
    }
}
