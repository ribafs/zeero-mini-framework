<?php

namespace Zeero\Zcli;


/**
 * Migration Changes Controller
 * 
 * parse columns to predefined array format
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class SqlToArrayParser
{

    /**
     * append modifier string
     *
     * @param string $str
     * @param array $info
     * @return void
     */
    private static function appendModifier(string $str, array &$info)
    {
        $_sql = ['null', 'default', 'unsigned'];

        foreach ($_sql as $item) {
            if (stripos($str, $item) >= 0) {

                if ($item == 'null') {
                    if (stripos($str, "not null") === false) {
                        $info['IS_NULLABLE'] = 'YES';
                    } else {
                        $info['IS_NULLABLE'] = 'NO';
                    }
                } else {
                    $_regex = "@{$item}\s([a-zA-Z\)0-9\(\'\`/\\\_\.\/\-\+]+)\s?@i";
                    // capture modifier argument
                    if (preg_match($_regex, $str, $_matches)) {
                        // replace `a` by 'a'
                        $_matches[1] = str_replace('`', "'", $_matches[1]);
                        $_matches[1] = str_replace(['"', "'"], ['', ''], $_matches[1]);

                        if (!preg_match("@^\d+$@", $_matches[1])) {
                            $_matches[1] = "'{$_matches[1]}'";
                        }

                        if ($item == 'default') {

                            if ($info['DATA_TYPE'] == 'set' or $info['DATA_TYPE'] == 'enum') {
                                $_matches[1] = "'{$_matches[1]}'";
                                $info['COLUMN_TYPE'] = str_replace('"', "'", $info['COLUMN_TYPE']);
                            }

                            $info['COLUMN_DEFAULT'] = $_matches[1];
                        }

                        if ($item == 'unique') {
                            $info['COLUMN_KEY'] = 'UNI';
                        }
                    }
                }
            }
        }
    }


    /**
     * parse column definition string
     *
     * @param string $line
     * @param array $info
     * @return void
     */
    private static function parseDefinition(string $line, array &$info)
    {
        $column_regex = "@^\`([a-zA-Z0-9:\_]+)\`\s+?(.+)$@";
        $regex_with_args = '@([a-zA-Z:]+)\s?\((.+)\)\s?(.+)@';
        $regex_without_args = '@([a-zA-Z:]+)\s?(.+)\s?@';

        if (preg_match($column_regex, $line, $matches)) {
            $name = $matches[1];

            if (preg_match($regex_with_args, $matches[2], $_matches)) {
                $datatype_info = [$_matches[1], $_matches[2]];
                $next_str = $_matches[3];
            } elseif (preg_match($regex_without_args, $matches[2], $_matches)) {
                $datatype_info = $_matches[1];
                $next_str = $_matches[2];
            }

            $info['COLUMN_NAME'] = $name;
            $info['IS_NULLABLE'] = 'NO';
            $info['COLUMN_DEFAULT'] = null;
            $info['COLUMN_TYPE'] = null;
            $info['DATA_TYPE'] = null;
            $info['EXTRA'] = '';
            $info['COLUMN_KEY'] = '';
            $info['CHARACTER_MAXIMUM_LENGTH'] = null;

            if (!is_array($datatype_info)) {
                $data_text = strtolower($datatype_info);
                $sizes = ['tinyint' => 2, 'smallint' => 4, 'mediumint' => 8, 'int' => 11, 'bigint' => 25];
                // only integers
                if (array_key_exists($data_text, $sizes)) {
                    $datatype_info = [$data_text, $sizes[$data_text]];
                } else {
                    $info['COLUMN_TYPE'] = $data_text;
                    $info['DATA_TYPE'] = $data_text;
                }
            }

            if (is_array($datatype_info)) {
                $info['DATA_TYPE'] = strtolower($datatype_info[0]);
                $info['COLUMN_TYPE'] = "{$info['DATA_TYPE']}({$datatype_info[1]})";
                // unsigned 
                if (stripos($line, 'unsigned')) {
                    $info['COLUMN_TYPE'] .= " unsigned";
                }
                // maximum length for text type
                if (in_array($info['DATA_TYPE'], ["varchar", "char", "text", "blob", 'tinytext', 'longtext'])) {
                    $info['CHARACTER_MAXIMUM_LENGTH'] = $datatype_info[1];
                }

                if (in_array($info['DATA_TYPE'], ["enum", "set"])) {
                    if (preg_match("@\((.+)\)@", $info['COLUMN_TYPE'], $matches)) {
                        $_list = explode(',', $matches[1]);
                        $lengths = [];
                        foreach ($_list as $i) {
                            $i = trim(str_replace(['"', "'"], ['', ''], $i));
                            $lengths[] = strlen($i);
                        }

                        $info['CHARACTER_MAXIMUM_LENGTH'] = max($lengths);
                    }
                }

                if ($info['DATA_TYPE'] == 'timestamp') {
                    $info["COLUMN_DEFAULT"] = "current_timestamp(6)";
                    $info["EXTRA"] = "on update current_timestamp(6)";
                }
            }

            $next_str = strtoupper(trim($next_str));

            if (stripos($next_str, 'auto_increment')) {
                $info['EXTRA'] = 'auto_increment';
            }

            self::appendModifier($next_str, $info);
        }
    }


    /**
     * extract and change key
     *
     * @param string $line
     * @param array $columns
     * @param array $informations
     * @param string $value
     * @return integer
     */
    private static function ExtractAndChangeKey(string $line, array $columns, array &$informations, string $value)
    {
        preg_match("@\(`?([a-z0-9:_]+)`?\)@i", $line, $matches);
        $pk_info_index = array_search($matches[1], $columns);
        $informations[$pk_info_index]['COLUMN_KEY'] = $value;
        return $pk_info_index;
    }


    /**
     * extract and append constraint information
     *
     * @param string $line
     * @return array|void
     */
    private static function ExtractConstraintInfo(string $line)
    {
        $line = str_replace(["`", "'", '"'], ["", "", ""], $line);

        $regex_key_name = "@constraint\s([a-zA-Z0-9\-\_]+)\s+@i";
        if (preg_match($regex_key_name, $line, $_matches)) {
            $fk_name = $_matches[1];
        }

        // capture the referenced table and her column
        $regex_reference = "@\s?FOREIGN\sKEY\s\(([a-zA-Z0-9\-\_]+)\)\s+REFERENCES\s+?([a-zA-Z0-9\-\_\.]+)\s+?\(([a-zA-Z0-9\-\_]+)\)@i";

        if (preg_match($regex_reference, $line, $_matches)) {
            list($column, $table, $table_column) = array_slice($_matches, 1);

            $parts = array_slice(explode(" ON ", $line), 1);
            $parts_info = [];

            foreach ($parts as $part) {
                $white_space_pos = strpos($part, " ");
                $item = substr($part, 0, $white_space_pos);
                $value = trim(substr($part, $white_space_pos));

                $parts_info[strtolower($item)] = $value;
            }

            $parts_info['delete'] ??= 'NO ACTION';
            $parts_info['update'] ??= 'NO ACTION';
        }

        if (isset($fk_name) and isset($table)) {
            return [
                "fk" => $fk_name,
                "table_name" => "",
                "delete_rule" => $parts_info['delete'],
                "update_rule" => $parts_info['update'],
                "foreign_col" => $column,
                "ref_col" => $table_column,
                "ref_table" => $table
            ];
        }
    }


    /**
     * parse columns to predefined array format
     * 
     * this method returns information about columns and table constraints
     *
     * @param string $create_str
     * @return array
     */
    public static function getParsedColumns(string $create_str)
    {
        // the index of the first (
        $index = strpos($create_str, '(');
        // the index of ) ENGINE
        $index_engine = strrpos($create_str, ') ENGINE');

        $engine_substr = substr($create_str, $index_engine);
        $text = str_replace($engine_substr, '', substr($create_str, $index + 1));

        $lines = explode(', ', $text);

        $columns = [];
        $informations = [];
        $fks = [];
        $uniques = [];

        foreach ($lines as $line) {
            // remove whitespaces
            $line = trim($line);
            $info = [];

            // test if is column
            if (strpos($line, '`') === 0) {
                self::parseDefinition($line, $info);
                $columns[] = $info['COLUMN_NAME'];
                $informations[] = $info;
            } else {
                // primary key column
                if (stripos($line, 'PRIMARY KEY') === 0) {
                    self::ExtractAndChangeKey($line, $columns, $informations, 'PRI');
                }

                // unique key column
                if (stripos($line, 'UNIQUE') === 0) {
                    $index = self::ExtractAndChangeKey($line, $columns, $informations, 'UNI');
                    if (preg_match("@\s`?([a-z0-9:_]+)`?\s@i", $line, $matches)) {
                        $uniques[$columns[$index]] = $matches[1];
                    }
                }

                // constraint
                if (stripos($line, 'CONSTRAINT') === 0 and stripos($line, 'REFERENCES')) {
                    $result = self::ExtractConstraintInfo($line);
                    if (is_array($result)) {
                        self::ExtractAndChangeKey(explode('REFERENCES', $line)[0], $columns, $informations, 'MUL');
                        $fks[] = $result;
                    }
                }
            }
        }

        return [$informations, $fks, $columns, $uniques];
    }
}
