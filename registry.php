<?php

class RegistryException extends Exception {}

class Registry {

  private $instances = array();

  function has($className) {
    return isset($this->instances[$className]);
  }

  function set($instance, $className) {
    $this->instances[$className] = $instance;
  }

  function __get($className) {
    if (!isset($this->instances[$className])) {
      $this->instances[$className] = $this->instantiate($className);
    }
    return $this->instances[$className];
  }

  function instantiate($className) {
    // @REMARK: Dunno why but the class_exists check fails for some reason.
    // if ( class_exists($className, false) ) {
    //  throw new RegistryException("Undefined class '".$className."'.");
    // }

    $className = strtolower($className); // only lowercase

    $class = new ReflectionClass($className);
    
    $dependencies = array();
    $constructor = $class->getConstructor();
    if ($constructor) {
      foreach ($constructor->getParameters() as $parameter) {
        if (!$parameter->isOptional()) {
          $paramClass = $parameter->getClass();
          if (!$paramClass) {
            throw new RegistryException("Can't auto-assign parameter '" . $parameter->getName() . "' for '" . $class->getName(). "'");
          }
          $dependencies[] = $this->__get($paramClass->getName());
        }
      }
      return $class->newInstanceArgs($dependencies);
    }
    return $class->newInstance();
  }

}