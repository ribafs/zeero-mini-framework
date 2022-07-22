<?php

namespace Zeero\Core\Router;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;
use Zeero\facades\FormRequest;



final class Dispatcher
{

    /**
     * Return the Arguments for a Closure Call
     *
     * @param Closure|callable $closure
     * @param array|null $params
     * @return array
     */
    private function closureArgs($closure, array $params = null): array
    {
        $reflectionFunc = new ReflectionFunction($closure);
        $reflectionParams = $reflectionFunc->getParameters();

        return $this->argsParameters($reflectionParams, $params);
    }



    /**
     * Return the Arguments for a Controller Method Call
     *
     * @param object $class
     * @param string $method
     * @param array $params
     * @return array
     */
    private function methodArgs(object $class, string $method, array $params = []): array
    {
        $reflectionFunc = new ReflectionMethod($class, $method);
        $reflectionParams = $reflectionFunc->getParameters();

        return $this->argsParameters($reflectionParams, $params);
    }



    /**
     * Return a typed value
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    private function typedValue($type, &$value)
    {
        switch ($type) {
            case "int":
                $value = intval($value);
                break;
            case "bool":
                $value = boolval($value);
                break;
            case "string":
                $value = strval($value);
                break;
            case "float":
                $value = floatval($value);
        }

        return $value;
    }




    /**
     * Capture and inject the required arguments of a closure or controller method
     *
     * @param array $reflectionParams
     * @param array $params
     * @return array
     */
    private function argsParameters(array $reflectionParams, array $params = [])
    {
        $args = [];

        for ($i = 0; $i < count($reflectionParams); $i++) {

            // if the parameter is not typed
            // ex: $n
            if (!$reflectionParams[$i]->hasType()) {
                if ($params) {
                    $args[] = $params[$reflectionParams[$i]->getName()] ?? null;
                } else {
                    $args[] = FormRequest::all_get()[$reflectionParams[$i]->getName()] ?? null;
                }
            } else {
                // if the parameter is typed
                // ex: int $n
                $reflectionType = $reflectionParams[$i]->getType();
                $name = $reflectionType->getName();
                // if the type is Builtin
                if (!$reflectionType->isBuiltin()) {
                    $args[] = new $name;
                } else {
                    // if exist in the given params array
                    if ($params && isset($params[$reflectionParams[$i]->getName()])) {
                        $value = $params[$reflectionParams[$i]->getName()];
                        $this->typedValue($name, $value);
                        $args[] = $value;
                    } else {
                        $value = FormRequest::all_get()[$reflectionParams[$i]->getName()] ?? "";
                        $this->typedValue($name, $value);
                        $args[] = $value;
                    }
                }
            }
        }

        return $args;
    }



    /**
     * Dispach a Route
     *
     * @param stdClass $object
     * @param array|null $params
     * @return void
     */
    public function dispatch(stdClass $object, array $params = null)
    {
        $response = null;

        if (is_array($object->action) and count($object->action) == 2) {

            list($controller, $method) = $object->action;

            $controller = new $controller;

            $args = $this->methodArgs($controller, $method, $params ?? []);

            $response = call_user_func_array([$controller, $method], $args);
        } elseif (is_callable($object->action)) {

            $args = $this->closureArgs($object->action, $params ?? null);
            $response = call_user_func_array($object->action, array_values($args));
        } elseif (is_array($object->action) and count($object->action) == 1) {
            $file = $file = $object->action[0];
            if(!strpos($file , '.')) $file .='.twig';
            
            if (file_exists(VIEWS_DIR . 'renders' . DS . ($file))) {
                view($file);
            } else {
                throw new InvalidArgumentException("Bad Route Definition ( {$object->route} ) ");
            }

        }

        $status = null;
        $headers = null;

        if (is_array($response)) {

            if (isset($response['redirect'])) {
                return redirect($response['redirect']);
            }

            if (isset($response['status'])) {

                if (is_int($response['status'])) $status = $response['status'];

                unset($response['status']);
            }

            if (isset($response['headers'])) {

                // if is a valid associative array
                if (
                    $c1 = count(array_keys($response['headers']))
                    and
                    $c2 = count(array_values($response['headers']))
                    and
                    $c1 == $c2
                ) {
                    $headers = $response['headers'];
                }

                unset($response['headers']);
            }
        }

        /**
         * 
         * Response
         * 
         */
        if (is_array($response) || is_object($response))
            return json_response($response, $status ?? 200, $headers ?? []);
        elseif (is_string($response))
            return response($response);
    }
}
