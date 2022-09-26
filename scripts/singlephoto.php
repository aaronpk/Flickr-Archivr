<?php
require_once(__DIR__.'/../vendor/autoload.php');

$flickr = getFlickrClient();

$result = $flickr->request('flickr.photos.getInfo', [
  'photo_id' => $argv[1],
]);

if(!isset($result['photo'])) {
  echo "Error downloading photo\n";
  die();
}

$photo = $result['photo'];

savePhoto($photo, false);

