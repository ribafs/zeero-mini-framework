<?php

namespace Zeero\DataBase\QueryBuilder\traits;


/**
 * Condiction Trait
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
trait CondictionTrait
{
    private $map;
    private $case;

    public function where(string $str, array $params = [])
    {
        $this->map['WHERE'] = $str;
        $this->map['PARAMS'] = array_merge($this->map['PARAMS'] ?? [],  $params);
        return $this;
    }

    public function and(string $str, array $params = [])
    {
        $this->map['LOGIC'][] = ['AND', $str];
        $this->map['PARAMS'] = array_merge($this->map['PARAMS'] ?? [],  $params);
        return $this;
    }

    public function or(string $str, array $params = [])
    {
        $this->map['LOGIC'][] = ['OR', $str];
        $this->map['PARAMS'] = array_merge($this->map['PARAMS'] ?? [],  $params);
        return $this;
    }




    /**
     * get all builder params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->map['PARAMS'] ?? [];
    }


    /**
     * get the condictions 
     *
     * @return string
     */
    protected function ProcessCond()
    {
        $sql = '';
        $matrix = $this->map;

        // WHERE
        if (isset($matrix['WHERE'])) {
            $sql .= " WHERE ( " . $matrix['WHERE'] . " )";
        }

        // LOGIC
        if (isset($matrix['LOGIC'])) {
            foreach ($matrix['LOGIC'] as $value) {
                $type = $value[0];
                $string = $value[1];
                $sql .= " {$type} {$string} ";
            }
        }

        return $sql;
    }
}
