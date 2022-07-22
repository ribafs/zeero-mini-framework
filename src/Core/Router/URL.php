<?php

namespace Zeero\Core\Router;

use Zeero\facades\Request;
use Zeero\facades\Session;

/**
 * URL class
 * 
 * a helper class that manipulate basics URL operations
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class URL
{

    /**
     * go to a URL
     *
     * @param string $url
     * @return void
     */
    public static function go(string $url)
    {
        header("location:{$url}");
        exit;
    }

    /**
     * go to previous URL
     *
     * @return void
     */
    public static function back()
    {
        $urls = Session::get('_urls', []);
        $index =  array_search(Request::uri(), $urls);

        if (isset($urls[$index - 1])) self::go($urls[$index - 1]);
    }

    /**
     * get the previous URL
     *
     * @return string|null
     */
    public static function backURL()
    {
        $urls = Session::get('_urls', []);
        $index =  array_search(Request::uri(), $urls);

        if (isset($urls[$index - 1])) return ($urls[$index - 1]);
    }


    /**
     * go to a named route
     *
     * @param string $route
     * @param array|null $params
     * @return void
     */
    public static function goToRoute(string $route, array $params = null)
    {
        $route = Route::route($route, $params);

        if ($route) self::go($route);
    }
}
