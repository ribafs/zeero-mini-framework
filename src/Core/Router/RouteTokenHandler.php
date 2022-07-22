<?php

namespace Zeero\Core\Router;

use Closure;


/**
 * Token Handler for Routes
 * 
 * this class help to validate route token
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class RouteTokenHandler
{
    /**
     * the validation action
     *
     * @var Closure
     */
    private $action;
    /**
     * the parameter name
     *
     * @var string
     */
    private $param;
    /**
     * the action after validation success
     *
     * @var Closure
     */
    private $afterAction;


    public function __construct(Closure $action, string $paramName)
    {
        $this->action = $action;
        $this->param = $paramName;
    }

    public function setAfterAction(Closure $action)
    {
        $this->afterAction = $action;
    }

    public function getAfterAction()
    {
        return $this->afterAction;
    }

    public function getParamName()
    {
        return $this->param;
    }

    public function getValidation(string $value = null)
    {
        $return = ($this->action)($value);
        return is_bool($return) and $return == true;
    }
}
