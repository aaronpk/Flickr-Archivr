<?php
require_once(__DIR__.'/../lib/init.php');

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


// Process the current page
$photos = processPage($page);

// iterate through the remaining pages
for($i=$page+1; $i<$photos['pages']; $i++) {
  $photos = processPage($i);
}



function processPage($page) {
  global $flickr, $progress, $progressFile;

  echo "Processing page $page\n";

  $result = $flickr->request('flickr.people.getPhotos', [
    'user_id' => 'me',
    'per_page' => 10,
    'page' => $page,
    'extras' => 'date_upload,date_taken',
  ]);

  if(!isset($result['photos'])) {
    echo "Error downloading photos\n";
    die();
  }

  $photos = $result['photos'];

  try {
    foreach($photos['photo'] as $photo) {
      savePhoto($photo);
    }
  } catch(Exception $e) {
    echo "EXCEPTION: ".$e->getMessage()."\n";
    die();
    # try again
  }

  $progress['page'] = $page;

  file_put_contents($progressFile, json_encode($progress, JSON_PP));

  return $photos;
}





function savePhoto($info) {
  global $flickr;

  # Check if it's already been saved
  if($info['datetakenunknown']) {
    $date = DateTime::createFromFormat('U', $info['dateupload']);
  } else {
    $date = new DateTime($info['datetaken']);
  }

  $folder = $_ENV['STORAGE_PATH'].$date->format('Y/m/d').'/'.$info['id'];
  $infoFolder = $folder.'/info';
  $sizesFolder = $folder.'/sizes';

  if(file_exists($sizesFolder.'/'.$info['id'].'_'.$info['secret'].'.jpg')) {
    echo "Already downloaded ".$info['id']."\n";
    return;
  }

  echo "Archiving photo: ".$info['id']."\n";

  $result = $flickr->request('flickr.photos.getInfo', [
    'photo_id' => $info['id'],
    'secret' => $info['secret'],
  ]);
  $photo = $result['photo'];
  # print_r($photo);

  $result = $flickr->request('flickr.photos.getSizes', [
    'photo_id' => $info['id'],
    'secret' => $info['secret'],
  ]);
  $sizes = $result['sizes']['size'];
  # print_r($sizes);

  $result = $flickr->request('flickr.photos.getExif', [
    'photo_id' => $info['id'],
    'secret' => $info['secret'],
  ]);
  $exif = $result['photo']['exif'];
  # print_r($exif);

  if($photo['comments'] > 0) {
    $result = $flickr->request('flickr.photos.comments.getList', [
      'photo_id' => $info['id'],
    ]);
    $comments = $result['comments']['comment'];
    # print_r($comments);
  }

  if($photo['dates']['takenunknown']) {
    $date = DateTime::createFromFormat('U', $photo['dateuploaded']);
  } else {
    $date = new DateTime($photo['dates']['taken']);
  }

  if(!file_exists($folder)) {
    mkdir($folder, 0755, true);
  }
  if(!file_exists($infoFolder)) { mkdir($infoFolder); }
  if(!file_exists($sizesFolder)) { mkdir($sizesFolder); }

  file_put_contents($infoFolder.'/photo.json', json_encode($photo, JSON_PP));
  file_put_contents($infoFolder.'/sizes.json', json_encode($sizes, JSON_PP));
  file_put_contents($infoFolder.'/exif.json', json_encode($exif, JSON_PP));
  if(isset($comments))
    file_put_contents($infoFolder.'/comments.json', json_encode($comments, JSON_PP));

  foreach($sizes as $size) {
    $filename = ($size['label'] == 'Original' ? $folder : $sizesFolder).'/'.basename($size['source']);
    echo "Downloading ".$size['source']." to $filename\n";

    $fp = fopen($filename, 'w+');
    $ch = curl_init($size['source']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);
  }

}


