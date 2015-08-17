<?php

class pages_Album extends pages_Base {

  public function renderHtml() {
    $this->styles[] = "/css/photoswipe.css";
    $this->styles[] = "/css/default-skin/default-skin.css";
    $this->styles[] = "/css/album.css";

    $this->scripts[] = "/js/salvattore.min.js";
    $this->scripts[] = "/js/jquery.unveil.js";
    $this->scripts[] = "/js/photoswipe.min.js";
    $this->scripts[] = "/js/photoswipe-ui-default.min.js";
    $this->scripts[] = "/js/album.js";

    $albumPath = $this->request['route'];
    $photos = $this->registry->photoGateway->fetchAlbumPhotos($albumPath);

    $t = $this->registry->templateGateway->createTemplate("album.tpl.php");
    return $t->render(
      array(
        'albumPath' => $albumPath,
        'photos' => $photos
      )
    );
  }

}