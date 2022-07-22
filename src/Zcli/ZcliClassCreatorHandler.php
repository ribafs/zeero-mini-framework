<?php

namespace Zeero\Zcli;


/**
 * Class Creator Handler
 * 
 * this class handle in file creation
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
final class ZcliClassCreatorHandler
{

    /**
     * Insert empty lines in current stream
     *
     * @param resource $stream
     * @param integer $n number of empty lines
     * @return void
     */
    private function insertEmptyLines($stream, int $n = 1)
    {
        for ($i = 0; $i < $n; $i++) {
            fwrite($stream, PHP_EOL);
        }
    }



    /**
     * Insert lines in current stream
     *
     * @param resource $stream
     * @param string $line
     * @return void
     */
    private function insertLine($stream, string $line)
    {
        fwrite($stream, $line . PHP_EOL);
    }


    /**
     * Insert Method in current stream
     *
     * @param resource $stream
     * @param array $methods
     * @param array|null $bodys
     * @return void
     */
    private function insertMethods($stream, array $methods, array $bodys = null)
    {
        foreach ($methods as $index => $value) {
            $access = 'public';

            if (is_array($value)) {
                $access = $value[0];
                $value = $value[1];
            }

            $line = "\t{$access} function {$value} ";
            $this->insertLine($stream, $line);
            $this->insertLine($stream, "\t{");

            if (isset($bodys[$index])) {
                foreach ($bodys[$index] as $statment) {
                    $this->insertLine($stream, $statment);
                }
            }

            $this->insertLine($stream, "\t}");
            $this->insertEmptyLines($stream);
        }
    }


    /**
     * create a class file
     *
     * @param string $filename
     * @param array $lines
     * @param array $newLines
     * @param array|null $methods
     * @param array|null $bodys
     * @return void
     */
    private function makeClass(string $filename, array $lines, array $newLines, array $methods = null, array $bodys = null)
    {
        $stream = fopen($filename, 'w+');

        foreach ($lines as $index => $line) {

            if ($line == '}') {
                if ($methods) {
                    $this->insertEmptyLines($stream);
                    $this->insertMethods($stream, $methods, $bodys);
                }
            }

            $this->insertLine($stream, $line);

            if (isset($newLines[$index]))
                $this->insertEmptyLines($stream);
        }
    }


    /**
     * create a controller class
     *
     * @param string $name
     * @param boolean|null $resource as REST resource
     * @return void
     */
    public function makeController(string $name, bool $resource = null)
    {
        if (!file_exists("App/Controllers")) mkdir("App/Controllers");

        $filename = "App/Controllers/{$name}.php";

        $lines = [
            '<?php',
            'namespace App\Controllers;',
            "class {$name}", '{', '}'
        ];

        $new_lines  = [1, 1];

        if ($resource)
            $methods = ['index()', 'store()', 'create()', 'show($id)', 'edit($id)', 'update($id)', 'destroy($id)'];
        else {
            $methods = ['Index()'];
        }

        $this->makeClass($filename, $lines, $new_lines, $methods ?? null);
    }


    /**
     * create a model class
     *
     * @param string $name
     * @param string|null $attributes
     * @param string|null $primary_key
     * @param string|null $foreign_keys
     * @return void
     */
    public function makeModel(
        string $name,
        string $attributes = null,
        string $primary_key = null,
        string $foreign_keys = null,
        string $table = null
    ) {
        $attributes ??= '[]';

        if ($primary_key == '') $primary_key = null;

        $primary_key ??= '""';

        $name = ucfirst($name);

        if (!file_exists("App/Models")) mkdir("App/Models");

        $filename = "App/Models/{$name}.php";

        $lines = [
            '<?php',
            'namespace App\Models;',
            'use Zeero\Database\ORM\Model;',
            "class {$name} extends Model",
            '{'
        ];

        if (isset($table)) {
            $lines[] = "\tprotected \$table = '{$table}';";
        }

        $lines[] = "\tprotected \$attributes = {$attributes};";
        $lines[] = "\tprotected \$primary_key = {$primary_key}; ";
        $lines[] = '}';

        if ($foreign_keys) {
            $start = isset($table) ? 8 : 7;

            $lines[$start] = "\tprotected \$foreign_keys = [ ";
            $fks_items = explode('+', $foreign_keys);
            $count = $start;

            foreach ($fks_items as $fk) {
                $lines[$count += 1] = "\t\t" . $fk . ',';

                // remove the comma
                if (end($fks_items) == $fk) {
                    $lines[$count] = substr(
                        $lines[$count],
                        0,
                        strlen($lines[$count]) - 1
                    );
                }
            }

            // close the array
            $lines[$count += 1] = "\t];";
            // close the class
            $lines[$count + 1] = "}";
        }

        $new_lines  = [1, 1, 1];
        $new_lines[4] = 1;

        $this->makeClass($filename, $lines, $new_lines);
    }



    /**
     * create a migration class
     *
     * @param string $name
     * @return void
     */
    public function makeMigration(string $name, string $option = null, array $body_contents = null)
    {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);

            if (strpos(end($parts), '_')) {
                $table = end($parts);
            }

            $parts = array_map('ucfirst', $parts);
            $class = implode('', $parts);
        }

        $class ??= ucfirst(strtolower($name));

        // filename
        $f_name = strtolower($class);

        $classname =  date('Y_m_d_His') . "_create_{$f_name}_table";

        if (!file_exists("App/DataBase/Migrations")) mkdir("App/DataBase/Migrations");

        $filename = "App/DataBase/Migrations/{$classname}.php";

        if (ZcliMigrationHandler::MigrationExists($f_name)) {
            echo " => Migration for table '{$f_name}' already exists\n";
            return false;
        }

        $lines = [
            '<?php',
            'namespace App\DataBase\Migrations;',
            'use Zeero\Database\Migration;',
            'use Zeero\Database\QueryBuilder\SchemaBuilder\Table;',
            'use Zeero\facades\Schema;',
            'class Create' . $class . 'Table extends Migration',
            '{',
            '}',
        ];

        $new_lines  = [1, 1];
        $new_lines[4] = 1;
        $methods = ['up()', 'down()'];

        $alter_body = [
            "\t\tSchema::alter('{$name}', function (Alter \$table) {", "\t\t});"
        ];

        $bodys = [
            [
                "\t\tSchema::create('{$name}', function (Table \$table) {",
                "\t\t\t\$table->autoIncrement('id');", "\t\t});"
            ],
            ["\t\tSchema::dropIfExists('{$name}');"]
        ];

        if ($body_contents) {
            $bodys[0] = $body_contents;
        }

        if ($option == "--alter") {
            $bodys[0] = $alter_body;
            $lines[3] = 'use Zeero\Database\QueryBuilder\SchemaBuilder\Alter;';
        }

        if (isset($table)) {
            $lastIndex = count($bodys[0]) - 1;
            $tmp = $bodys[0][$lastIndex];
            $bodys[0][$lastIndex] = "\t\t\t//model table attribute: " . $table;
            $bodys[0][] = $tmp;
        }

        $this->makeClass($filename, $lines, $new_lines, $methods, $bodys);
    }



    /**
     * Create a form request class
     *
     * @param string $name
     * @return void
     */
    public function makeRequestClass(string $name)
    {
        $name = ucfirst($name);

        if (!stripos($name, 'request')) {
            $name .= 'Request';
        }

        if (!file_exists("App/Controllers/Request")) mkdir("App/Controllers/Request");

        $filename = "App/Controllers/Requests/{$name}.php";

        $lines = [
            '<?php',
            'namespace App\Controllers\Requests;',
            'use Zeero\Core\Validator\Form;',
            "class {$name} extends Form", '{',
            "\tprotected \$redirectTo = \"\";",
            '}',
        ];

        $new_lines  = [1, 1, 4];
        $methods = ['rules()', 'messages()'];
        $bodys = [["\t\treturn [];"], ["\t\treturn [];"]];

        $this->makeClass($filename, $lines, $new_lines, $methods, $bodys);
    }



    /**
     * Create a Seed class from a model
     *
     * @param string $name
     * @param array $fields
     * @param array $data
     * @return void
     */
    public function makeSeed(string $name, array $fields, array $data)
    {
        $model = $name;

        if (!stripos($name, 'seed')) {
            $name .= 'Seed';
        }

        if (!file_exists("App/DataBase/Seeds")) mkdir("App/DataBase/Seeds");

        $filename = "App/DataBase/Seeds/{$name}.php";

        if (!file_exists("App/DataBase/Seeds")) {
            mkdir("App/DataBase/Seeds");
        }

        $lines = [
            '<?php',
            'namespace App\DataBase\Seeds;',
            'use Zeero\DataBase\Seed;',
            "class {$name} extends Seed", '{',
            "\tprotected \$model = '{$model}';",
            '}',
        ];

        $new_lines  = [1, 1, 4];
        $methods = [['protected', 'fields()'], ['protected', 'data()']];

        // FIELDS
        if (($hiddenPos = array_search('hidden', $fields)) >= 0 and $hiddenPos !== false) {
            unset($fields[$hiddenPos]);
        }

        if (($onlyPos = array_search('only', $fields)) >= 0 and $onlyPos !== false) {
            unset($fields[$onlyPos]);
        }

        $fields = array_map(function ($i) use ($data) {
            if (!is_object($data[0]->$i))
                return "'{$i}'";
        }, $fields);

        array_pop($fields);
        // fields to string
        $toString = implode(', ', $fields);

        // remove the last comma if is in end
        if (substr(trim($toString), -1) == ',') {
            $toString = substr($toString, 0, strlen($toString) - 2);
        }

        $fields = "\t\treturn [" . $toString . "];";

        // VALUES
        $values = ["\t\treturn ["];

        foreach ($data as $obj) {
            $obj_values = array_values((array) $obj);

            // check if the *hidden values is set if true unset
            if ($hiddenPos >= 0 and $hiddenPos !== false) unset($obj_values[$hiddenPos]);
            // check if the *only values is set if true unset
            if ($onlyPos >= 0 and $onlyPos !== false) unset($obj_values[$onlyPos]);

            $values[] = Util::DataToArray($obj_values);
        }

        $values[] = "\t\t];";

        $bodys = [[$fields], $values];

        $this->makeClass($filename, $lines, $new_lines, $methods, $bodys);
    }



    /**
     * Create a zcli command class
     *
     * @param string $name
     * @param string|null $option
     * @return void
     */
    public function makeCommandClass(string $name, $option = null)
    {

        if (!stripos($name, 'command')) {
            $name .= 'Command';
        }

        if (!file_exists("App/Controllers/Console")) mkdir("App/Controllers/Console");

        $namespace = 'App\Controllers\Console';

        if ($option != '--dev')
            $filename = "App/Controllers/Console/{$name}.php";
        else {
            $namespace = 'Zeero\Zcli\Commands';
            $filename = FRAMEWORK_DIR . 'Zcli/Commands/' . $name . '.php';
        }

        $lines = [
            '<?php',
            "namespace {$namespace};",
            'use Zeero\Zcli\Command;',
            "class {$name} extends Command", '{',
            "\tpublic static \$_arguments = [];",
            '}',
        ];

        $new_lines  = [1, 1, 4];
        $methods = ['__construct()', '_initialize()'];
        $bodys = [["\t\tparent::__construct();"], ["\t\techo 'hello world';"]];

        $this->makeClass($filename, $lines, $new_lines, $methods, $bodys);
    }
}
