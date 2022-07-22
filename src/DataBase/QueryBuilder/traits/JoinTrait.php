<?php

namespace Zeero\DataBase\QueryBuilder\traits;

use Zeero\DataBase\QueryBuilder\Join;
use Zeero\DataBase\QueryBuilder\JoinSentence;
use Zeero\DataBase\QueryBuilder\TypedJoinSentence;

/**
 * JOIN trait
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
trait JoinTrait
{
    private array $joinSentences = [];
    private string $Jfields = '';


    /**
     * set the fields
     *
     * @param array $fields
     * @return array
     */
    public function fields(array $list)
    {
        $table = $this->table;

        if (str_word_count($this->table) > 1) {
            $parts = explode(' ', $this->table);
            $table = end($parts);
        }

        $list = array_map(function ($i) use ($table) {
            if (strpos($i, '(') === false)
                return $table . '.' . $i;
            else
                return $i;
        }, $list);

        $this->fields = implode(" , ", $list);
    }



    /**
     * Construct a new TYPED JOIN sentence
     *
     * @param string $type
     * @param string $table
     * @return TypedJoinSentence
     */
    public function &addJoin(string $table, string $type)
    {
        $sentence = new TypedJoinSentence($type, $table);
        $reference = &$sentence;
        $this->joinSentences[] = $reference;
        return $reference;
    }



    /**
     * Construct a new JOIN sentence
     *
     * @param string $type
     * @param string $table
     * @return Join
     */

    public function &joinWith(string $table)
    {
        
        if (str_word_count($table) > 1) {
            $parts = explode(' ', $table);
            $this->fields .= end($parts) . ', ';
        }

        $sentence = new Join($table);
        $reference = &$sentence;
        $this->joinSentences[] = $reference;
        return $reference;
    }


    public function getJoin()
    {
        if (count($this->joinSentences)) {
            $sql = '';
            foreach ($this->joinSentences as $object) {
                if ($f = $object->getFields()) $this->fields .= ', ' . $f;
                $sql .= $object;
            }

            return $sql;
        }
    }


    protected function getJoinFields()
    {
        return $this->Jfields;
    }
}
