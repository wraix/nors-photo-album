<?php

abstract class pages_Base {

  protected $uniqueId;
  protected $request;
  protected $session;
  protected $registry;
  protected $albums;

  protected $title;
  protected $styles = array();
  protected $scripts = array();

  public function process($uniqueId, $request, &$session, Registry $registry, array $albums) {
    $this->uniqueId = $uniqueId;
    $this->request = $request;
    $this->session = $session;
    $this->registry = $registry;
    $this->albums = $albums;

    if ( $this->request['method'] === 'get' ) {
      $this->title = "Nor's Photos";
      $page = $this->renderHtml();

      // User decided to send response, so let it trough untouched.
      // Used for redirects etc.
      if ( is_a($page, 'Response') ) {
        return $page;
      }

      return new response_Ok(
        array(),
        $this->wrapHtml($page)
      );

    } elseif ($this->request['method'] === 'post') {

      $page = $this->postHtml();

      if ( is_a($page, 'Response') ) {
        return $page;
      }

      // Redirect get
      return new SeeOther($this->request['url']);
    }

  }

  public function renderHtml() {
  }

  public function postHtml() {
  }
  
  public function wrapHtml($page) {

    // @REMARK: This is not needed no more, is done in index.php as we need to auto generate routes for albums.
    // Create menu
    // $albums = $this->registry->photogateway->fetchAlbums();
    // $albums = $albums['albums'];

    $activeAlbum = explode('/', $this->request['route']);
    $activeAlbumName = $activeAlbum[1];
    
    $activeAlbumItem = "";
    if ( isset($activeAlbum[2]) ) {
      $activeAlbumItem = $activeAlbum[2];
    }

    $t = $this->registry->templateGateway->createTemplate("document.tpl.php");
    return $t->render(
      array(
        'title' => $this->title,
        'styles' => $this->styles,
        'scripts' => $this->scripts,
        'page' => $page,
        'isRoot' => $this->request['route'] == '/',
        'activeAlbumName' => $activeAlbumName,
        'activeAlbumItem' => $activeAlbumItem,
        'albums' => $this->albums,
        'albumsBase' => '/albums'
      )
    );
  }

}
