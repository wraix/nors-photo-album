<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<meta name="description" content="" />
<meta name="author" content="Marc Nyboe Kammersgaard Frisenette" />
<link rel="shortcut icon" href="ico/favicon.png" />

<title><?php e($title); ?></title>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

<?php foreach($styles as $style) : ?>
  <link rel="stylesheet" href="<?php e($style); ?>" />
<?php endforeach; ?>

<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
</head>
<body>

  <nav class="navbar navbar-fixed-top navbar-inverse">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="/"><?php e($title); ?></a>    
      </div>
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav">  
<?php foreach($albums as $albumName => $albumItems) : ?>
  <?php if (is_array($albumItems)) : ?>
       <li class="dropdown <?php ( $activeAlbumName == $albumName ? "" /*e("active")*/ : ""); ?>">
         <a href="<?php e($albumName); ?>" class="dropdown-toggle" data-toggle="dropdown"><?php e(ucfirst($albumName)); ?> <b class="caret"></b></a>
         <ul class="dropdown-menu navmenu-nav" role="menu">
         <?php foreach($albumItems as $itemName => $path) : ?>
         <?php if (is_array($path)) :?>
           <li class="<?php ( $activeAlbumItem == $itemName ? e("active") : ""); ?>"><a href="<?php e($albumName . '/' . $itemName); ?>"><?php e(ucfirst($itemName)); ?></a></li>
         <?php else : ?>
           <li class="<?php ( $activeAlbumItem == $itemName ? e("active") : ""); ?>"><a href="<?php e(str_replace($albumsBase, '', $path)); ?>"><?php e(ucfirst($itemName)); ?></a></li>
         <?php endif; ?>
         <?php endforeach; ?>
         </ul>
       </li>
  <?php else : ?>
       <li class="<?php ( $activeAlbumName == $albumName ? "" /*e("active")*/ : ""); ?>"><a href="<?php e(str_replace($albumsBase, '', $albumItems)); ?>"><?php e(ucfirst($albumName)); ?></a></li>
  <?php endif; ?>
<?php endforeach; ?>
   </ul>

      </div><!-- /.nav-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->

<div class="container" >
  <!-- Start content below navigation bar. -->
<?php if ($isRoot) : ?>
  <div>
<?php else: ?>
  <div style="padding-top: 60px">
<?php endif; ?>
  
    <!-- Overlay used for ajax waits.  -->
    <div id="loader"></div>

    <?php echo $page ?>

  </div>
</div>

<!-- Scripts used on all pages -->
<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

<!-- Page specific scripts -->
<?php foreach($scripts as $script) : ?>
  <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>

</body>
</html>
