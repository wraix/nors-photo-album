<?php
error_reporting(E_ALL);

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) ."/../") . PATH_SEPARATOR . realpath(dirname(__FILE__) ."/../pages/"));

require_once('site.php');

$registry = new Registry();
$albums = $registry->photoGateway->fetchAlbums();
$albums = $albums['albums'];

// Routes
$routes = array();
$routes[] = array('r' => '~^/authenticate$~', 'c' => 'pages_Authenticate'); // Required to make authentication work
$routes[] = array('r' => '~^/upload$~', 'c' => 'pages_Upload'); // Required to make upload photos work

$routes[] = array('r' => '~^/$~', 'c' => 'pages_Root');

// Album routes
foreach ($albums as $albumName => $albumChildren) {
  if ( is_array($albumChildren) ) {
    foreach ($albumChildren as $childName => $path) {
      $routes[] = array('r' => '~^/'.$albumName.'/'.$childName.'$~', 'c' => 'pages_Album');
    }
  } else {
    $routes[] = array('r' => '~^/'.$albumName.'$~', 'c' => 'pages_Album');
  }
}

$site = new Site($registry);
$site->run($routes, $albums);
exit(0);
