<?php

namespace Zeero\Zcli\Commands;

use Zeero\Core\Env;
use Zeero\Zcli\Command;


/**
 * Command for Security purposes
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
class SecurityCommand extends Command
{
	public static $_arguments = ['key'];

	public function __construct()
	{
		parent::__construct();
	}

	public function _initialize()
	{
		echo "generate a new APP_KEY: \n\n";
		echo "try security:key generate \n";
	}

	/**
	 * generate a application key
	 *
	 * @return void
	 */
	public function key()
	{
		if ($this->input_value == 'generate') {
			$key = bin2hex(openssl_random_pseudo_bytes(20));
			Env::replace('APP_KEY', $key);
			echo "app key generated";
		}
	}
}
