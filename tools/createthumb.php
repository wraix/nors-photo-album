<?php

require_once('../photogateway.php');

class tools_CreateThumb {

  public function run($file) {
    $thumbFile = "/home/mnk/www/marc-katrine-new/www/thumbs/test.png";

    $gw = new PhotoGateway();
    $gw->createThumbnailForImageFile($file, $thumbFile);
    return "Thumb created.";
  }

}

$file = $argv[1];

$tool = new tools_CreateThumb();
$r = $tool->run($file);
var_dump($r);
exit(0);
