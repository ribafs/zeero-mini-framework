<?php

namespace Zeero\Zcli\Commands;

use Exception;
use Zeero\Core\Router\Route;
use Zeero\Zcli\Command;


class RouteCommand extends Command
{
	public static $_arguments = ['list'];

	public function __construct()
	{
		parent::__construct();
	}

	public function _initialize()
	{
		echo 'commands: ' . implode(',', self::$_arguments) . "\n";
	}


	/**
	 * list the current routes list
	 *
	 * @return void
	 */
	function list()
	{
		require_once "App" . DS . 'routes.php';

		$routes = Route::getInstance()->getAllRoutes();
		
		if(is_null($routes)) {
			throw new Exception("Routes List Empty");
		}

		echo "| METHOD |AUTH|\t\tROUTE\t\t|\tACTION\n";

		foreach ($routes as $method => $method_routes) {
			// 
			foreach ($method_routes as $route => $info) {
				$action_type = gettype($info->action);
				$action = 'NULL';

				$auth = $info->require['auth'] ? '1' : '0';

				if ($action_type == 'array' and count($info->action) == 2) {
					$action = $info->action[0] . "@" . $info->action[1];
				} elseif ($action_type == 'object') {
					$action = 'Closure';
				}

				// for small routes
				if (strlen($route) < 7) $route .= str_repeat(' ' , 13);

				if (strpos($action, 'App\Controllers') === 0)
					$action = str_replace("App\Controllers\\", "", $action);

				echo "| " . strtoupper($method) . "\t | {$auth}  | {$route} \t\t| {$action}\n";
			}
		}
	}
}
