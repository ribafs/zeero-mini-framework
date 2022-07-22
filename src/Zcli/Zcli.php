<?php

namespace Zeero\Zcli;

use Zeero\Kernel;

define("_ROOT_", getcwd());
define("_BASE_DIR_", _ROOT_ . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR);

Kernel::BootAppConstants();


/**
 * Zeero Command Line Interface
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
abstract class Zcli
{
    private static array $input = [];

    public static $cmds = [];

    /**
     * Show Avaible command
     *
     * @param array $commands
     * @return void
     */
    private static function commandsAvaible(array $commands)
    {
        echo str_repeat("-", 9) . "\n";

        foreach ($commands as $cmd) {
            echo "--> $cmd \n";
        }

        echo str_repeat("-", 9) . "\n";
        exit;
    }


    /**
     * Show a invalid command message
     *
     * @param string $cmd
     * @return void
     */
    private static function invalidCommand(string $cmd)
    {
        echo "Invalid Command '{$cmd}' \n";
        exit;
    }

    /**
     * Show a invalid argument message
     *
     * @param string $command
     * @param string $arg
     * @return void
     */
    private static function invalidArgument(string $command, string $arg)
    {
        echo "Invalid Argument '{$arg}' for command '{$command}' \n";
        exit;
    }


    /**
     * get the PHP CLI values
     *
     * @param int $argc
     * @param array $argv
     * @return void
     */
    public static function terminalInput($argc, $argv)
    {

        if ($argc == 1) {
            echo "WellCome to Zeero Command line interface (v1.0) \n";
            echo "try < zcli --help  > to see all commands avaible";
            exit;
        } else {

            if (($argv[1] ?? false) == '--help') {
                echo "Avaible Commands: \n";
                self::commandsAvaible(array_keys(self::$cmds));
            }

            if ($argc >= 2) {
                $command = $argv[1];
                $argument = null;
                $value = $argv[2] ?? null;

                if (strpos($command, ':') >= 1) {
                    list($command, $argument) = explode(':', $command);
                }


                // test if the command is avaible
                if (!array_key_exists($command, self::$cmds)) {
                    self::invalidCommand($command);
                }

                // if has a argument test if this is avaible
                if (!is_null($argument)) {
                    if (
                        !in_array($argument, self::$cmds[$command]['arguments'] ?? [])
                        && !array_key_exists($argument, self::$cmds[$command]['arguments'] ?? [])
                    ) {
                        self::invalidArgument($command, $argument);
                    }
                }
            }
        }


        self::$input = [$command, $argument, $value,  array_slice($argv, 3)];

        if (isset(self::$cmds[$command]) && array_key_exists('classname', self::$cmds[$command])) {

            $classname = self::$cmds[$command]['classname'];
            $instance = new $classname;

            if (!$argument and $value == '--help') {
                echo "List of Arguments of < $command > Command\n";
                self::commandsAvaible($classname::$_arguments);
                exit;
            }

            if (is_null($argument))
                $instance->_initialize();
            else
                $instance->$argument();
        } else {
            self::executeCommand($command, $argument, $value, array_slice($argv, 3));
        }

        echo "\n";
        exit;
    }



    /**
     * Execute a ZCLI command
     *
     * @param string $command
     * @param string|null $argument
     * @param string|null $value
     * @param array|null $options
     * @return void
     */
    private static function executeCommand(string $command, $argument, $value, $options)
    {
        $info = self::$cmds[$command];

        if (isset($info['action'])) {
            $info['action']($value ?? '', $options);
        } else {

            if (is_null($argument)) {
                echo $info['help'] . "\n\n";
                echo "Avaible Arguments: \n";
                self::commandsAvaible(array_keys($info['arguments']));
                exit;
            }

            $action = $info['arguments'][$argument]['action'];
            $action($value ?? '', $options);

            if (!is_null($info['extra'])) {
                $str = $info['extra']($argument, $value);

                if (!is_string($str)) {
                    echo "Invalid Extra Information Return \n";
                    exit;
                }

                echo $str . "\n";
            }
        }
    }


    /**
     * Register ZCLI command
     *
     * @param string $name
     * @param string $classname
     * @return void
     */
    public static function registerCommand(string $name, string $classname)
    {
        self::$cmds[$name]['arguments'] = $classname::$_arguments;
        self::$cmds[$name]['classname'] = $classname;
    }

    /**
     * get ZCLI input
     *
     * @return array
     */
    public static function getInput()
    {
        return self::$input;
    }


    /**
     * Start the ZCLI
     *
     * @param int $argc
     * @param array $argv
     * @return void
     */
    public static function start($argc, $argv)
    {
        require_once "Commands/commands.php";
        self::terminalInput($argc, $argv);
    }
}
