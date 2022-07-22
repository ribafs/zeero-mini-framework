<?php

namespace Zeero\Database;


/**
 * Abstract Migration Class
 * 
 * 
 */
abstract class Migration
{

    /**
     * up
     *
     * @return void
     */
    abstract public function up();


    /**
     * down
     *
     * @return void
     */
    abstract public function down();
}