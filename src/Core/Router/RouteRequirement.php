<?php

namespace Zeero\Core\Router;

use Exception;
use Zeero\facades\Flash;
use Zeero\facades\FormRequest;
use Zeero\facades\Request;


/**
 * A class that tests all the routes requirements
 * 
 * @author carlosb bumba <carlosbumbanio@gmail.com>
 */
final class RouteRequirement
{
    private $action;

    public function __construct($action)
    {
        $this->action = $action;
    }


    /**
     * Reject a Request
     *
     * @param integer $error_id
     * @param string $message
     * @param mixed $extraInfo
     * @return void
     */
    private function reject(int $error_id, string $message, $extraInfo = null)
    {
        $action = $this->action;

        if (!$action) {
            if (Request::isAjax())
                return json_response(['message' => $message], 401);
            else
                return response($message, 401);
        }

        $action($error_id, $extraInfo);
        die;
    }



    /**
     * test if a required token is valid
     *
     * @param RouteTokenHandler $handler
     * @param array $params
     * @return void
     */
    public function requireValidToken(RouteTokenHandler $handler, array $params)
    {
        $param_key = $handler->getParamName();
        $param_value = $params[$param_key] ?? null;
        $return = $handler->getValidation($param_value) ?? false;
        // validate the closure return value or the param_value
        if ($return != true || $param_value == null) {
            $this->reject(1, 'Invalid Token: ' . $param_key, true);
        } else {
            // call the afterAction closure if is defined
            if ($afterAction = $handler->getAfterAction()) {
                $afterAction($param_value);
            }
        }
    }


    /**
     * 
     * Test if the CSRF Token is valid
     *
     * @return void
     */
    public function requireCSRFToken()
    {
        $params = array_merge(FormRequest::all(), (array) FormRequest::getContent());
        $sended = $params['_csrf_token'] ?? '';
        $storedTokens = Flash::peek('_csrf_token');

        if (!in_array($sended, $storedTokens) or $sended == '')
            $this->reject(1, 'TOKEN CSRF EXPIRED');

        // remove the token and replace the current flash
        unset($storedTokens[array_search($sended, $storedTokens)]);

        Flash::set('_csrf_token',  $storedTokens);
    }


    /** 
     * Test if the route must be accessed by logged users
     *
     * @param array $info
     * @return void
     */
    public function requireUserLogged(array $info)
    {
        if (empty($info)) {
            return;
        }

        if (isset($info["auth"]) && $info["auth"]) {
            if (Request::path() != "/login") {
                if (!auth()->isLogged()) {
                    return $this->reject(2, 'REQUIRE USER AUTHENTICATION', Request::path());
                }
            }
        }

        if (isset($info["admin"]) && $info["admin"]) {
            if (!auth()->isAdmin()) {
                return $this->reject(3, 'REQUIRE USER AUTHENTICATION AS ADMIN', Request::path());
            }
        }
    }


    /**
     * Test if user level is allowed in this route
     *
     * @param array $info
     * @return void
     */
    public function requireUserLevel(array $info)
    {
        if (empty($info) || !isset($info["user_level"])) {
            return;
        }

        if (!auth()->isLogged()) {
            $info['auth'] = true;
            return $this->requireUserLogged($info);
        }

        $level = $info["user_level"];
        $user = auth()->getUser();
        $error = false;

        if (!is_bool($user)) {
            $user_level = $user->level;
        } else {
            return auth()->logout();
        }

        if (is_array($level)) {
            // multiple user levels
            if (!in_array($user_level, $level)) {
                $error = true;
            }
        } else if ($level != $user_level) {
            $error = true;
        }


        /**
         * A helper function
         * 
         * return the required user level
         */
        $helper = function ($requiredLevel) {
            return function (array $levels) use ($requiredLevel) {

                $s = '';

                if (!is_array($levels))
                    return $requiredLevel[$levels];
                else {

                    foreach ($levels as $k => $lv) {
                        if (in_array($k, $requiredLevel)) $s .= $lv . ' , ';
                    }

                    if (strlen($s) == 0 || count($levels) == 0) {
                        throw new Exception("Array with levels is empty \t or  Check if the level(s) required is an valid ", 1);
                    }

                    $s = substr($s, 0, strlen($s) - 2);
                    return $s;
                }
            };
        };

        if ($error) {
            return $this->reject(4, 'REQUIRE USER LEVEL', [$level, $user_level, $helper(is_array($level) ? $level : [$level])]);
        }
    }


    /**
     * test if a user must no be logged 
     *
     * @param array $info
     * @return void
     */
    public function requireUserNotLogged(array $info)
    {
        if (empty($info)) {
            return null;
        }

        if ($a = isset($info["auth"]) && $info["auth"]) {
            if (auth()->isLogged()) {
                return redirect($info["redirect"]);
            }
        }

        if (isset($info["admin"]) && $info["admin"]) {
            if (auth()->isAdmin()) {
                return redirect($info["redirect"]);
            }
        }

        if (isset($info["user_level"])) {

            if (!$a or is_null($info["user_level"])) return;

            if (auth()->getUser()->level !== $info["user_level"]) {
                return redirect($info["redirect"]);
            }
        }
    }
}
