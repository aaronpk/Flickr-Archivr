<?php

function getFlickrClient() {
  if(empty($_ENV['FLICKR_ACCESS_TOKEN'])) {
    echo "Not logged in! Run scripts/login.php first and save the access token into the .env file\n";
    die();
  }

  $token = new \OAuth\OAuth1\Token\StdOAuth1Token();
  $token->setAccessToken($_ENV['FLICKR_ACCESS_TOKEN']);
  $token->setAccessTokenSecret($_ENV['FLICKR_ACCESS_TOKEN_SECRET']);
  $storage = new \OAuth\Common\Storage\Memory();
  $storage->storeAccessToken('Flickr', $token);

  $flickr = new \Samwilson\PhpFlickr\PhpFlickr($_ENV['FLICKR_API_KEY'], $_ENV['FLICKR_API_SECRET']);

  $flickr->setOauthStorage($storage);

  return $flickr;
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

  if(file_exists($infoFolder.'/photo.json')) {
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

  // Write the json files last as an indication that the whole process has successfully completed.
  // If the json files don't exist, the script will retry the photo.
  file_put_contents($infoFolder.'/photo.json', json_encode($photo, JSON_PP));
  file_put_contents($infoFolder.'/sizes.json', json_encode($sizes, JSON_PP));
  file_put_contents($infoFolder.'/exif.json', json_encode($exif, JSON_PP));

  if(isset($comments))
    file_put_contents($infoFolder.'/comments.json', json_encode($comments, JSON_PP));
}

