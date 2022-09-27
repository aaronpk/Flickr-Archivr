<?php
require_once(__DIR__.'/../vendor/autoload.php');

$flickr = getFlickrClient();

$progressFile = $_ENV['STORAGE_PATH'].'progress.json';
if(!file_exists($progressFile)) {
  $progress = [];
} else {
  $progress = json_decode(file_get_contents($progressFile), true);
}

if(isset($progress['page'])) {
  $page = $progress['page'];
} else {
  $page = null;
}


$skip_if_exists = ($argv[1] == 're-download' ? false : true);


// Process the current page
$photos = processPage($page);

// iterate through the remaining pages
for($i=$page+1; $i<$photos['pages']; $i++) {
  $photos = processPage($i);
}



function processPage($page) {
  global $flickr, $progress, $progressFile, $skip_if_exists;

  echo "Processing page $page\n";

  $result = $flickr->request('flickr.people.getPhotos', [
    'user_id' => 'me',
    'per_page' => 10,
    'page' => $page,
    'extras' => 'date_upload,date_taken',
  ]);

  if(!isset($result['photos'])) {
    echo "Error downloading photos\n";
    die(1);
  }

  $photos = $result['photos'];

  try {
    foreach($photos['photo'] as $photo) {
      savePhoto($photo, $skip_if_exists);
    }
  } catch(Exception $e) {
    $message = $e->getMessage();
    if(($p=strpos($message, '<!DOCTYPE html>')) !== false) {
      $message = substr($message, 0, $p);
    }
    echo "EXCEPTION: ".$message."\n";
    die(1);
    # try again
  }

  $progress['page'] = $page;

  file_put_contents($progressFile, json_encode($progress, JSON_PP));

  return $photos;
}




