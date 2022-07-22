<?php

namespace Zeero\DataBase;

use Exception;
use InvalidArgumentException;
use Zeero\Kernel;

/**
 * Seed Interface
 * 
 * 
 */
abstract class Seed
{

    /**
     * Run a seed classs
     *
     * @return int the number of records inserted
     */
    final public function run()
    {
        Kernel::DataBaseBoot();
        $fields = $this->fields();
        $values = $this->data();

        if (empty($this->model) or !isset($this->model)) {
            throw new InvalidArgumentException("Model Not Defined");
        }

        if (count($values) == 0) {
            throw new Exception("No Data To Insert");
        }

        $model = "App\Models\\{$this->model}";

        foreach ($values as $record) {
            $model::create(array_combine($fields, $record));
        }

        return count($values);
    }


    /**
     * get the table fields
     *
     * @return array
     */
    abstract protected function fields();


    /**
     * get the table records
     *
     * @return array
     */
    abstract protected function data();
}
