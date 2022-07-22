<?php

use Symfony\Component\HttpFoundation\Response;
use Zeero\Core\Registry as Collector;
use Zeero\Core\Timer;
use Monolog\Logger;
use Symfony\Component\VarDumper\VarDumper;
use Zeero\Core\Auth\Auth;
use Zeero\Core\Env;
use Zeero\Core\Router\Route;
use Zeero\Core\Router\URL;


/**
 * respond the request
 *
 * @param string $content
 * @param integer $status
 * @param array $headers
 * @return void
 */
function response(string $content, $status = 200, $headers = [])
{
    $r = new Response($content, $status, $headers);
    $r->send();
    exit;
}


/**
 * respond with a JSON content
 *
 * @param mixed $content
 * @param integer $status
 * @param array $headers
 * @return void
 */
function json_response($content, $status = 200, $headers = [])
{
    $data = json_encode($content);
    $headers["Content-Type"] = "application/json";
    response($data, $status, $headers);
}


/**
 * redirect to a URL
 *
 * @param string $to
 * @return void
 */
function redirect(string $to)
{
    return URL::go($to);
}


/**
 * display a dynamic template
 *
 * @param string $render
 * @param array $params
 * @param integer $status
 * @param array $headers
 * @return void
 */
function view(string $render, array $params = [], int $status = 200, array $headers = [])
{
    $content = app("twig")->render("renders" . DS . $render, $params);
    response($content, $status, $headers);
}


/**
 * display a static template
 *
 * @param string $template
 * @param integer $status
 * @param array $headers
 * @return void
 */
function display(string $template, int $status = 200, array $headers = [])
{
    $content = app('twig')->render('templates' . DS . $template);
    response($content, $status, $headers);
}


/**
 * get the full url of a named route
 *
 * @param string $name
 * @param array $params
 * @return string|null
 */
function route(string $name, $params = [])
{
    return Route::route($name, $params);
}



/**
 * get a application dependency object
 *
 * @param string $key
 * @return mixed
 */
function app(string $key)
{
    return Collector::getInstance()->get($key);
}


/**
 * register a application dependency
 *
 * @param string $key
 * @param object $component
 * @return void
 */
function app_register(string $key, object $component)
{
    Collector::getInstance()->collect($key, $component);
}



/**
 * get the Auth instance
 *
 * @return Auth
 */
function auth(): Auth
{
    return (new Auth);
}



/**
 * get the Logger instance
 *
 * @return Logger
 */
function app_log(): Logger
{
    return app("app_logger");
}


/**
 * get the timer instance
 *
 * @return Timer
 */
function timer(): Timer
{
    return new Timer;
}


/**
 * __dump
 *
 * @param mixed ...$args
 * @return void
 */
function _dump(...$args)
{
    return VarDumper::dump($args);
}



function public_path(string $str = '')
{
    return PUBLIC_DIR . $str;
}


function isValid(string $regexp, string $value)
{
    return preg_match($regexp, $value) !== false;
}


/**
 * display a error page
 *
 * @param string $title the page title
 * @param string $message the message to be displayed
 * @param integer $code the HTTP Response Status
 * @param string|null $template 
 * @return void
 */
function errorPage(string $title, string $message, int $code, string $template = null)
{
    $data = [];
    // page information
    $data['code'] = $code;
    $data['title'] = $title;
    $data['message'] = $message;
    // the default template
    $render = 'error.twig';
    // template from the env file
    if (Env::has('ERROR_PAGE')) {
        $render = Env::get('ERROR_PAGE');
    }
    // custom template
    if (isset($template)) {
        $render = $template;
    }
    // check if the file exists
    if (!file_exists(VIEWS_DIR . 'renders' . DS . $render)) {
        throw new Exception("ERROR PAGE '{$render}' NOT EXISTS ");
    }
    // display the page
    view($render, $data, $code);
}
