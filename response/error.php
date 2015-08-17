<?php

class response_Error extends Response {

  public function __construct(array $headers, $body) {
    parent::__construct(500, 'Internal Server Error', $headers, $body);
  }

}