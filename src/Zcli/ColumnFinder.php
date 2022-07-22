<?php

namespace Zeero\Zcli;


/**
 * Column Finder
 * 
 * used to find columns definition in migration class
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
abstract class ColumnFinder
{

    /**
     * Capture Foreign Key
     *
     * @param string $value
     * @param array $fks
     * @return void
     */
    private static function CaptureForeignKey(string $value, array &$fks)
    {
        $fk_regex  = '@foreign_key\((.+)\)@';

        // * Capture Foreign Keys
        if (preg_match($fk_regex,  $value, $m)) {
            $info = explode(',', $m[1]);

            $_info = explode('.', $info[2]);

            if (count($_info) == 3) {
                $table = $_info[1];
                $column = $_info[2];
            } else {
                $table = $_info[0];
                $column = $_info[1];
            }

            list($column, $table) = str_replace(['"', "'"], ['', ''], [$column, $table]);

            $table = trim($table);
            $column = trim($column);
            $fk = "{$info[1]} => ['table' => '{$table}', 'column' => '{$column}'] ";
            $fks[] = str_replace("'", '"', trim($fk));
        }
    }


    /**
     * Capture Functions
     *
     * @param string $value
     * @param array $columns
     * @param string $pk
     * @return true|void
     */
    private static function CaptureFunctions(string $value, array &$columns, string &$pk)
    {
        $regexp = "@([a-zA-Z_]+)\(\'([a-zA-Z_\.]+)\'\)@";
        $regexp2 = "@([a-zA-Z_]+)\(@";

        // * Capture Functions With Arguments
        if (preg_match($regexp, $value, $matches)) {

            if (in_array($matches[1], ['foreign_key', 'engine', 'charset', 'CreateSchemaIfNotExists'])) return true;

            if ($matches[1] == 'autoIncrement' or strpos($value, '->primaryKey') or $matches[1] == 'uuid') {
                $pk = "'" . $matches[2] . "'";
            }

            if (count($columns))
                $matches[2] = "'" . $matches[2] . "'";

            $columns[] = $matches[2];
        } else {

            //  * Capture Functions Without Arguments

            if (preg_match($regexp2, $value, $m)) {

                // timesTamps function
                if ($m[1] == 'timesTamps') {
                    $columns[] = "'created_at'";
                    $columns[] = "'updated_at'";
                } elseif ($m[1] == 'uuid') {
                    //uuid function
                    $columns[] = "'uuid'";
                    $pk = "'uuid'";
                }
            }
        }
    }


    /**
     * find Columns
     *
     * @param string $filename
     * @return array
     */
    public static function find(string $filename)
    {
        $data = file($filename);

        $columns = [];
        $pk = '';
        $fks = [];

        for ($i = 0; $i < count($data); $i++) {

            $value = str_replace('"', "'", trim($data[$i]));

            /**
             * capture model *table attribute sugestion
             */
            if (strpos(trim($value), "//") !== false) {
                $columns['table'] = trim(explode(':', $value)[1] ?? '');
            }

            if (strpos($value, '}') === 0) break;

            self::CaptureForeignKey($value, $fks);

            $value = str_replace(',', ')', $value);

            if (self::CaptureFunctions($value, $columns, $pk)) continue;
        }

        if (strpos($filename, end($columns)) === 0) array_pop($columns);

        if (strpos($columns[0], '.')) {
            $array = explode('.', $columns[0]);
            $columns[0] = end($array);

            if (strpos($columns[0], '_')) {
                $array = explode('_', $columns[0]);
                $array = array_map('ucfirst', $array);
                $columns[0] = implode('', $array);
            }
        }

        $columns[0] = ucfirst($columns[0]);

        $columns['fk'] = $fks;
        $columns['primary'] = $pk;

        return $columns;
    }
}
