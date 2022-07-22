<?php

namespace Zeero\Zcli\Commands;

use Zeero\Kernel;
use Zeero\Zcli\Command;
use Zeero\Zcli\ZcliClassCreatorHandler;


/**
 * Create Migrations classes from sql file
 * 
 * this class reads and generate migrations from a sql file
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
class sqlToMigrationCommand extends Command
{
	public static $_arguments = [];
	private static $_schema = null;

	public function __construct()
	{
		parent::__construct();
		Kernel::BootFrameworkFunctions();
	}


	/**
	 * fix multi line
	 *
	 * @param integer $start_index
	 * @param integer $end_index
	 * @param array $lines
	 * @return void
	 */
	private function toSingleLine(int $start_index, int $end_index, array &$lines)
	{
		$line = '';
		// 
		for ($j = $start_index; $j <= $end_index; $j++) {
			$lines[$j] = str_replace(["\r", "\n", "  "], ["", "", " "], $lines[$j]);
			$line .= $lines[$j];
			if ($j != $start_index)
				unset($lines[$j]);
		}

		// update the line
		$line = substr($line, 0, -1);
		$lines[$start_index] = $line . "\n";
	}


	/**
	 * prepare the sql file before extraction
	 *
	 * this method fix multiline foreign key definitions and insert just a single line
	 * 
	 * @param string $filename
	 * @return array the lines to extraction
	 */
	private function getPreparedSqlFile(string $filename)
	{
		$lines = file($filename);
		$start_index = 0;
		$items_count = count($lines);

		for ($i = 0; $i < $items_count; $i++) {
			$line = trim($lines[$i]);

			if (stripos($line, 'constraint') === 0) {
				$start_index = $i;
			}

			if ($start_index) {
				if (stripos($line, 'ON') === 0) {
					$end = substr($line, -1, 1);
					if ($end == ',' or $end == ')') {
						$this->toSingleLine($start_index, $i, $lines);
						$start_index = 0;
					}
				}
			}
		}

		return $lines;
	}


	/**
	 * append a modifier
	 *
	 * @param string $str
	 * @return string
	 */
	private function appendModifier(string $str)
	{
		$_sql = ['null', 'default', 'unsigned', 'after', 'before', 'unique'];
		$_modifier_string = '';

		foreach ($_sql as $item) {
			if (stripos($str, $item) >= 0) {
				if ($item == 'null') {
					if (stripos($str, "not null") === false)
						$_modifier_string .= "->nullable()";
				} else {
					$_regex = "@{$item}\s([a-zA-Z0-9\'\`/\\\_\.\/\-\+]+)\s?@i";
					// capture modifier argument
					if (preg_match($_regex, $str, $_matches)) {
						// replace `a` by 'a'
						$_matches[1] = str_replace('`', "'", $_matches[1]);
						$_matches[1] = str_replace(['"', "'"], ['', ''], $_matches[1]);

						if (!preg_match("@^\d+$@", $_matches[1])) {
							$_matches[1] = "'{$_matches[1]}'";
						}

						if ($item == "unsigned")
							$_modifier_string .= "->{$item}()";
						else
							$_modifier_string .= "->{$item}({$_matches[1]})";
					}
				}
			}
		}

		return $_modifier_string . ';';
	}


	/**
	 * append datatype function
	 *
	 * @param string $column_name
	 * @param array|string $datatype_info
	 * @param string|null $other_str
	 * @return string
	 */
	private function appendDataTypeFunction(string $column_name, $datatype_info, string $other_str = null)
	{
		$column_name = "'{$column_name}'";
		$datatype = is_array($datatype_info) ? $datatype_info[0] : $datatype_info;
		// the datatype function argument
		$arg = is_array($datatype_info) ? $datatype_info[1] : '';
		// the final string
		$datatype_string = '';

		// convert to lower case
		$datatype = strtolower($datatype);
		// replace the datatype *varchar by *string function
		if ($datatype == 'varchar') $datatype = 'string';

		if (is_array($datatype_info)) {

			$arg = str_replace(['"', "'"], ['', ''], $arg);

			if (!preg_match("@^\d+$@", $arg)) {
				$arg = "'{$arg}'";
			}

			// check if is comma separated values
			if (strpos($datatype_info[1], ',')) {
				// this behavior is present in the *enum and *set datatype
				if (in_array($datatype, ['enum', 'set']))
					$arg = '[' . $datatype_info[1] . ']';
			}

			if (in_array($datatype, ['double', 'real', 'float', 'enum', 'set']))
				$datatype_string = "{$datatype}({$column_name}, {$arg})";
			else
				$datatype_string = "{$datatype}({$column_name})->size({$arg})";
		} else {
			$datatype_string = "{$datatype}({$column_name})";
		}

		// check if is an auto_increment column
		if (stripos($other_str, 'auto_increment') > 0) $this->_auto_increment = true;

		$_modifier_string = $this->appendModifier($other_str);
		return $datatype_string . $_modifier_string;
	}


	/**
	 * request creation of migrations class
	 *
	 * @param array $tables_definitions
	 * @param array $table_info
	 * @return integer
	 */
	public static function createMigrationsClass(array $tables_definitions, array $table_info = [])
	{
		$handler = new ZcliClassCreatorHandler;
		$total = count($tables_definitions);
		$i = 1;
		$count = 0;

		foreach ($tables_definitions as $name => $info) {
			$values = [];

			$values = array_map(function ($item) {
				return "\t\t\t\$table->" . $item;
			}, array_merge(array_values($info), $table_info));

			$body = [];

			if (isset(self::$_schema)) {
				$schema = self::$_schema;
				$body[] = "\t\tSchema::CreateSchemaIfNotExists({$schema});";
				$body[] = " ";
				self::$_schema = null;
			}

			$body[] = "\t\tSchema::create('{$name}', function (Table \$table) {";
			$body = array_merge($body, $values);
			$body[] = "\t\t});";

			$bool = $handler->makeMigration($name, null, $body);

			if ($bool !== false) {
				$count += 1;
			}

			echo " --> status: {$i} / {$total}\n";
			$i += 1;
			sleep(1);
		}

		return $count;
	}



	/**
	 * Extract Columns
	 *
	 * @param string $line
	 * @param string|null $current_table
	 * @param array $columns
	 * @return void
	 */
	private function extractColumns(string $line, $current_table, array &$columns)
	{
		$column_regex = "@^\`([a-zA-Z0-9\_]+)\`\s+?(.+)$@";

		// extract columns
		if (preg_match($column_regex, $line, $matches)) {
			$regex_with_args = '@([a-zA-Z]+)\s?\((.+)\)\s?(.+)@';
			$regex_without_args = '@([a-zA-Z]+)\s?(.+)\s?@';
			$column_name = $matches[1];

			if (preg_match($regex_with_args, $matches[2], $_matches)) {
				$datatype_info = [$_matches[1], $_matches[2]];
				$next_str = $_matches[3];
			} elseif (preg_match($regex_without_args, $matches[2], $_matches)) {
				$datatype_info = $_matches[1];
				$next_str = $_matches[2];
			}

			if (isset($datatype_info)) {

				$column_string = $this->appendDataTypeFunction(
					$column_name,
					$datatype_info,
					$next_str
				);

				$columns[$current_table][$column_name] = $column_string;
			}
		}
	}


	/**
	 * Extract Primary Key
	 *
	 * @param string $line
	 * @param string|null $current_table
	 * @param array $columns
	 * @return void
	 */
	private function extractPrimaryKey(string $line, $current_table, array &$columns)
	{
		// extract primary keys
		if (stripos($line, 'primary key') === 0) {
			$line = str_replace(["`", "'", '"'], ["", "", ""], $line);

			if (preg_match('@\((.+)\)@', $line, $_matches)) {
				$primary_key = $_matches[1] ?? '';

				if (substr($primary_key, -1) == ')') {
					$primary_key = substr($primary_key, 0, -1);
				}

				if (isset($this->_auto_increment)) {
					$columns[$current_table][$primary_key] = "autoIncrement('{$primary_key}');";
					unset($this->_auto_increment);
				} else {

					// check if the current field is valid for Schema method: uuid
					// varchar(36) and primary key is required
					if ("string('{$primary_key}')->size(36);" == $columns[$current_table][$primary_key]) {
						$columns[$current_table][$primary_key] = "uuid('{$primary_key}');";
					} else {
						$columns[$current_table][$primary_key] = substr($columns[$current_table][$primary_key], 0, -1);
						$columns[$current_table][$primary_key] .= "->primaryKey();";
					}
				}
			}
		}
	}


	/**
	 * Extract Foreign Keys
	 *
	 * @param string $line
	 * @param string|null $current_table
	 * @param array $columns
	 * @param array $info
	 * @return void
	 */
	private function extractForeignKeys(string $line, $current_table, array &$info)
	{
		// extract foreign key
		if (
			stripos($line, 'foreign key') >= 0
			and stripos($line, 'references') > 0
			and $current_table
		) {
			$line = str_replace(["`", "'", '"'], ["", "", ""], $line);
			$fk_name = null;
			$on_delete = null;
			$on_update = null;

			// try to capture foreign key name
			if (stripos($line, "constraint") >= 0) {
				$regex_key_name = "@constraint\s([a-zA-Z0-9\-\_]+)\s+@i";
				if (preg_match($regex_key_name, $line, $_matches)) {
					$fk_name = $_matches[1];
				}
			}

			// capture the referenced table and her column
			$regex_reference = "@\s?FOREIGN\sKEY\s\(([a-zA-Z0-9\-\_]+)\)\s+REFERENCES\s+?([a-zA-Z0-9\-\_\.]+)\s+?\(([a-zA-Z0-9\-\_]+)\)@i";

			if (preg_match($regex_reference, $line, $_matches)) {
				list($column, $table, $table_column) = array_slice($_matches, 1);
				$fk_name ??= '';
				if (strpos($table, '.')) $table = explode('.', $table)[1];
				$fk_string = "foreign_key('{$fk_name}', '{$column}', '{$table}.{$table_column}'";
			}

			if (isset($fk_string)) {
				$parts = array_slice(explode(" ON ", $line), 1);

				foreach ($parts as $part) {
					$white_space_pos = strpos($part, " ");
					$item = substr($part, 0, $white_space_pos);
					$value = trim(substr($part, $white_space_pos));

					if (strpos($value, ';')) {
						$value = substr($value, 0, -1);
					}

					if (strpos($value, ')')) {
						$value = substr($value, 0, -1);
					}

					if (strtolower($item) == 'delete') $on_delete  = $value;
					if (strtolower($item) == 'update') $on_update  = $value;
				}

				$on_delete ??= 'NO ACTION';
				$on_update ??= 'NO ACTION';

				if (!($on_delete == 'NO ACTION' and $on_update == 'NO ACTION'))
					$fk_string .= ", '{$on_delete}', '{$on_update}');";
				else
					$fk_string .= ');';

				$info[] = $fk_string;
			}
		}
	}


	/**
	 * Extract Unique Index
	 *
	 * @param string $line
	 * @param string|null $current_table
	 * @param array $columns
	 * @return void
	 */
	private function extractUniqueIndexes(string $line, $current_table, array &$columns)
	{
		// extract unique index
		if (stripos($line, 'unique index') === 0 and $current_table) {

			if (preg_match('@\`(.+)\`@', $line, $_match_column)) {
				$result_array = explode(' ', $_match_column[1]);
				$index_name = $result_array[0] = substr($result_array[0], 0, -1);
				$column_name = $result_array[1] = substr($result_array[1], 2);

				$columns[$current_table][$column_name] = substr($columns[$current_table][$column_name], 0, -1);
				/**
				 * the unique index will be ignored if is equal with column name
				 */
				if (array_key_exists($column_name, $columns[$current_table])) $index_name = '';

				$columns[$current_table][$column_name] .= "->unique({$index_name});";
			}
		}
	}


	public function _initialize()
	{

		if (!$this->input_value) {
			echo "Invalid filename";
			exit;
		}

		if (strpos($this->input_value, '.sql') === false) {
			exit('The file must contains the .sql extension');
		}

		/**
		 * 
		 * Read the .sql file and test regular expressions
		 * 
		 */
		$file = $this->input_value;
		$filename = _ROOT_ . DS . 'App' . DS . 'public' . DS .  $file;

		if (!file_exists($filename)) {
			exit('File Not Exists - path:' . $filename);
		}

		$lines = $this->getPreparedSqlFile($filename);

		$current_table = null;
		$columns = [];
		$tables = [];
		$info = [];

		foreach ($lines as $line) {
			$line = trim($line);

			// find the current table
			if (stripos($line, "create table") === 0) {
				$text = str_replace('`', '', $line);
				$regex = "@\s?([a-zA-Z0-9_\.]+)\s+?\(@";

				if (preg_match($regex, $text, $_matches)) {
					$current_table = $_matches[1];
					if (strpos($current_table, '.')) $current_table = explode('.', $current_table)[1];
					$tables[] = $current_table;
				}
			}

			// table engine
			if ((stripos($line, 'ENGINE') === 0) and $current_table) {
				$engine = substr($line, 9);
				if (strpos($engine, ';') > 0) $engine = substr($engine, 0, -1);
				$info[] = "engine('{$engine}');";
				$line .= ';';
			}

			// find the end table definition 
			if (strpos($line, ';') and $current_table) {
				// request the creation of migration class
				$columns[$current_table] = array_merge($columns[$current_table], $info);
				$info = [];
				echo "-- table: '{$current_table}' found \n";
				$current_table = null;
			}

			$this->extractColumns($line, $current_table, $columns);

			// find schema create string
			if (stripos($line, 'CREATE SCHEMA IF NOT EXISTS') === 0) {
				$regex = "@\`([a-zA-Z0-9\_\-]+)\`@";
				$schema = null;
				$charset = null;

				// capture the schema name
				if (preg_match($regex, $line, $_matches)) {
					$schema = $_matches[1];
				}

				// capture charset
				if (stripos($line, 'DEFAULT CHARACTER SET')) {
					$regex = "@DEFAULT\sCHARACTER\sSET\s([a-zA-Z0-9\-\_]+)\s+?@i";
					if (preg_match($regex, $line, $_matches)) {
						$charset = $_matches[1];
					}
				}

				if (isset($schema)) {
					self::$_schema = "'{$schema}'";
					if (isset($charset) and $charset != 'utf8')	self::$_schema .= ", '{$charset}'";
					echo "-- Schema: '{$schema}'\n";
				}
			}

			if (!empty($columns)) {
				$this->extractPrimaryKey($line, $current_table, $columns);

				$this->extractForeignKeys($line, $current_table, $info);

				$this->extractUniqueIndexes($line, $current_table, $columns);
			}
		}

		echo "---> " . count($tables) . " tables found\n";
		$result = array_combine($tables, $columns);
		$count = $this->createMigrationsClass($result);
		echo "---> {$count} migrations created\n";
	}
}
