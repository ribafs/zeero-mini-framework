<?php

namespace Zeero\Zcli;



/**
 * Base Class for all comands
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class Command
{
    protected static $_arguments;
    // zcli informations
    protected $input_argument;
    protected $input_value;
    protected $input_options;

    /**
     * when a object is created , this can access the zcli input
     * 
     */
    public function __construct()
    {
        $input = Zcli::getInput();

        // set the properties
        $this->input_argument = trim($input[1]);
        $this->input_value = trim($input[2]);
        $this->input_options = array_map('trim', $input[3]);
    }

    /**
     * this method is executed when the command is called without arguments
     */
    abstract public function _initialize();
}
