<?php
require_once(__DIR__.'/../vendor/autoload.php');

$flickr = getFlickrClient();

$result = $flickr->request('flickr.photosets.getInfo', [
  'photoset_id' => $argv[1],  
]);

$set = $result['photoset'];

downloadPhotoset($set);

