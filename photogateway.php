<?php

class PhotoGateway {

  const ALBUMS_PATH = "/albums";
  const THUMBS_PATH = "/thumbs";

  public function validateUploadedPhoto($uploadedFile, $albumPath) {

    // Sanity check. Empty.
    if ( empty($uploadedFile) ) {
      return  "No file.";
    }    

    // Sanity check. Is the uploaded file an image we can use?
    $size = getimagesize($uploadedFile);
    if ( $size === false) {
      return  "Not a valid photo.";
    }

    // Sanity check. File size and dimensions
    // @TODO:

    // Sanity check. albumPath must exist.
    $path = $this->resolveAlbumFilePath($albumPath);
    if ( !file_exists($path) ) {
      return "Not a valid album path.";
    }

    return null;
  }
 
  // @return string of the new photo name || false
  public function uploadPhotoToAlbum($albumPath, $uploadedFile) {

    $timestamp = (new DateTime('NOW'))->format('YmdHis');
    $imageType = exif_imagetype($uploadedFile);
    $extension = image_type_to_extension($imageType, false);
    $contentHash = hash_file("sha256", $uploadedFile); // No need to salt  to avoid collision. Odds that someone will upload the same file at same time more than once is zero.

    // Reorientate image based on exif data. Fixes IOS uploads.
    switch ($imageType) {
      case IMAGETYPE_JPEG:
        $this->reorientImageBasedOnExifJpeg($uploadedFile);
        break;
    }

    // Uploaded filename format: 20150101_[hash].[extension]
    $newFile = $timestamp . "_" . $contentHash . "." . $extension;
    $newFile = $this->resolveAlbumFilePath($albumPath . "/" . $newFile);

    if ( file_exists($newFile) ) {
      return false;
    }

    if ( move_uploaded_file($uploadedFile, $newFile) ) {
      return $newFile;
    }
    return false;
  }

  public function fetchAlbumPhotos($albumPath) {
    $photos = [];

    $path = $this->resolveAlbumFilePath($albumPath);
    
    $it = new DirectoryIterator($path);
    foreach ($it as $file) {
      if ($file->isDot()) continue;
      if (!$file->isFile()) continue;

      list($width, $height) = getimagesize($file->getPathname());
      $thumb = $this->fetchThumbForPhoto($file->getPathname());
      $src = $this->resolveAlbumLogicalPath($albumPath) . "/" . $file->getFilename();

      $photos[$src] = [
        'idx' => 0,
        'w' => $width,
        'h' => $height,
        'src' => $src,
        'thumb' => $thumb
      ];
    }

    array_multisort(array_keys($photos), SORT_NATURAL, $photos);

    // Add index to help javascript maintain ordering made by php.
    $result = array();
    $i = 1;
    foreach ($photos as $photo) {
      $photo['idx'] = $i;  
      $i++;
      $result[$photo['src']] = $photo;
    }
    $photos = $result;

    return $photos;
  }

  private function reorientImageBasedOnExifJpeg($uploadedFile) {
    $exif = exif_read_data($uploadedFile);
    if ( empty($exif) || empty($exif['Orientation']) ) {
      return;
    }

    $image = imagecreatefromjpeg($uploadedFile);
    if ( empty($image) ) {
      return;
    }

    $ort = $exif['Orientation'];
    if ($ort == 6 || $ort == 5)
      $image = imagerotate($image, 270, null);
    if ($ort == 3 || $ort == 4)
      $image = imagerotate($image, 180, null);
    if ($ort == 8 || $ort == 7)
      $image = imagerotate($image, 90, null);

    if ($ort == 5 || $ort == 4 || $ort == 7)
      imageflip($image, IMG_FLIP_HORIZONTAL);

    imagejpeg($image, $uploadedFile, 100);
    imagedestroy($image);
  }

  private function fetchThumbForPhoto($file) {
    $thumbFile = str_replace(self::ALBUMS_PATH, self::THUMBS_PATH, $file);
    $thumb = self::THUMBS_PATH . $this->translateToRelativePath($thumbFile);

    // Check existance, iff not create it.
    if ( !file_exists($thumbFile) ) {
      // Auto create directories if missing, before creating thumb.
      $this->mkdir_r(dirname($thumbFile), 0775);
      $this->createThumbnailForPhoto($file, $thumbFile);
    }
    return $thumb;
  }

  private function translateToRelativePath($file) {

    $needle = $this->fetchRootAlbumDirectory();
    if ( stripos($file, $needle) !== false ) {
      return str_replace($needle, "", $file);
    }

    $needle = $this->fetchRootThumbDirectory();
    if ( stripos($file, $needle) !== false ) {
      return str_replace($needle, "", $file);
    }

    return $file;
  }

