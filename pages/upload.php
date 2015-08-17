<?php

ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
ini_set('max_input_time', 300);
ini_set('memory_limit', "256M");
ini_set('post_max_size', "100M");
ini_set('upload_max_filesize', "50M");
ini_set('max_file_uploads', 1); // number of simultanious file uploads

class pages_Upload extends pages_Base {

  public function renderHtml() {
  }

  public function postHtml() {
    if ( empty($_FILES['uploadphoto']) ) {
      return new response_NotFound(array(), "Missing uploadphoto.");
    }
    $uploadedFile = $_FILES['uploadphoto']['tmp_name'];

    $albumPath = null;
    if ( isset($this->request['query']) ) {
      $query = null;
      parse_str($this->request['query'], $query);
      if ( !empty($query['l']) ) {
        $albumPath = $query['l'];
      }
    }

    $errors = $this->registry->photoGateway->validateUploadedPhoto($uploadedFile, $albumPath);
    if ( !empty($errors) ) {
      if ( file_exists($uploadedFile) ) {
        unlink($uploadedFile);
      }
      return new response_NotFound(array(), json_encode($errors));
    }

    $photo = $this->registry->photoGateway->uploadPhotoToAlbum($albumPath, $uploadedFile);
    if ($photo !== false) {

      $url = $this->request['protocol'] . '://' . $this->request['host'];
      if ( empty($query['l']) ) {
        $url .= '/';
      } else {
        $url .= $query['l'];
      }
      return new response_SeeOther($url);

      return new response_Ok(array(), "");
    }

    throw new Exception("Unable to upload photo '".$uploadedFile."' to album '".$albumPath."'.");
  }

}