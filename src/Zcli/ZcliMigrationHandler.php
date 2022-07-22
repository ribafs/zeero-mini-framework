<?php

namespace Zeero\Zcli;

use Zeero\Kernel;


/**
 * Zcli Migration Handler
 * 
 * this class handle in migration process
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
final class ZcliMigrationHandler
{

    /**
     * Generate a Model class
     *
     * @param string $classname
     * @return string the name of the model created
     */
    private function createModelForMigrationClass(string $classname)
    {
        $filename = str_replace("\\", '/', $classname . '.php');
        $info = ColumnFinder::find($filename);
        // 
        $pk = null;
        $foreign_key =  null;
        $table = null;

        if (isset($info['primary'])) {
            $pk = $info['primary'];
            unset($info['primary']);
        }

        if (isset($info['table'])) {
            $table = $info['table'];
            unset($info['table']);
        }

        if (isset($info['fk'])) {
            $foreign_key = implode('+', $info['fk']);
            unset($info['fk']);
        }

        $attributes = "[" . implode(', ', array_slice($info, 1)) . "]";

        $handler = new ZcliClassCreatorHandler;

        $handler->makeModel(
            $info[0],
            $attributes,
            $pk,
            $foreign_key,
            $table
        );

        echo "--> model created\n";
        return $info[0];
    }


    /**
     * return a boolean indicating the existence of a migration class file
     *
     * @param string $table
     * @return bool
     */
    public static function MigrationExists(string $table)
    {
        $dir = 'App/DataBase/Migrations';

        if (!file_exists($dir)) mkdir($dir);

        $handler = opendir($dir);

        if (strpos($table, '.')) {
            $table = str_replace('.', '', $table);
        }

        while ($file = readdir($handler)) {
            if ($file != '..' and $file != '.') {
                $classname = str_replace('.php', '', $file);
                $_class = strtolower(ucfirst(str_replace(
                    ['create_', '_table'],
                    ['', ''],
                    substr(
                        $classname,
                        18
                    )
                )));

                if ($_class == $table) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Run a local seed
     *
     * @param string $modelname
     * @return void
     */
    public static function RunSeed(string $modelname)
    {
        $seedname = $modelname . 'Seed';

        $seed = "App/DataBase/Seeds/" . $seedname;

        if (!file_exists($seed . '.php')) {
            echo "--X Seed for Model '{$modelname}' Not Exists \n";
            return;
        }

        $seedclass = str_replace('/', '\\', $seed);
        $seedInstance = new $seedclass;

        echo "--> Running seed {$modelname} \n";
        $n = $seedInstance->run();
        echo "--> {$n} records inserted \n";
    }



    /**
     * Migrate 
     *
     * @param string $classname
     * @param string|null $option
     * @param string|null $option2
     * @param string|null $option3
     * @return void
     */
    public function migrate(string $classname, string $option = null, string $option2 = null, string $option3 = null)
    {
        Kernel::DataBaseBoot();
        $dir = 'App/DataBase/Migrations';

        if (!file_exists($dir)) mkdir($dir);

        require_once _ROOT_ . DS . 'App' . DS . 'DataBase' . DS . 'Migrations' . DS . $classname . '.php';

        $_class = $c = ucfirst(str_replace(
            ['create_', '_table'],
            ['', ''],
            substr(
                $classname,
                18
            )
        ));

        $_class = "Create{$_class}Table";

        $class = "App\DataBase\Migrations\\" . $_class;
        $instance = new $class;

        if ($option == '--up') {

            echo "--> migrate: {$c} \n";

            $instance->up();

            if ($option2 == '--m') {
                $modelname = $this->createModelForMigrationClass("App\DataBase\Migrations\\" . $classname);

                if ($option3 == '--s') {
                    $this->RunSeed(ucfirst($modelname));
                }
            }

            echo "\n";

            // 
        } elseif ($option == '--down') {
            echo "--> migrating: {$c} \n";

            $instance->down();

        } else {
            exit('You must provide the option ( --up or --down )');
        }
    }


    /**
     * Migrate all migration classes in Migration Folder
     *
     * @param string|null $option
     * @param string|null $createModel
     * @param string|null $runSeed
     * @return void
     */
    public function migrateAll(string $option = null, string $createModel = null, $runSeed = null)
    {
        $option ??= '--up';
        $dir = 'App/DataBase/Migrations';

        if (!file_exists($dir)) mkdir($dir);

        $handler = opendir($dir);

        while ($file = readdir($handler)) {
            if ($file != '..' and $file != '.') {
                $classname = str_replace('.php', '', $file);
                // migrate the current file
                $this->migrate(
                    $classname,
                    $option,
                    $createModel,
                    $runSeed
                );
            }
        }
    }
}
