<?php

namespace Zeero\Zcli\Commands;

use Zeero\Kernel;
use Zeero\Zcli\Command;
use Zeero\Zcli\ZcliClassCreatorHandler;


/**
 * Zcli Make Command
 * 
 * for creation of controllers , models , migrations classes , requests , console 
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
class MakeCommand extends Command
{
	public static $_arguments = [
		'controller', 'model',
		'migration', 'request',
		'console_command', 'seed'
	];

	private $handler;


	public function __construct()
	{
		$this->handler = new ZcliClassCreatorHandler();
		parent::__construct();
	}


	public function _initialize()
	{
		echo 'commands: ' . implode(',', self::$_arguments) . "\n";
	}

	/**
	 * create controller
	 *
	 * @return void
	 */
	public function controller()
	{
		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		$this->handler->makeController(
			$this->input_value,
			($this->input_options[0] ?? false) == '--r' ? true : null
		);
		echo "Controller {$this->input_value} created !\n";
	}

	/**
	 * create model
	 *
	 * @return void
	 */
	public function model()
	{
		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		$this->handler->makeModel($this->input_value);
		echo "Model {$this->input_value} created !";
	}


	/**
	 * create migration
	 *
	 * @return void
	 */
	public function migration()
	{
		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		$this->handler->makeMigration($this->input_value, $this->input_options[0] ?? null);
		echo "Migration {$this->input_value} created !\n";
	}


	/**
	 * create request
	 *
	 * @return void
	 */
	public function request()
	{
		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		$this->handler->makeRequestClass($this->input_value);
		echo "Request {$this->input_value} created !\n";
	}


	/**
	 * create seed
	 *
	 * @param string|null $value
	 * @return void
	 */
	public function seed(string $value = null)
	{
		Kernel::DataBaseBoot();

		if (!is_null($value)) $this->input_value = $value;

		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		$model = "App/Models/" . $this->input_value;

		if (!file_exists($model . '.php')) {
			echo "Model {$this->input_value} Not Found";
			exit;
		}

		$model = str_replace('/', '\\', $model);

		$data = $model::all();

		if (count($data)) {
			$fields = array_keys((array) $data[0]);
			array_shift($fields);
		} else {
			echo "No Records";
			exit;
		}

		$this->handler->makeSeed($this->input_value, $fields, $data);
		echo "Seed {$this->input_value} created !\n";
	}


	/**
	 * create command
	 *
	 * @return void
	 */
	public function console_command()
	{
		if (!$this->input_value) {
			echo "Invalid Classname\n";
			exit;
		}

		$this->handler->makeCommandClass($this->input_value, $this->input_options[0] ?? '');
		echo "Command {$this->input_value} created !\n";
	}
}
