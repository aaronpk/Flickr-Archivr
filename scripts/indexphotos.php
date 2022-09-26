<?php
require_once(__DIR__.'/../vendor/autoload.php');

$photos = glob($_ENV['STORAGE_PATH'].'*/*/*/*/info/photo.json');

$indexPath = $_ENV['STORAGE_PATH'].'index';

if(!file_exists($indexPath))
  mkdir($indexPath);

$index = [];

foreach($photos as $photoMetaFile) {
  preg_match('/\/([0-9]+)\/info\/photo\.json/', $photoMetaFile, $match);
  $photoID = $match[1];

  $path = str_replace($_ENV['STORAGE_PATH'], '', $photoMetaFile);
  $path = str_replace('info/photo.json', '', $path);

  $index[$photoID] = $path;
  echo $photoID."\t\t".$path."\n";
}

file_put_contents($indexPath.'/photos.json', prettyJSON($index));

