<?php

class AuthenticationException extends Exception {}

class AuthenticationGateway {

  public function createUserByPhoneNo($phoneNo, $password, $name) {
    $passwd = password_hash($phoneNo . $password, PASSWORD_DEFAULT);

    $user = array(
      'name' => $name,
      'user' => $phoneNo,
      'passwd' => $passwd
    );
    return $user;
  }

  public function selectUserByPhoneNo($phoneNo, $password) {

    $userPath = __DIR__ . '/users';
    $file = $userPath . '/' . $phoneNo;

    if ( file_exists($file) ) {

      $data = file_get_contents($file);
      $user = json_decode($data, true);

      switch(json_last_error()) {
        case JSON_ERROR_NONE:

          if ( empty($user['passwd']) ) {
            throw new AuthenticationException("No password found for user '" . $phoneNo."'");
          }

          if ( password_verify($phoneNo . $password, $user['passwd']) ) {
            return true;
          }        

          break;
        default:
          throw new AuthenticationException("Failed to decode data '".$data."', error: '".json_last_error_msg()."' for user '" . $phoneNo . "'");
          break;
      }

    }

    // default deny.
    return false;
  }

}