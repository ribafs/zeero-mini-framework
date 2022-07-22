<?php

namespace Zeero\facades;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;


abstract class Request
{
    private static function initialize()
    {
        $r = new HttpFoundationRequest(
            [],
            [],
            [],
            [],
            [],
            $_SERVER,
            []
        );

        return $r;
    }


    //

    public static function mimeType(string $format)
    {
        return self::initialize()->getMimeType($format);
    }


    public static function mimeTypes(string $format)
    {
        return self::initialize()->getMimeTypes($format);
    }


    public static function format(string $mimeType)
    {
        return self::initialize()->getFormat($mimeType);
    }

    public static function requestFormat(?string $default = "html")
    {
        return self::initialize()->getRequestFormat($default);
    }

    public static function setFormat(string $format, $mimeTypes)
    {
        return self::initialize()->setFormat($format, $mimeTypes);
    }

    //    
    public static function method()
    {
        return self::initialize()->getMethod();
    }


    public static function isMethod(string $method)
    {
        return self::initialize()->isMethod(strtoupper($method));
    }

    public static function isMethodSafe()
    {
        return self::initialize()->isMethodSafe();
    }

    public static function isMethodIdempotent()
    {
        return self::initialize()->isMethodSafe();
    }

    public static function isMethodCacheable()
    {
        return self::initialize()->isMethodCacheable();
    }

    public static function realMethod()
    {
        return self::initialize()->getRealMethod();
    }

    public static function path()
    {
        return self::initialize()->getPathInfo();
    }

    public static function basePath()
    {
        return self::initialize()->getBasePath();
    }

    public static function makeUri(string $path)
    {
        return self::initialize()->getUriForPath($path);
    }

    public static function relativeUri(string $path)
    {
        return self::initialize()->getRelativeUriForPath($path);
    }

    public static function uri()
    {
        return self::initialize()->getRequestUri();
    }

    public static function url()
    {
        return self::initialize()->getUri();
    }

    public static function baseUrl()
    {
        return self::initialize()->getBaseUrl();
    }

    public static function toArray()
    {
        return self::initialize()->toArray();
    }

    public static function eTags()
    {
        return self::initialize()->getETags();
    }

    public static function isNoCache()
    {
        return self::initialize()->isNoCache();
    }

    public static function languages()
    {
        return self::initialize()->getLanguages();
    }

    public static function charsets()
    {
        return self::initialize()->getCharsets();
    }

    public static function encodings()
    {
        return self::initialize()->getEncodings();
    }

    public static function setdefaultDefault(string $locale)
    {
        self::initialize()->setDefaultLocale($locale);
    }

    public static function getDefaultLocale()
    {
        return self::initialize()->getDefaultLocale();
    }

    public static function getlocale()
    {
        return self::initialize()->getLocale();
    }

    public static function content(bool $asResource = false)
    {
        return self::initialize()->getContent($asResource);
    }

    public static function contentType()
    {
        return self::initialize()->getContentType();
    }

    public static function acceptableContentTypes()
    {
        return self::initialize()->getAcceptableContentTypes();
    }

    public static function isAjax()
    {
        return self::initialize()->isXmlHttpRequest();
    }

    public static function preferSafeContent()
    {
        return self::initialize()->preferSafeContent();
    }

    public static function queryString()
    {
        return self::initialize()->getQueryString();
    }
    //

    public static function port()
    {
        return self::initialize()->getPort();
    }


    public static function domain()
    {
        return self::hostName() . ":" . self::port();
    }


    public static function protocolVersion()
    {
        return self::initialize()->getProtocolVersion();
    }

    public static function sheme()
    {
        return self::initialize()->getScheme();
    }

    public static function headers()
    {
        return self::initialize()->server->getHeaders();
    }

    public static function secure()
    {
        return self::initialize()->isSecure();
    }

    public static function shemeAndHost()
    {
        return self::initialize()->getSchemeAndHttpHost();
    }

    public static function httpHost()
    {
        return self::initialize()->getHttpHost();
    }

    public static function hostName()
    {
        return self::initialize()->getHost();
    }

    public static function trustedHosts()
    {
        return self::initialize()->getTrustedHosts();
    }
    //
    public static function ips()
    {
        self::initialize()->getClientIps();
    }

    public static function ip()
    {
        return self::ips()[0];
    }

    public static function user()
    {
        return self::initialize()->getUser();
    }

    public static function password()
    {
        return self::initialize()->getPassword();
    }

    public static function userInfo()
    {
        return self::initialize()->getUserInfo();
    }
}
