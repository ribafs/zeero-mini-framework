<?php

namespace Zeero\Zcli;



/**
 * ColumnTransform
 * 
 * helper to zcli command 'reverse_migration'
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
abstract class ColumnTransform
{

    /**
     * find value in a simple string or comma separated string
     *
     * @param string $str the string
     * @param string $value the value to search
     * @return boolean
     */
    private static function findValueInString(string $str, string $value)
    {
        if ($value == $str) return true;

        if (strpos($str, ',')) {
            $list = explode(',', $str);
            return array_search($value, $list) !== false;
        }

        return false;
    }


    /**
     * append modifier string
     *
     * @param array $info the column information
     * @return string
     */
    private static function appendModifierString(array $info)
    {
        $str = '';

        if (isset($info['UNSIGNED'])) {
            $str .= '->unsigned()';
            unset($info['UNSIGNED']);
        }

        if ($nullable = $info['IS_NULLABLE'] == 'YES') {
            $str .= '->nullable()';
        }

        if (self::findValueInString('PRI', $info['COLUMN_KEY'])) {
            $str .= '->primaryKey()';
        }

        if (self::findValueInString('UNI', $info['COLUMN_KEY'])) {
            $str .= '->unique()';
        }

        if ($info['COLUMN_DEFAULT']) {
            $v = str_replace(['"', "'"], ['', ''], $info['COLUMN_DEFAULT']);

            if ($bool1 = ctype_digit($v)) {
                $info['COLUMN_DEFAULT'] = intval($v);
            }

            if ($bool2 = (strpos($v, '.') and ($isFloat = in_array($info['DATA_TYPE'], ["real", "float", "double"])))) {
                $parts = explode('.', $v);
                $afterDot = str_replace('.', '', $parts[1] / 100);
                $info['COLUMN_DEFAULT'] =  $parts[0] . '.' . $afterDot;
            }

            if ($bool3 = (strpos($v, '.') and !$isFloat)) {
                $info['COLUMN_DEFAULT'] = $v;
            }

            $not = $nullable and $info['COLUMN_DEFAULT'] == 'NULL' and in_array($info['DATA_TYPE'], ["time", "year", "datetime", "date", "timestamp"]);

            if (!$not) {
                if ($bool1)
                    $str .= "->default({$info['COLUMN_DEFAULT']})";
                else
                    $str .= "->default('{$info['COLUMN_DEFAULT']}')";
            }
        }

        return $str;
    }


    /**
     * test the default size of a datatype
     *
     * @param mixed $size
     * @param string $datatype
     * @param mixed $value
     * @return void
     */
    private static function testSizeDefault(&$size, string $datatype, $value)
    {
        $list = [
            ['text' => 500, 'blob' => 50, 'char' => 1, 'varchar' => 255, 'tinytext' => 255],
            ['tinyint' => 2, 'smallint' => 4, 'mediumint' => 8, 'int' => 11, 'bigint' => 25]
        ];

        foreach ($list as $sublist) {
            if (array_key_exists($datatype, $sublist)) {
                $v = $sublist[$datatype] ?? false;
                if ($v != $value) {
                    $size = $value;
                    return;
                }
            }
        }

        $size = null;
    }


    /**
     * Extract Function Args
     *
     * @param string $datatype
     * @param string $type
     * @param array|null $values
     * @param array $info
     * @return void
     */
    private static function ExtractValueFromArgs(string $datatype, string $type, &$values, &$info)
    {
        $list = ["real", "float", "double", "enum", 'set', "tinyint", "int", "smallint", "mediumint", "bigint",  "varchar", "char", "text", "blob", 'tinytext', 'longtext', 'mediumtext'];
        
        // extract function args
        if (in_array($datatype, $list)) {

            if (strpos($type, 'unsigned')) {
                $type = str_replace(' unsigned', '', $type);
                $info['UNSIGNED'] = true;
            }

            $substr = substr(substr($type, strpos($type, '(')), 1, -1);

            if (strpos($substr, ',')) {
                $values = explode(',', $substr);

                foreach ($values as $key => $value) {
                    $v = str_replace(['"', "'"], ['', ''], $value);
                    if (ctype_digit($v)) {
                        $values[$key] = intval($v);
                    }

                    if (strpos($v, '.')) {
                        $values[$key] = $v;
                    }
                }
            }

            $values ??= $substr;
        }
    }


    /**
     * Set The Type size parameter
     *
     * @param string $datatype
     * @param string $name
     * @param array|null $values
     * @param string $statment
     * @return void
     */
    private static function SetTypeSize(string $datatype, string $name, &$values, string &$statment)
    {
        $isDefault = false;

        if ($datatype == 'enum' or $datatype == 'set') {
            $values = '[' . implode(',', $values) . ']';
            $statment .= "{$datatype}('{$name}', {$values})";
        } elseif (in_array($datatype, ["real", "float", "double"])) {
            // float
            $isDefault = $values == [25, 3];

            if ($isDefault)
                $statment = "{$datatype}('{$name}')";
            else
                $statment = "{$datatype}('{$name}', {$values[1]})";
        } else {
            $size = $values;
            // test defaults values
            self::testSizeDefault($size, $datatype, $values);
            // replace *varchar by *string
            if ($datatype == 'varchar') $datatype = 'string';

            if (isset($size))
                $statment = "{$datatype}('{$name}')->size({$values})";
            else
                $statment = "{$datatype}('{$name}')";
        }
    }


    /**
     * Transform Foreign Key
     *
     * @param array $foreign_keys
     * @param array $statments
     * @return void
     */
    private static function transformForeignKey(array $foreign_keys, array &$statments)
    {
        // foreign keys
        foreach ($foreign_keys as $foreign_key) {
            $fk_name = $foreign_key['fk'];
            $for_col = $foreign_key['foreign_col'];
            list($onDelete, $onUpdate) = [$foreign_key['delete_rule'], $foreign_key['update_rule']];
            list($ref_col, $ref_tbl) = [$foreign_key['ref_col'], $foreign_key['ref_table']];

            $fk = "foreign_key('{$fk_name}','{$for_col}','{$ref_tbl}.{$ref_col}'";

            if ($onDelete != 'NO ACTION' and $onUpdate == 'NO ACTION') {
                $fk .= ",'{$onDelete}')";
            } elseif ($onDelete != 'NO ACTION' or $onUpdate != 'NO ACTION') {
                $fk .= ",'{$onDelete}' , '{$onUpdate}')";
            } else {
                $fk .= ')';
            }

            $statments[] = $fk . ';';
        }
    }



    /**
     * get the top tree of a table 
     *
     * top tree refers to tables that the current depends on
     * 
     * @param string $name
     * @param array $references
     * @return array
     */
    private static function getTopTree(string $name, array $references)
    {
        $top_tree = $references[$name] ?? [];

        if (count($top_tree) == 0) return $name;

        $list = [];

        foreach ($top_tree as $key => $v) {
            $qq  = self::getTopTree($v, $references);

            if (!in_array($v, $list)) {
                if (is_array($qq)) {

                    foreach ($qq as $vv) {
                        if (!in_array($vv, $list)) $list[] = $vv;
                    }

                    $list[] = $v;
                } else {
                    if (!in_array($qq, $list))
                        $list[] = $qq;
                }
            }
        }

        return $list;
    }


    /**
     * Get the foreign key order
     *
     * @param string $name
     * @param array $references
     * @return array
     */
    public static function getTableForeignOrder(string $name, array $references)
    {
        $top_tree = $references[$name];
        $names = [];

        foreach ($top_tree as $key) {
            $result = self::getTopTree($key, $references);

            if (!in_array($key, $names)) {
                if (is_array($result)) {
                    $result[] = $key;
                    foreach ($result as $vv) {
                        if (!in_array($vv, $names)) $names[] = $vv;
                    }
                } else {
                    if (!in_array($result, $names))
                        $names[] = $result;
                }
            }
        }

        return $names;
    }


    /**
     * transform a column information to a Table class method
     *
     * @param array $columns_info the columns
     * @return array
     */
    public static function getStatments(array $columns_info, array $foreign_keys)
    {
        $statments = [];

        // columns definitions
        foreach ($columns_info as $info) {
            // informations
            $name = $info['COLUMN_NAME'];
            $datatype = $info['DATA_TYPE'];
            $values = null;
            $type = $info['COLUMN_TYPE'];
            // final string
            $statment = '';

            // check if is uuid field
            if ($uuidField = ($type == "varchar(36)" and self::findValueInString('PRI', $info['COLUMN_KEY']))) {
                if ($name == 'uuid')
                    $statments[] = ($statment .= 'uuid()') . ';';
                else
                    $statments[] = ($statment .= "uuid('{$name}')") . ';';
            }


            if (self::findValueInString('PRI', $info['COLUMN_KEY'])) {
                if (self::findValueInString('auto_increment', $info['EXTRA'])) {
                    if ($name == 'ID')
                        $statments[] = ($statment .= "autoIncrement()") . ';';
                    else
                        $statments[] = ($statment .= "autoIncrement('{$name}')") . ';';

                    $uuidField = true;
                }
            }

            self::ExtractValueFromArgs($datatype, $type, $values, $info);

            self::SetTypeSize($datatype, $name, $values, $statment);

            $statment .= self::appendModifierString($info);

            if (!$uuidField)
                $statments[] = $statment . ';';
        }

        self::transformForeignKey($foreign_keys, $statments);

        return $statments;
    }
}
