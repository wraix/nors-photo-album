<?php

class TypeValidationGateway {

  public function isPhoneNo($phoneNo) {
    if (!empty($phoneNo) && is_numeric($phoneNo) && strlen($phoneNo) == 8) {
      return true;
    }
    return false;
  }

}