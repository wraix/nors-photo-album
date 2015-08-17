<?php

class response_SeeOther extends Response {

  public function __construct($url) {
    parent::__construct(
      303,
      'SeeOther',
      array(
        'Location: ' . $url
      ),
      ""
    );
  }

}