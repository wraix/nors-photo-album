<?php

class response_NotFound extends Response {

  public function __construct(array $headers, $body) {
    parent::__construct(404, 'NotFound', $headers, $body);
  }

}