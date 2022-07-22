<?php

namespace Zeero\Zcli\Commands;

use Zeero\Zcli\Command;
use Zeero\Zcli\ZcliMigrationHandler;



/**
 * Zcli Migrate Command
 * 
 * used for migration of tables into the schema
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
class MigrateCommand extends Command
{
	public static $_arguments = ['all'];
	public $handler;


	public function __construct()
	{
		$this->handler = new ZcliMigrationHandler();
		parent::__construct();
	}


	/**
	 * Helper Function to find the correct filename of a class
	 *
	 * @param string $class - the classname
	 * @return string|void
	 */
	private function getFileByClass(string $class)
	{
		$dir = 'App/DataBase/Migrations';
		$handler = opendir($dir);

		$middleName = str_replace(['Create', 'Table'], ['', ''], $class);

		while ($file = readdir($handler)) {
			if ($file != '..' and $file != '.') {
				if (strpos(
					$file,
					'create_' . strtolower($middleName) . '_table'
				)) {
					return str_replace('.php', '', $file);
				}
			}
		}
	}

	public function _initialize()
	{

		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		if ($this->input_value) {

			if (strpos($this->input_value, 'Create') !== 0) {
				$this->input_value = 'Create' . $this->input_value;
			}

			if (substr($this->input_value, -5) != 'Table') {
				$this->input_value .= 'Table';
			}

			if (!($value = $this->getFileByClass($this->input_value))) {
				echo "Class {$this->input_value} Not Found \n";
				exit;
			}

			$this->handler->migrate(
				$value,
				$this->input_options[0] ?? null,
				$this->input_options[1] ?? null,
				$this->input_options[2] ?? null
			);
		}
	}

	public function all()
	{
		$this->handler->migrateAll(
			$this->input_value ?? null,
			$this->input_options[0] ?? null,
			$this->input_options[1] ?? null
		);
	}
}
