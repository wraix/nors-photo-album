<?php

require_once('../authenticationgateway.php');

class tools_CreateUser {

  public function run() {
    $line = readline("Telefonnummer: ");
    $phoneNo = trim($line);

    echo "Navn: ";
    $fh = fopen('php://stdin','r');
    $name = rtrim( fgets($fh, 256) );
    fclose($fh);

    $fh = fopen('php://stdin','r');
    echo "Kode: ";
    `/bin/stty -echo`;
    $password = rtrim(fgets($fh,64));
    `/bin/stty echo`;
    echo "\n";
    fclose($fh);

    $gw = new AuthenticationGateway();
    $user = $gw->createUserByPhoneNo($phoneNo, $password, $name);

    return json_encode($user);
  }

}

$tool = new tools_CreateUser();
$r = $tool->run();
var_dump($r);
exit(0);