<?php

require_once('../photogateway.php');

class tools_CreateDimensionIndex {

  public function run($albumPath) {

    chdir("/home/mnk/www/marc-katrine-new/www/");

    $gw = new PhotoGateway();
    $dim = $gw->createDimensionIndexForAlbum($albumPath);
    return json_encode($dim);
  }

}

$albumPath = $argv[1];

$tool = new tools_CreateDimensionIndex();
$r = $tool->run($albumPath);
var_dump($r);
exit(0);
