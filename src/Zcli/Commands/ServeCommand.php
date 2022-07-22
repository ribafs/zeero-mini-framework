<?php

namespace Zeero\Zcli\Commands;

use Zeero\Zcli\Command;


/**
 * Command to start development localhost
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */
class ServeCommand extends Command
{
	public static $_arguments = [];

	public function _initialize()
	{
		if (empty($this->input_value)) $this->input_value = 1000;

		exec("php -S localhost:{$this->input_value} -t App/public");
	}
}
