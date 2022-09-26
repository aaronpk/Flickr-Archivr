<?php
require_once(__DIR__.'/../vendor/autoload.php');

$flickr = getFlickrClient();


$result = $flickr->request('flickr.photosets.getList', [
  'per_page' => 500,
]);

$sets = $result['photosets']['photoset'];

foreach($sets as $set) {
  downloadPhotoset($set);
}
