<?php

class pages_Root extends pages_Base {

  public function renderHtml() {
    $t = $this->registry->templateGateway->createTemplate("root.tpl.php");
    return $t->render();
  }

}