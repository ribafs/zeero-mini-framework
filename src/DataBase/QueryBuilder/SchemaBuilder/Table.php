<?php

namespace Zeero\Database\QueryBuilder\SchemaBuilder;

use Zeero\DataBase\QueryBuilder\SchemaBuilder\traits\floatType;
use Zeero\DataBase\QueryBuilder\SchemaBuilder\traits\modifier;
use Zeero\DataBase\QueryBuilder\SchemaBuilder\traits\setType;
use Zeero\DataBase\QueryBuilder\SchemaBuilder\traits\util;
use Exception;


/**
 * Table
 * 
 * this class represent the table structure
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class Table
{
    private $dataTypes;

    // datatypes traits
    use floatType, setType;
    // modifier trait
    use modifier;
    // utility trait
    use util;


    /**
     * Register a datatype item
     *
     * @param string $key
     * @param string $name
     * @param integer|null $size
     * 
     * @return this
     */
    private function register(string $key, string $name, int $size = null)
    {
        $this->dataTypes[][$key] = [$name, $size];
        return $this;
    }



    /**
     * Create the column data type string
     *
     * @param array $info
     * @return string
     */
    private function create_str(array $info): string
    {
        $s = '';
        $type = $info['type'];
        $name = $info['name'];
        $size = $info['size'];

        if (isset($info['mod']['size'])) {
            $size = $info['mod']['size'];
        }

        if ($size)
            $s .= " `{$name}` {$type}({$size}) ";
        else
            $s .= " `{$name}` {$type} ";


        if ($info['class'] == 'integer') {
            if (isset($info['mod']['unsigned'])) {
                $s .= "unsigned ";
            }
        }

        // make sure that all fields will be not null
        $info['mod']['not null'] = '';

        if (isset($info['mod'])) {
            $this->modifiers($s, $info['mod']);
        }

        return $s;
    }




    /**
     * set the primary key
     *
     * @throws Exception if not column is selected
     * @throws Exception if already exists a primary key
     * @return this
     */
    public function primaryKey()
    {
        $current = $this->currentField();

        if (is_null($current)) {
            throw new Exception('No Collumn selected for Primary Key');
        }

        if (isset($this->pk)) {
            throw new Exception("Duplicated Primary Key");
        }

        $this->pk = $current[0][0];
        return $this;
    }


    /**
     * set a AUTO INCREMENT column
     *
     * @param string $name
     * @return this
     */
    public function autoIncrement(string $name = 'ID')
    {
        $this->autoIncrement = $name;
        $this->pk = $name;
        return $this;
    }


    /**
     * Set a Foreign Key
     *
     * @param string $name the foreign key name
     * @param string $field the local column
     * @param string $reference the target table and column separated with a dot
     * @param string $onDelete the action on DELETE
     * @param string $onUpdate the action on UPDATE
     * 
     * @throws Exception if the referenced column is not separated by a dot
     * @return this
     */
    public function foreign_key(string $name, string $field, string $reference, string $onDelete = 'NO ACTION', string $onUpdate = 'NO ACTION')
    {
        if (!strpos($reference, '.')) {
            throw new Exception("Referenced Collumn must be divided by a dot (ex: Table.Collumn )");
        }

        if (!in_array(strtolower($onDelete), ['cascade', 'no action'])) {
            throw new Exception("Invalid value for ON DELETE on Constraint '{$name}'");
        }

        if (!in_array(strtolower($onUpdate), ['cascade', 'no action'])) {
            throw new Exception("Invalid value for ON UPDATE on Constraint '{$name}'");
        }

        $this->fk[] = [$field, $reference, $name, $onUpdate, $onDelete];
        return $this;
    }


    /**
     * Set the current column as unique
     *
     * @param string|null $name
     * @throws Exception if not column is selected
     * @return this
     */
    public function unique(string $name = null)
    {
        $current = $this->currentField();

        if (is_null($current)) {
            throw new Exception('No Collumn selected for Unique Key');
        }

        $name ??= $current[0][0] . '_UNIQUE';
        $this->uniques[] = [$name, $current[0][0]];
        return $this;
    }


    /**
     * set the table engine
     *
     * @param string $engine
     * @return this
     */
    public function engine(string $engine)
    {
        $this->engine = $engine;
        return $this;
    }


    /**
     * set the table charset
     *
     * @param string $charset
     * @return this
     */
    public function charset(string $charset)
    {
        $this->charset = $charset;
        return $this;
    }


    /**
     * Build the Table definition string
     *
     * @param boolean $onlyFields
     * @return string
     */
    public function results(bool $onlyFields = false): string
    {
        $matrix = $this->dataTypes;
        $str = '';

        if ($matrix) {
            for ($i = 0; $i < count($matrix); $i++) {
                // get a array with information about this column type
                $info = $this->getInfo($matrix[$i]);
                // create strings
                if ($info['class'] == 'float')
                    $str .= $this->floatType($info);
                elseif ($info['class'] == 'set')
                    $str .= $this->setsTypes($info);
                else
                    $str .= $this->create_str($info);
                // separate with plus signal
                $str .= " +";
            }
        }

        if (isset($this->autoIncrement)) {
            $s = "`{$this->autoIncrement}` int not null auto_increment +";
            $str = $s . $str;
        }

        // eliminate the last plus signal
        $str = substr($str, 0, strlen($str) - 1);

        if (isset($this->pk)) {
            $str .= "+ PRIMARY KEY (`{$this->pk}`) ";
        }

        if (isset($this->fk)  and !$onlyFields) {
            foreach ($this->fk as $info) {

                $_info = explode(".", $info[1]);

                if (count($_info) == 3) {
                    $t = $_info[1];
                    $f = $_info[2];
                } else {
                    $t = $_info[0];
                    $f = $_info[1];
                }

                $s = "+ CONSTRAINT `{$info[2]}` FOREIGN KEY (`{$info[0]}`) REFERENCES {$t} (`{$f}`) ON DELETE {$info[4]} ON UPDATE {$info[3]}";
                $str .= $s;
            }
        }

        if (isset($this->uniques) and !$onlyFields) {
            $s = '';
            foreach ($this->uniques as $info) {
                list($name, $field) = $info;
                $s .= "UNIQUE `{$name}` (`{$field}`) + ";
            }

            $s = substr($s, 0, strlen($s) - 2);
            $str .= " + {$s}";
        }

        $str = "( {$str})";

        if ($onlyFields) return $str;

        if (isset($this->engine)) {
            $str .= " ENGINE = {$this->engine}";
        } else
            $str .= " ENGINE = InnoDB";

        if (isset($this->charset)) {
            $str .= "  default charset={$this->charset}";
        } else
            $str .= "  default charset=UTF8";

        return $str . ' ;';
    }


    public function string(string $name)
    {
        $size = 255;
        return $this->register("varchar", $name, $size);
    }

    public function char(string $name)
    {
        $size = 1;
        return $this->register("char", $name, $size);
    }

    public function text(string $name)
    {
        $size = 500;
        return $this->register("text", $name, $size);
    }

    public function tinyText(string $name)
    {
        return $this->register("tinytext", $name);
    }

    public function mediumText(string $name)
    {
        return $this->register("mediumtext", $name);
    }

    public function longText(string $name)
    {
        return $this->register("longtext", $name);
    }

    public function blob(string $name, int $size = 50)
    {
        $size = 50;
        return $this->register("blob", $name, $size);
    }

    // INTEGERS
    public function tinyInt(string $name)
    {
        $size = 2;
        return $this->register('tinyint', $name, $size);
    }

    public function smallInt(string $name)
    {
        $size = 4;
        return $this->register('smallint', $name, $size);
    }

    public function mediumInt(string $name)
    {
        $size = 8;
        return $this->register('mediumint', $name, $size);
    }

    public function int(string $name)
    {
        $size = 11;
        return $this->register('int', $name, $size);
    }

    public function bigInt(string $name)
    {
        $size = 25;
        return $this->register('bigint', $name, $size);
    }



    // DATE
    public function datetime(string $name)
    {
        return $this->register("datetime", $name);
    }

    public function date(string $name)
    {
        return $this->register("date", $name);
    }

    public function timestamp(string $name)
    {
        $size = 6;
        return $this->register("timestamp", $name, $size);
    }

    public function time(string $name)
    {
        return $this->register("time", $name);
    }

    public function year(string $name)
    {
        $size = 4;
        return $this->register("year", $name, $size);
    }


    /**
     * add two datetime columns
     *
     * created_at
     * 
     * updated_at
     * @return void
     */
    public function timesTamps()
    {
        $this->datetime('created_at');
        $this->datetime('updated_at')->nullable();
    }

    /**
     * define a varchar(36) column of UUID
     *
     * @param string $name
     * @return void
     */
    public function uuid(string $name = 'uuid')
    {
        $this->string($name)->size(36)->primaryKey();
    }
}