  public function createDimensionIndexForAlbum($albumPath) {
    $map = array();
    $images = $this->fetchAlbumImages($albumPath);

    $cnt = count($images);
    for ($i = 0; $i < $cnt; $i++) {

      $file = getcwd() . $images[$i];
      list($width, $height) = getimagesize($file);

      $map[md5($images[$i])] = array('src' => str_replace(self::ALBUMS_PATH, "", $images[$i]), 'w' => $width, 'h' => $height);
    }
    return $map;
  }

  public function fetchAlbumImages($albumPath) {
    $images = array();

    $path = $this->resolveAlbumFilePath($albumPath);

    $it = new DirectoryIterator($path);
    foreach ($it as $file) {
      if($file->isDot()) continue;
      if (!$file->isFile()) continue;

      $logicalAlbumFile = $this->resolveAlbumLogicalPath($albumPath) . "/" . $file->getFilename();      
      $images[] = $logicalAlbumFile;
    }
    
    // Sort
    natcasesort($images);

    return $images;
  }

  public function fetchAlbumIconForUrl($albumPath) {
    // Pick random photo in the album and show as album icon.
    $path = $this->resolveThumbsFilePath($albumPath);

    $files = glob($path ."/*");
    if ( empty($files[rand(0, count($files) - 1)]) ) {
      return "";
    }

    $icon = $files[rand(0, count($files) - 1)];

    $i = strripos($icon, "/");
    $icon = substr($icon, $i);
    return $this->resolveThumbsLogicalPath($albumPath) . $icon;
  }

  // @return string
  public function createThumbnailForPhoto($file, $thumbFile) {
    $img = new SimpleImage($file);
    $img->scale(0.25)->save($thumbFile);
    return $thumbFile;
  }

  // @return array
  public function fetchThumbnails($albumPath) {
    $thumbs = array();

    $path = $this->resolveAlbumFilePath($albumPath);
    
    // Fetch all files in album path.
    $it = new DirectoryIterator($path);
    foreach ($it as $file) {
      if ($file->isDot()) continue;
      if (!$file->isFile()) continue;

      $thumbFile = $this->resolveThumbsFilePath($albumPath) . "/" . $file->getFilename();
      $logicalThumbFile = $this->resolveThumbsLogicalPath($albumPath) . "/" . $file->getFilename();

      // Sanity check. Existance
      if ( !file_exists($thumbFile) ) {

        // Auto create directories if missing, before creating thumb.
        $this->mkdir_r(dirname($thumbFile), 0775);

        $this->createThumbnailForPhoto($file->getPathname(), $thumbFile);
        $thumbs[] = $logicalThumbFile;
      } else {
        $thumbs[] = $logicalThumbFile;
      }
    }

    // Sort
    natcasesort($thumbs);

    return $thumbs;
  }

  // @return array
  public function fetchAlbums() {
    $rootDirectory = $this->fetchRootAlbumDirectory();
    $base = explode('/', $rootDirectory);
    $root = array_pop($base);
    $base = implode('/', $base);

    $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD);

    $result = array('/'.$root);
    foreach ($it as $path => $dir) {
      if ($dir->isDir()) {
        $result[] = str_replace($base, '', $path);
      }
    }

    // Sort result
    natcasesort($result);

    $result = array_combine(array_values($result), array_values($result));

    $result = $this->explodeTree($result, '/');

    if ( !is_array($result['albums']) ) {
      $result['albums'] = array($result['albums']);
    }

    return $result;
  }

  private function mkdir_r($dirName, $rights=0777){
    $dirs = explode('/', $dirName);
    $dir='';
    foreach ($dirs as $part) {
      $dir .= $part . '/';
      if ( !is_dir($dir) && strlen($dir) > 0 )
        mkdir($dir, $rights);
    }
  }

  private function fetchRootAlbumDirectory() {
    return getcwd() . self::ALBUMS_PATH;
  }

  private function resolveAlbumFilePath($albumPath) {
    return $this->fetchRootAlbumDirectory() . $albumPath;
  }

  private function resolveAlbumLogicalPath($albumPath) {
    return self::ALBUMS_PATH . $albumPath;
  }

  private function fetchRootThumbDirectory() {
    return getcwd() . self::THUMBS_PATH;
  }

  private function resolveThumbsFilePath($albumPath) {
    return $this->fetchRootThumbDirectory() . $albumPath;
  }

  private function resolveThumbsLogicalPath($albumPath) {
    return self::THUMBS_PATH . $albumPath;
  }

  private function explodeTree($array, $delimiter = '_', $baseval = false) {
    if(!is_array($array)) return false;
    $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
    $returnArr = array();
    foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
            if (!isset($parentArr[$part])) {
                $parentArr[$part] = array();
            } elseif (!is_array($parentArr[$part])) {
                if ($baseval) {
                    $parentArr[$part] = array('__base_val' => $parentArr[$part]);
                } else {
                    $parentArr[$part] = array();
                }
            }
            $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
            $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
            $parentArr[$leafPart]['__base_val'] = $val;
        }
    }
    return $returnArr;
  }

}