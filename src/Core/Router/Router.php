<?php

namespace Zeero\Core\Router;

use Zeero\Facades\FormRequest;
use Zeero\Facades\Request;

use Zeero\Core\Router\RouteRequirement;
use Zeero\Core\Utils\RegexPatterns;
use Zeero\facades\Session;

/**
 * Router
 * 
 * Process and Dispatch Routes
 * 
 * @author carlosb bumba <carlosbumbanio@gmail.com>
 */
class Router
{

    /**
     * Routes Not Found Action
     *
     * @var callable|null
     */
    private $NotFoundAction;


    /**
     * Routes Not Authorized Action
     *
     * @var callable|null
     */
    private $UnauthorizedAction;

    private $opcional;


    /**
     * Generate a regular expression for a route
     *
     * @param string $route
     * @param object $obj
     * @return string
     */
    private function makeRegexp(string $route, $obj): string
    {
        $this->opcional = false;

        if (preg_match_all("/(\{[a-zA-Z0-9\-\_\?]+\})/", $route, $matches)) {

            for ($i = 0; $i < count($matches[1]); $i++) {

                $name = str_replace(["{", "}"], ["", ""],  $matches[1][$i]);

                if (strpos($name, "?") === 0) {
                    $this->opcional = true;
                    $name = str_replace("?", "", $name);
                }

                // default pattern
                $pattern = "_text";

                if (isset($obj->params)) {
                    $types = $obj->params;
                }

                if (isset($types[$name])) {
                    $pattern = $types[$name];
                }

                if (RegexPatterns::get($pattern)) {
                    $pattern = RegexPatterns::get($pattern);
                }

                if ($this->opcional) {
                    $pattern = "({$pattern})?";
                }

                $route = str_replace($matches[1][$i], $pattern, $route);
            }

            $this->hasParams = true;
        }

        return $route;
    }



    /**
     * Extract and Return the URL params
     *
     * @param string $route
     * @return array
     */
    private function UrlParams(string $route): array
    {
        $params = [];

        $pathParts = array_slice(explode('/', Request::path()), 1);
        $routeParts = array_slice(explode('/', $route), 1);

        foreach ($routeParts as $key => $routePart) {

            if (strpos($routePart, '{') === 0) {
                $name = str_replace(["{", "}"], ["", ""], $routePart);

                if (strpos($name, "?") === 0) {
                    $name = str_replace("?", "", $name);
                }

                if (strpos($pathParts[$key] ?? "", "+")) {
                    $pathParts[$key] = str_replace("+", " ", $pathParts[$key]);
                }

                if ($pathParts[$key] ?? null) {
                    $params[$name] = $pathParts[$key];
                }
            }
        }
        return $params;
    }



    /**
     * Set a action for NOT FOUND Routes
     *
     * @param callable $action
     * @return void
     */
    public function NotFoundAction(callable $action)
    {
        $this->NotFoundAction = $action;
    }

    /**
     * set an action for not unauthorized errors
     * 
     * 1 - Invalid Token
     * 
     * 2 - Require User Logged
     * 
     * 3 - Require Admin Logged
     * 
     * 4 - Require User Level
     * 
     * @param callable $action
     * @return void
     */
    public function UnauthorizedAction(callable $action)
    {
        $this->UnauthorizedAction = $action;
    }


    /**
     * Match and Dispatch Routes
     *
     * @param array $routeMap the list of registered routes
     * @return void
     */
    public function run(array $routeMap)
    {
        $path = Request::path();
        $method = strtolower(Request::method());
        $matrix = $routeMap[$method] ?? null;
        // object to test all the requirements
        $routeRequirement = new RouteRequirement($this->UnauthorizedAction);
        // dispatcher
        $dispatcher = new Dispatcher;

        $url_session = Session::get('_urls', []);

        if (!Request::isAjax()) {

            if ((count($url_session) + 1) == 10) {
                array_shift($url_session);
            }

            if (!in_array(Request::uri(), $url_session)) {
                $url_session[] = Request::uri();
                Session::set('_urls', $url_session);
            }
        }

        if ($matrix) {
            foreach ($matrix as $route => $object) {

                $pattern = $this->makeRegexp($route, $object);

                if ($pattern != "/") {
                    $pattern = "@^$pattern$@";
                } else {
                    $pattern = "@^/$@";
                }

                if (preg_match($pattern, $path) or preg_match($pattern, $path . '/')) {

                    $params = array_merge($this->UrlParams($route), FormRequest::all());

                    /**
                     * 
                     * first check if is defined a required token 
                     * if not defined request and test the CSRF token
                     * 
                     */
                    if (isset($object->tokenHandler)) {
                        // check if exists a header with the same name with token
                        // ex: api_token = X-API-TOKEN = X_API_TOKEN
                        $_key = $object->tokenHandler->getParamName();
                        $param_key = strtoupper($_key);
                        // append the header prefix X-
                        $param_key = "X-" . $param_key;
                        // replacea all - for _
                        if (strpos($param_key, '-') !== false) {
                            $param_key = str_replace('-', '_', $param_key);
                        }
                        // get all request headers
                        $headers = Request::headers();
                        /**
                         * if the current token is a header
                         * modify the *params array with the association
                         */
                        if (array_key_exists($param_key, $headers)) {
                            $params[$_key] = $headers[$param_key];
                        }
                        // test the requirement
                        $routeRequirement->requireValidToken($object->tokenHandler, $params);
                    } elseif (!Request::isMethod('HEAD') and !Request::isMethod('GET')) {
                        $routeRequirement->requireCSRFToken();
                    }

                    $routeRequirement->requireUserLogged($object->require);
                    $routeRequirement->requireUserLevel($object->require);
                    $routeRequirement->requireUserNotLogged($object->not_logged ?? []);

                    return $dispatcher->dispatch($object, $params);
                }
            }
        }

        $action = $this->NotFoundAction;

        if (!$action)
            die("REQUEST NOT FOUND");

        $action = $action($path);
        exit;
    }
}
