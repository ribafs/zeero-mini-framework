<?php

namespace Zeero\Core;

use Zeero\Core\Exceptions\AlreadyExistsException;
use Zeero\Core\Exceptions\NotFoundException;


/**
 * Registry Sigleton Class
 * 
 * used to register applications components
 * 
 * @author carlos bumba carlosbumbanio@gmail.com
 */

class Registry
{
  private static $instance;
  private $objects;

  private function __construct()
  {
  }


  /**
   * Get the Registry Class Instance
   *
   * @return Registry
   */
  public static function getInstance(): Registry
  {
    if (self::$instance == null) {
      self::$instance = new Registry;
    }

    return self::$instance;
  }


  /**
   * Set a new item in registry
   *
   * @param string $key
   * @param mixed $reference
   * @return void
   */
  public function collect(string $key, $reference)
  {

    if (in_array($key, $this->objects ?? [])) {
      throw new AlreadyExistsException("DUPLICATE REGISTRY: '$key' ");
    } else {
      $this->objects[$key] = $reference;
    }
    // 
  }



  /**
   * get a item in registry
   *
   * @param string $key
   * @throws Zeero\Core\Exceptions\NotFoundException if the registry not exists
   * @return mixed
   */
  public function get(string $key)
  {
    $key = trim($key);

    if (!isset($this->objects[$key]))
      throw new NotFoundException("REGISTRY '$key' NOT FOUND");

    return $this->objects[$key];
  }
  // 
}
