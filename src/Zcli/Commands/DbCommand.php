<?php

namespace Zeero\Zcli\Commands;

use Zeero\Database\SchemaInfo;
use Zeero\Kernel;
use Zeero\Zcli\ColumnTransform;
use Zeero\Zcli\Command;
use Zeero\Zcli\ZcliMigrationHandler;

class DbCommand extends Command
{
	public static $_arguments = ['seed', 'reverse_migration'];

	public function __construct()
	{
		parent::__construct();
	}

	public function _initialize()
	{
		echo 'commands: ' . implode(',', self::$_arguments) . "\n";
	}

	/**
	 * run a seed class
	 *
	 * @return void
	 */
	public function seed()
	{
		if (!$this->input_value) {
			die('Invalid Seed Classname');
		}

		ZcliMigrationHandler::RunSeed($this->input_value);
	}


	/**
	 * swap tables
	 *
	 * @param array $list
	 * @param array $names
	 * @param array $values
	 * @return void
	 */
	private function Swap($list, &$names, array $values)
	{
		$result = [];

		// sort
		foreach ($list as $key => $value) {
			if (!array_key_exists($key, $values)) {
				if (count($value) == 0) $result[] = $key;
				else {
					$v = $value;
					$v[] = $key;
					foreach ($v as $_v) {
						if (array_key_exists($_v, $list)) {
							$index = array_search($_v, $result);
							unset($result[$index]);
						}
						$result[] = $_v;
					}
				}
			}
		}

		// push the tables names
		foreach ($result as $v) {
			if (!in_array($v, $names))
				$names[] = $v;
		}
	}


	/**
	 * Sort Tables in Foreign Key Order
	 *
	 * @param array $tables the tables names
	 * @param array $statments the staments list
	 * @param array $refs the foreign keys
	 * @return array
	 */
	private function SortStatmentsByForeignKeys(array $tables, array $statments, array $refs)
	{
		// constraints order
		$relationships = [];

		foreach ($tables as $tbl) {
			$relationships[$tbl] = ColumnTransform::getTableForeignOrder($tbl, $refs);
		}

		$keys = array_keys($relationships);
		$names = [];

		foreach ($relationships as $key => $value) {
			if (!in_array($key, $relationships)) {
				if (count($value)) {
					$index = array_search($key, $keys);
					$sliced = array_slice($relationships, 0, $index + 1);
					$this->Swap($sliced, $names, $value);
				} else {
					$relationships[] = $key;
				}
			}
		}

		$dict = [];

		foreach ($names as $key) {
			$dict[$key] = $statments[$key] ?? [];
		}

		return $dict;
	}


	/**
	 * get tables statments and references
	 *
	 * @param array $tables the tables names
	 * @param array $statments
	 * @param array $refs
	 * @param array $engines
	 * @return void
	 */
	private function getTableStatments(array $tables, array &$statments, array &$refs, array $engines)
	{
		// foreign keys references
		$foreig_keys = [];

		// statments
		foreach ($tables as $tbl) {
			$cols = SchemaInfo::getColumns($tbl);
			$fks = SchemaInfo::getTableForeignKeyConstraint($tbl);

			if (count($fks)) {
				$foreig_keys[$tbl][] = $fks;
				foreach ($fks as $value) {
					$refs[$tbl][] = $value['ref_table'];
				}
			} else {
				$refs[$tbl] = [];
			}
			//
			$statments[$tbl] = ColumnTransform::getStatments($cols, $fks);
			$statments[$tbl][] = "engine('{$engines[$tbl]}');";
		}
	}


	/**
	 * Generate migrations files by currrents table or tables in database server
	 *
	 * @return void
	 */
	public function reverse_migration()
	{
		if (!$this->input_value) {
			die('Invalid Option');
		}

		$value = $this->input_value;

		Kernel::DataBaseBoot();

		// the all tables in current db
		$tables_list = SchemaInfo::getTables();

		$engines = [];

		// filter only the TABLE_NAME field
		$tables = array_map(function ($info) use (&$engines) {
			$engines[$info['TABLE_NAME']] = $info['ENGINE'];
			return $info['TABLE_NAME'];
		}, $tables_list);

		if ($value != 'all') {
			if (!in_array($value, $tables)) {
				die('Table Not Exists');
			} else {
				$tables = [$value];
			}
		}

		$statments = [];
		// foreign keys referenced tables names
		$refs = [];

		$this->getTableStatments($tables, $statments, $refs, $engines);

		if (count($tables) > 1) {
			echo "\nTables Found: " . count($tables) . PHP_EOL;
			$s = array_keys($statments);
			sort($s);
			$s = array_map(function ($item) {
				return " => {$item}" . PHP_EOL;
			}, $s);
			echo implode('', $s);
		}

		$sorted = $this->SortStatmentsByForeignKeys($tables, $statments, $refs);
		$count = sqlToMigrationCommand::createMigrationsClass($sorted);
		echo "---> {$count} migrations created\n";
	}
}
