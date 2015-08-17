<?php

class TemplateGatewayException extends Exception {}

class TemplateGateway {

  public function createTemplate($template) {
    // @TODO: Make Template class use search path
    return new Template("../templates/" . $template);
  }

}