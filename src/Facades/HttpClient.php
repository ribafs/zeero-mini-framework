<?php

namespace Zeero\facades;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

/**
 * Http Client Facade
 * 
 * used to make http requests
 *
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class HttpClient
{
    private static $config = [];
    private static $request;

    /**
     * set the client configuration
     *
     * @param array $config
     * @return void
     */
    public static function setConfig(array $config)
    {
        self::$config = $config;
    }

    /**
     * send a request
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @throws Exception if GuzzleHttp\Client class not exists
     * @return ResponseInterface
     */
    public static function request(string $method, string $uri, array $options = [])
    {

        if (!class_exists(Client::class)) {
            throw new Exception("GuzzleHttp\Client class is required to use the HttpClient Facade");
        }

        try {
            $client = new Client(self::$config);
            $response = $client->request($method, $uri, $options);
        } catch (ClientException | ServerException $e) {
            $response = $e->getResponse();
            self::$request = $e->getRequest();
        }

        return $response;
    }


    /**
     * the last RequestInterface implementation
     *
     * @return void
     */
    public static function lastRequest()
    {
        if (isset(self::$request)) return self::$request;
    }


    public static function __callStatic($name, $arguments)
    {
        if (in_array(strtolower($name), ['get', 'post', 'head', 'put', 'patch', 'delete'])) {

            $uri = $arguments[0] ?? '';

            $options = $arguments[1] ?? 0;
            if (!is_array($options)) $options = [];

            return self::request($name, $uri, $options);
        }
    }
}
