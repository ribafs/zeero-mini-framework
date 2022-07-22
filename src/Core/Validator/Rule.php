<?php

namespace Zeero\Core\Validator;

/**
 * Rule
 * 
 * @author Carlos Bumba git:@CarlosNio
 */
class Rule
{
    private $name;
    private $action;

    private function __construct()
    {
        
    }

    /**
     * Build a new rule object
     * 
     * @return self
     */
    public static function Builder(string $name, callable $action)
    {
        $o = new self;
        $o->name = $name;
        $o->action = $action;
        return $o;
    }


    /**
     * Return a array with the rule information
     *
     * @param string $rule
     * @return array
     */
    public static function info(string $rule)
    {
        if ($pos = strpos($rule, ":")) {
            $value = substr($rule, $pos + 1);
            $rulename = substr($rule, 0, $pos);

            $regex_for_array = "/\[(.*)\]/";

            if (preg_match($regex_for_array, $value, $matches)) {
                $list = explode(",", $matches[1]);
                $info = ["name" => $rulename, "type" => "array", "data" => $list];
            } else {
                if (strpos($value, ","))
                    $value = explode(",", $value);

                $info = ["name" => $rulename, "type" => "single", "data" => $value];
            }
        } else {
            $info = ['name' => $rule];
        }

        return $info;
    }    
    

    // magic method
    public function __get($name)
    {
        if (isset($this->$name))
            return $this->$name;
    }

}