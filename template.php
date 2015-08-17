<?php

class TemplateException extends Exception {}

class Template extends Object {

  private $file;

  public function __construct($file) {
    $this->file = $file;
  }

  public function render() {
    if (func_num_args() > 0) {
      extract(func_get_arg(0));
    }
    ob_start();
    try {
      if ( !file_exists($this->file) ) {
        throw new TemplateException("Unable to find file '".$this->file."'.");
      }
      include($this->file);
      $buffer = ob_get_clean();
      return $buffer;
    } catch (Exception $ex) {
      ob_end_clean();
      throw $ex;
    }
  }

}
