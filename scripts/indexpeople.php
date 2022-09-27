<?php
require_once(__DIR__.'/../vendor/autoload.php');

$photos = glob($_ENV['STORAGE_PATH'].'*/*/*/*/info/photo.json');

$indexPath = $_ENV['STORAGE_PATH'].'index';

if(!file_exists($indexPath))
  mkdir($indexPath);

$index = loadJSONFile($indexPath.'/people.json');

foreach($index as $personID => $data) {
  $index[$personID]['photos'] = [];
}

foreach($photos as $photoMetaFile) {
  $peopleMetaFile = str_replace('photo.json', 'people.json', $photoMetaFile);

  if(file_exists($peopleMetaFile)) {
    echo "Indexing people for $peopleMetaFile\n";

    $photo = loadJSONFile($photoMetaFile);
    $people = loadJSONFile($peopleMetaFile);

    foreach($people as $person) {
      if(!isset($index[$person['nsid']]))
        $index[$person['nsid']] = ['photos' => []];

      $index[$person['nsid']]['photos'][] = $photo['id'];
    }

  }
}

file_put_contents($indexPath.'/people.json', prettyJSON($index));
