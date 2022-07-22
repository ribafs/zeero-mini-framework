<?php

namespace Zeero\DataBase\QueryBuilder;

use InvalidArgumentException;


class TypedJoinSentence extends Join
{
    // 
    private string $type;

    public function __construct(string $type, string $table)
    {
        $join_types = ['inner', 'left', 'right'];

        // invalid join type
        if (!in_array(strtolower($type), $join_types)) {
            $only = implode(" , ", $join_types);
            throw new InvalidArgumentException("Invalid Join Type '{$type}' (only: {$only})");
        }

        $type = strtoupper($type);
        $this->type = $type;
        parent::__construct($table);
    }

    public function __toString()
    {
        return $this->type . parent::__toString();
    }
}
