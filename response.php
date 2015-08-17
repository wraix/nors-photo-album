<?php

class Response extends Object {

  protected $status = 0;
  protected $message = "";
  protected $body = "";
  protected $headers = array();

  public function __construct($status, $message, array $headers, $body) {
    $this->status = $status;
    $this->message = $message;
    $this->headers = $headers;
    $this->body = $body;
  }

  public function getBody() {
    return (!empty($this->body) ? $this->body : "");
  }

  public function getHeaders() {
    return (!empty($this->headers) ? $this->headers : array());
  }

  public function getStatus() {
    return (!empty($this->status) ? $this->status : 0);
  }

  public function getMessage() {
    return (!empty($this->message) ? $this->message : "");
  }

}