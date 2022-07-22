<?php

use Zeero\facades\{Flash, Session, Request};
use Twig\TwigFunction;
use Zeero\Core\Router\Route;
use Zeero\Core\Router\URL;
use Zeero\Core\Timer;

/**
 * 
 * HERE IS DEFINED THE TWIG DEFAULT FUNCTIONS
 * 
 * - auth() -> return the Auth instance
 * - current_uri() -> return the current request uri
 * - timer() -> return a Timer instance
 * - _dump(arg) -> use the _dump zeero function
 * - session(name) -> return the value of session 'name'
 * - has_session(name) -> return if the session 'name' was defined
 * - flash(name) -> return the value of flash 'name'
 * - has_flash(name) -> return if the flash 'name' was defined
 * - csrfMeta() -> return a meta elemtent with a stored token ( for CSRF purposes )
 * - csrfInput() -> return a input:hidden element with a stored token ( for CSRF purposes)
 * - csrfToken() -> return a stored token ( for CSRF purposes)
 * - url_token(name) -> return a token for url
 * 
 * - asset(name) -> return the asset 'name' path
 * - render(name) -> return the macro 'name' path
 * - helper(name) -> return the helper 'name' path
 * - component(name) -> return the component 'name' path
 * - template(name) -> return the template 'name' path
 * - macro(name) -> return the macro 'name' path
 * - uploades(name) -> return uploaded file path
 * 
 * by: Carlos Bumba
 */

$functions = [];

// 



$functions[] = new TwigFunction("timer", function () {
   return new Timer;
});

$functions[] = new TwigFunction("_dump", function (...$arg) {
   return _dump($arg);
});

$functions[] = new TwigFunction("auth", function () {
   return auth();
});

$functions[] = new TwigFunction("current_uri", function () {
   return Request::uri();
});

$functions[] = new TwigFunction("has_errors", function ($name = '_form_error') {
   return Flash::has($name);
});

$functions[] = new TwigFunction("get_errors", function ($name = '_form_error') {
   return Flash::get($name);
});

$functions[] = new TwigFunction("session", function ($name) {
   return Session::get($name);
});

$functions[] = new TwigFunction("has_session", function ($name) {
   return Session::has($name);
});

$functions[] = new TwigFunction("flash", function ($name) {
   return Flash::get($name);
});

$functions[] = new TwigFunction("has_flash", function ($name) {
   return Flash::has($name);
});

$functions[] = new TwigFunction("backURL", function () {
   return URL::backURL();
});

$functions[] = new TwigFunction("route", function ($name, $params = null) {
   return Route::route($name, $params);
});


$functions[] = new TwigFunction("csrfMeta", function () {
   $token = base64_encode(openssl_random_pseudo_bytes(20));
   Session::set("_csrf_token", $token);
   $meta = "<meta name=\"_csrf_token\" content=\"{$token}\" />";
   echo $meta;
});


$functions[] = new TwigFunction("csrfInput", function () {
   $token = base64_encode(openssl_random_pseudo_bytes(20));

   if (!Flash::has('_csrf_token'))
      Flash::set("_csrf_token", $token);
   else
      Flash::add('_csrf_token', $token);

   $input = "<input id=\"_tkn\" type=\"hidden\" name=\"_csrf_token\" value=\"{$token}\" />";
   echo $input;
});


$functions[] = new TwigFunction("csrfToken", function () {
   $token = base64_encode(openssl_random_pseudo_bytes(20));

   if (!Flash::has('_csrf_token'))
      Flash::set("_csrf_token", $token);
   else
      Flash::add('_csrf_token', $token);

   return $token;
});


$functions[] = new TwigFunction("url_token", function ($name) {
   $token = md5(openssl_random_pseudo_bytes(14));

   if (!Flash::has($name))
      Flash::set($name, $token);
   else
      Flash::add($name, $token);

   return $token;
});


//TEMLATES
$functions[] = new TwigFunction("template", function ($filename) {
   $path = "templates/" . $filename;

   if (!file_exists(VIEWS_DIR . $path)) {
      throw new \Zeero\Core\Exceptions\NotFoundException("TEMPLATE: {$path} NOT FOUND");
   }

   return $path;
});



//COMPONENT
$functions[] = new TwigFunction("component", function ($filename) {
   $path = "components/" . $filename;

   if (!file_exists(VIEWS_DIR . $path)) {
      throw new \Zeero\Core\Exceptions\NotFoundException("COMPONENT: {$path} NOT FOUND");
      return;
   }

   return $path;
});




//RENDERS
$functions[] = new TwigFunction("render", function ($filename) {
   $path = "renders/" . $filename;

   if (!file_exists(VIEWS_DIR . $path)) {
      throw new \Zeero\Core\Exceptions\NotFoundException("RENDER: {$path} NOT FOUND");
      return;
   }

   return $path;
});



// HELPER
$functions[] = new TwigFunction("helper", function ($filename) {

   if (!strpos($filename, '.twig')) $filename .= '.twig';

   $path = "helpers/" . $filename;

   if (!file_exists(VIEWS_DIR . $path)) {
      throw new \Zeero\Core\Exceptions\NotFoundException("HELPER: {$path} NOT FOUND");
      return;
   }

   return $path;
});




//MACROS
$functions[] = new TwigFunction("macro", function ($filename) {

   if (!strpos($filename, '.twig')) $filename .= '.twig';

   $path = "macros/" . $filename;

   if (!file_exists(VIEWS_DIR . $path)) {
      throw new \Zeero\Core\Exceptions\NotFoundException("MACRO: {$path} NOT FOUND");
      return;
   }

   return $path;
});





//ASSETS
$functions[] = new TwigFunction("asset", function ($filename, $noCheck = false, $noCache = true) {
   $path = "/site/assets/" . $filename;

   if (!file_exists(_ROOT_ . DS . $path) and $noCheck == false) {
      throw new \Zeero\Core\Exceptions\NotFoundException("ASSET: {$path} NOT FOUND");
      return;
   }

   if ($noCache)
      $path .= "?" . bin2hex(random_bytes(3));

   return $path;
});



//MEDIA
$functions[] = new TwigFunction("uploaded", function ($filename) {
   $path = "/site/uploads/" . $filename;

   if (!file_exists(_ROOT_ . DS . $path)) {
      throw new \Zeero\Core\Exceptions\NotFoundException("UPLOADED FILE: {$path} NOT FOUND");
      return;
   }

   return $path . "?" . bin2hex(random_bytes(3));
});


// 
// STORE FUNCTIONS
foreach ($functions as $fn) {
   app("twig")->addFunction($fn);
}
