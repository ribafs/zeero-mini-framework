<?php

declare(strict_types=1);

namespace Zeero;

use DateTimeZone;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Zeero\Core\Crypto\Crypto;
use Zeero\Core\Debug\ZDebug;
use Zeero\Core\Env;
use Zeero\Core\Utils\Envfile;
use Zeero\Database\DataBase;

/**
 * The Framework Kernel
 * 
 */
abstract class Kernel
{

    /**
     * Bootstrap the user defined action
     *
     * @return void
     */
    public static function UserBootstrap()
    {
        self::BootAppConstants();
        require_once APP_DIR . 'boot.php';
        require_once APP_DIR . 'DataBase' . DS . 'factory.php';
        require_once APP_DIR . 'app.php';
        require_once APP_DIR . "Views" . DS . "functions.php";
    }

    /**
     * Bootstrap the Application constants
     *
     * @return void
     */
    public static function BootAppConstants()
    {
        // FRAMEWORK CONTANTS
        if (!defined("DS")) define("DS", DIRECTORY_SEPARATOR);

        if (defined('_ROOT_')) {
            // ZCLI
            if (!defined("_BASE_DIR_")) define("_BASE_DIR_", _ROOT_ . DS . 'App' . DS);
            if (!defined("PUBLIC_DIR")) define("PUBLIC_DIR",  _BASE_DIR_ . "site" . DS);
            if (!defined("APP_DIR")) define("APP_DIR", _BASE_DIR_);
        } else {
            // WEB
            define("_ROOT_", $_SERVER["DOCUMENT_ROOT"]);
            if (!defined("_BASE_DIR_")) define("_BASE_DIR_", _ROOT_ . DS);
            if (!defined("PUBLIC_DIR")) define("PUBLIC_DIR",  _BASE_DIR_ . "site" . DS);
            if (!defined("APP_DIR")) define("APP_DIR", _BASE_DIR_ . ".." . DS);
        }

        if (!defined("FRAMEWORK_DIR")) define("FRAMEWORK_DIR", __DIR__ . DS);

        // DEFAULTS
        if (!defined("VIEWS_DIR")) define("VIEWS_DIR",  APP_DIR . "Views" . DS);
        if (!defined("APP_LOGGER_LOCATION")) define("APP_LOGGER_LOCATION", APP_DIR . "Logs" . DS . "app.log");
        if (!defined("TIMEZONE")) define("TIMEZONE", "Africa/Luanda");

        // set the exception to zdebug
        set_exception_handler(function ($e) {
            ZDebug::debug($e);
        });


        // load the .env file 
        $envFile = new Envfile;
        $envFile->setFilename('.env');

        Env::setEnvFile($envFile);

        // set the crypto configs
        $cipherMethod = Env::get('CIPHER_METHOD') ?? 'aes-256-cbc';
        $bytesPerBlock = Env::get('BYTES_PER_BLOCK') ?? 16;
        $encryptionBlocks = Env::get('FILE_ENCRYPTION_BLOCKS') ?? 10000;

        Crypto::setConfigs($bytesPerBlock, $encryptionBlocks);
        Crypto::setMethod($cipherMethod);

        if (($t = Env::get('APP_KEY')) and $t != '<generate this>')
            Crypto::setKey($t);

        $dev = Env::get('DEV');

        if ($dev == 1) $bool = true;
        else $bool = false;

        if (!defined('DEV')) define('DEV', $bool);

        $uploads_dir = Env::get('UPLOADS_DIR');

        if (!$uploads_dir) {
            $uploads_dir = PUBLIC_DIR . 'uploads';
        } else {
            $uploads_dir = APP_DIR . $uploads_dir;
        }

        if (!defined("UPLOADS_DIR")) define("UPLOADS_DIR", $uploads_dir);
    }


    /**
     * Load the framework core functions
     *
     * @return void
     */
    public static function BootFrameworkFunctions()
    {
        require_once "Core/app.php";
    }


    public static function BootAppLogger()
    {
        self::BootAppConstants();
        self::BootFrameworkFunctions();

        /**
         * 
         * LOGGER
         * 
         */
        $app_logger = new \Monolog\Logger('app');

        $app_logger->pushHandler(
            new \Monolog\Handler\StreamHandler(APP_LOGGER_LOCATION,  \Monolog\Logger::INFO)
        );

        $app_logger->setTimezone(new DateTimeZone(TIMEZONE));

        app_register("app_logger", $app_logger);
    }


    /**
     * Bootstrap the DataBase Layer
     *
     * @return void
     */
    public static function DataBaseBoot()
    {
        self::BootAppConstants();
        self::BootFrameworkFunctions();

        // only try to connect to the database if the db_name is set in .env file
        if (Env::has('DB_NAME') &&  !empty(trim(Env::get('DB_NAME')))) {
            DataBase::createConnection();
        }
    }


    /**
     * Bootstrap the Entire Application
     *
     * @return void
     */
    public static function ApplicationBoot()
    {
        self::BootAppConstants();
        self::BootFrameworkFunctions();

        //TIMEZONE
        date_default_timezone_set(TIMEZONE);

        if (DEV == 1) {
            ini_set("display_errors", "true");
            error_reporting(E_ALL & ~E_NOTICE);
        } else {
            ini_set("display_errors", "false");
            error_reporting(~E_ALL);
        }

        // disable expose_php directive
        ini_set('expose_php', 'off');

        // remove the X-Powered-By Header
        header_remove('X-Powered-By');

        //SESSION
        $flash = new FlashBag("flash_messages");
        $session = new Session;
        $session->registerBag($flash);
        $session->start();


        // filesystem loader
        $loader = new FilesystemLoader([VIEWS_DIR, PUBLIC_DIR . "assets"]);
        // environment
        $twig = new Environment($loader);

        if (DEV)
            $twig->addExtension(new DebugExtension());


        app_register("twig", $twig);
        app_register("session", $session);

        // FRAMEWORK TWIG DEFAULTS FUNCTIONS
        require_once "Core/views_functions.php";

        self::UserBootstrap();
        self::BootAppLogger();
        self::DataBaseBoot();
    }
}
