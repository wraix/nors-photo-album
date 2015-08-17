<?php

class response_Ok extends Response {

  public function __construct(array $headers, $body) {
    parent::__construct(200, 'OK', $headers, $body);
  }

}