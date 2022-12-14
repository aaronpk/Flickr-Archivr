<?php
require_once(__DIR__.'/../vendor/autoload.php');

$photos = glob($_ENV['STORAGE_PATH'].'*/*/*/*/info/photo.json');

$indexPath = $_ENV['STORAGE_PATH'].'index';

if(!file_exists($indexPath))
  mkdir($indexPath);

$index = [];

foreach($photos as $photoMetaFile) {
  $photo = loadJSONFile($photoMetaFile);

  if(count($photo['tags']['tag'])) {
    echo "Indexing tags for ".$photo['id']."\n";

    foreach($photo['tags']['tag'] as $tag) {
      $tagName = $tag['_content'];

      echo "\t".$tagName."\n";

      if(!isset($index[$tagName])) {
        $index[$tagName] = [
          'photos' => [], 
          'name' => $tag['raw'], 
          'slug' => $tag['_content']
        ];
      }

      $index[$tagName]['photos'][] = $photo['id'];
    }

  }
}

file_put_contents($indexPath.'/tags.json', prettyJSON($index));
