<?php

namespace Zeero\Core\Router;

use App\Controllers\AuthController;
use Closure;
use Exception;


/**
 * Route
 * 
 * A representation of Routes in application Routing
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
final class Route
{
    
    // array with all the routes
    private $map;
    // the information about the last route
    private $lastRoute;
    // the routes names
    private static $names;
    // token handler names
    private static $handlers;

    private static $instance;

    private function __construct()
    {
    }


    /**
     * Singleton Method
     *
     * @return Route
     */
    public static function getInstance(): Route
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register a route
     *
     * @param string $method
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return void
     */
    private function register(string $method, string $route, $action, bool $auth, $level)
    {
        $requires = ['auth' => $auth, 'user_level' => $level];
        $token = null;

        if (isset($this->currentGroupRequirements)) {
            foreach ($this->currentGroupRequirements as $key => $value) {
                if (!is_array($value)) {
                    // group prefix
                    if ($key == 'prefix') {
                        $route = $value . $route;
                    } else {
                        // required token
                        if ($key == 'token') {
                            $token = $value;
                        } else {
                            // normal attribute
                            $requires[$key] = $value;
                        }
                    }
                } else {
                    // not logged array
                    if (count($value) == 3 and $key == 'not_logged') {
                        list($_auth, $_level, $redirect) = $value;
                    }
                }
            }
        }

        /**
         * 
         * cretate a StdObject that represent the route
         * and insert the defaults or overwrited requirements
         * 
         */
        $this->map[$method][$route] = (object) ['action' =>  $action , 'route' => $route];
        $this->map[$method][$route]->require = $requires;
        $this->lastRoute = [$method, $route];

        if (isset($this->_token)) {
            $token = $this->_token;
        }

        if ($token) {
            $this->tokenHandler($this->map[$method][$route], $token);
        }

        /**
         * the dynamic attribute *_token is triggered in *resource() method
         * and finish at *delete method
         * 
         * so after that method this attributes must be unseted
         */
        if (isset($this->_token) and $method == 'delete') unset($this->_token); 

        /**
         * check if the current group is using the *not_logged feature
         * the next statement will overwrite the current *auth and other route requirements
         * to shared requirements from the group
         */
        if (isset($_auth)) {
            $this->route_not_logged($_auth, $_level, $redirect);
        }
    }



    private function tokenHandler(&$route, string $handlername)
    {
        if (!isset(self::$handlers[$handlername])) {
            throw new Exception("Token Validation Handler: '{$handlername}' Not Exists");
        }

        $route->tokenHandler = self::$handlers[$handlername];
    }


    /**
     * Set a Route Name
     *
     * @param string $name
     * @return void
     */
    public function name(string $name)
    {

        if (is_null($this->lastRoute)) {
            throw new Exception("No route selected");
        }

        self::$names[$name] = $this->lastRoute;
    }


    /**
     * Create a Route Group
     *
     * @param array $requires the group requirements
     * @param callable $info the closure that contains the group definition
     * @return void
     */
    public function group(array $requires, callable $info)
    {

        if (isset($this->currentGroupRequirements)) {

            if (isset($requires['prefix']) && isset($this->currentGroupRequirements['prefix'])) {
                $requires['prefix'] = $this->currentGroupRequirements['prefix'] . $requires['prefix'];
                unset($this->currentGroupRequirements['prefix']);
            }

            $requires = array_merge($requires, $this->currentGroupRequirements);
        }

        $this->currentGroupRequirements = $requires;
        $info($this);
        unset($this->currentGroupRequirements);
    }



    /**
     * Create a not logged Route
     *
     * @param boolean $auth
     * @param int|array|null $level
     * @param string $redirect
     * @return Route
     */
    public function route_not_logged(bool $auth = false, $level = null, string $redirect = '')
    {
        $lastRouteInfo = $this->lastRoute;

        if (is_null($lastRouteInfo)) {
            throw new Exception("No route selected");
        }

        $this->map[$lastRouteInfo[0]][$lastRouteInfo[1]]->not_logged = ['auth' => $auth, 'user_level' => $level, 'redirect' => $redirect];
        return $this;
    }



    /**
     * get the route by name
     *
     * @param string $name
     * @param array|null $params
     * @return string|null
     */
    public static function route(string $name, array $params = null)
    {
        if (isset(self::$names[$name])) {
            $route = self::$names[$name][1];

            $route_parts = explode('/', $route);
            // filter params
            $route_parts = array_filter($route_parts, function ($i) {
                return preg_match("/(\{[a-zA-Z0-9\?\_\-]+\})/", $i);
            });

            foreach ($route_parts as $param) {

                $real_param = substr($param, 1, strlen($param) - 2);

                if (strpos($real_param, '?') === 0)
                    $real_param = substr($real_param, 1);

                if ($params and array_key_exists($real_param, $params)) {
                    $route = str_replace($param, $params[$real_param], $route);
                } else {
                    // check if is opcional parameter
                    if (strpos($param, '?') === 1) {
                        $route = str_replace("/{$param}", "", $route);
                    }
                }
            }
            return $route;
        }
    }


    /**
     * Register a Parameter Pattern
     *
     * @param array $pair the associative array that contains the parameter name as key and patterns as value
     * @throws Exception if no route is selected
     * @return void
     */
    public function where(array $pair)
    {
        $lastRouteInfo = $this->lastRoute;

        if (is_null($lastRouteInfo)) {
            throw new Exception("No route selected");
        }

        foreach ($pair as $paramName => $regexp) {
            $this->map[$lastRouteInfo[0]][$lastRouteInfo[1]]->params[$paramName] = $regexp;
        }
    }


    /**
     * Return all the routes registered
     *
     * @return array|null
     */
    public function getAllRoutes()
    {
        return $this->map;
    }


    /**
     * Register a GET route
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return Route
     */
    public function get(string $route, $action, bool $auth = false, $level = null)
    {
        $this->register('get', $route, $action, $auth, $level);
        return $this;
    }


    public function auth(string $controller = null)
    {
        $this->setControllerRoutes(
            $controller ?? AuthController::class,
            [
                '/login' => ['POST', 'login', false, null, [true, null, '/']],
                '/register' => ['POST', 'register', false, null, [true, null, '/']],
            ]
        );

        $this->get('/logout', function () {
            auth()->logout();
        });
    }


    /**
     * define a set of routes for a specified controller
     *
     * @param string $classname
     * @param array $routes
     * @return void
     */
    public function setControllerRoutes(string $classname, array $routes)
    {
        foreach ($routes as $route => $info) {
            // must contains *method and *method
            if (is_array($info) and count($info) > 1) {
                list($http_method, $method) = $info;
                $auth = $info[2] ?? false;
                $level = $info[3] ?? null;
                $not_logged = $info[4] ?? 0;
                // register the route
                $this->match(
                    [$http_method],
                    $route,
                    [$classname, $method],
                    $auth,
                    $level
                );
                // check if is a valid not_logged array
                if (is_array($not_logged) and count($not_logged) == 3) {
                    list($_auth, $_level, $redirect) = $not_logged;
                    $this->route_not_logged($_auth, $_level, $redirect);
                }
            }
        }
    }


    /**
     * Register a HEAD route
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return Route
     */
    public function head(string $route, $action, bool $auth = false, $level = null)
    {
        $this->register('head', $route, $action, $auth, $level);
        return $this;
    }





    /**
     * Register a POST route
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return Route
     */
    public function post(string $route, $action, bool $auth = false, $level = null)
    {
        $this->register('post', $route, $action, $auth, $level);
        return $this;
    }




    /**
     * Register a PUT route
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return Route
     */
    public function put(string $route, $action, bool $auth = false, $level = null)
    {
        $this->register('put', $route, $action, $auth, $level);
        return $this;
    }



    /**
     * Register a PATCH route
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return Route
     */
    public function patch(string $route, $action, bool $auth = false, $level = null)
    {
        $this->register('patch', $route, $action, $auth, $level);
        return $this;
    }



    /**
     * Register a DELETE route
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return Route
     */
    public function delete(string $route, $action, bool $auth = false, $level = null)
    {
        $this->register('delete', $route, $action, $auth, $level);
        return $this;
    }



    /**
     * Register a route in all request methods
     *
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|array|null $level
     * @return void
     */
    public function any(string $route, $action, bool $auth = false, $level = null)
    {
        $methods = ['get', 'head', 'post', 'put', 'delete', 'patch'];
        foreach ($methods as $method) {
            $this->register($method, $route, $action, $auth, $level);
        }
    }



    /**
     * Register a route in specified request methods
     *
     * @param array $methods
     * @param string $route
     * @param Closure|array|null $action
     * @param boolean $auth
     * @param int|level|null $level
     * @return void
     */
    public function match(array $methods, string $route, $action, bool $auth = false, $level = null)
    {
        foreach ($methods as $method) {
            $this->register(strtolower($method), $route, $action, $auth, $level);
        }
    }


    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $paramName
     * @param Closure $action
     * @return RouteTokenHandler
     */
    public function AddTokenValidator(string $name, string $paramName, Closure $action)
    {
        if (isset(self::$handlers[$name])) {
            throw new Exception("Duplicate Definition of {$name} Token Handler");
        }

        $handler = new RouteTokenHandler($action, $paramName);
        self::$handlers[$name] = $handler;
        return $handler;
    }


    /**
     * Undocumented function
     *
     * @param string $handlername
     * @return void
     */
    public function validToken(string $handlername)
    {
        $lastRouteInfo = $this->lastRoute;

        if (is_null($lastRouteInfo)) {
            throw new Exception("No route selected");
        }

        $this->tokenHandler(
            $this->map[$lastRouteInfo[0]][$lastRouteInfo[1]],
            $handlername
        );
    }



    /**
     * Register a REST Resource
     *
     * @param string $name the name used in URL
     * @param string $classname the controller classname
     * @param boolean $auth
     * @param int|array|null $level
     * @param string|null $token
     * @return void
     */
    public function resource(string $name, string $classname, bool $auth = false, $level = null, string $token = null)
    {

        if ($token) $this->_token = $token;

        //index
        $this->match(
            ['get', 'head'],
            "/{$name}",
            [$classname, 'index'],
            $auth,
            $level
        );

        //store
        $this->post(
            "/{$name}",
            [$classname, 'store'],
            $auth,
            $level
        );

        //create
        $this->match(
            ['get', 'head'],
            "/{$name}/create",
            [$classname, 'create'],
            $auth,
            $level
        );

        //show
        $this->get(
            "/{$name}/{id}",
            [$classname, 'show'],
            $auth,
            $level
        );

        $this->head(
            "/{$name}/{id}",
            [$classname, 'show'],
            $auth,
            $level
        );

        //edit        
        $this->get(
            "/{$name}/{id}/edit",
            [$classname, 'edit'],
            $auth,
            $level
        );

        $this->head(
            "/{$name}/{id}/edit",
            [$classname, 'edit'],
            $auth,
            $level
        );

        //update
        $this->put(
            "/{$name}/{id}",
            [$classname, 'update'],
            $auth,
            $level
        );

        $this->patch(
            "/{$name}/{id}",
            [$classname, 'update'],
            $auth,
            $level
        );

        //destroy
        $this->delete(
            "/{$name}/{id}",
            [$classname, 'destroy'],
            $auth,
            $level
        );
    }
}
