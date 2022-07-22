<?php

use Zeero\Zcli\Commands\DbCommand;
use Zeero\Zcli\Commands\MakeCommand;
use Zeero\Zcli\Commands\MigrateCommand;
use Zeero\Zcli\Commands\RouteCommand;
use Zeero\Zcli\Commands\SecurityCommand;
use Zeero\Zcli\Commands\ServeCommand;
use Zeero\Zcli\Commands\sqlToMigrationCommand;
use Zeero\Zcli\Zcli;

/**
 * 
 * HERE IS DEFINED ALL DEFAULT ZCLI COMMANDS
 * 
 */


Zcli::registerCommand('make', MakeCommand::class);
Zcli::registerCommand('serve', ServeCommand::class);
Zcli::registerCommand('migrate', MigrateCommand::class);
Zcli::registerCommand('security', SecurityCommand::class);
Zcli::registerCommand('db', DbCommand::class);
Zcli::registerCommand('route', RouteCommand::class);
Zcli::registerCommand('sql_to_php', sqlToMigrationCommand::class);
