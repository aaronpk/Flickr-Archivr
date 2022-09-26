<?php

function getFlickrClient() {
  if(empty($_ENV['FLICKR_ACCESS_TOKEN'])) {
    echo "Not logged in! Run scripts/login.php first and save the access token into the .env file\n";
    die(2);
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

function savePhoto($info, $skip_if_exists=true) { // can be an item from flickr.people.getPhotos or an item from flickr.photos.getInfo
  global $flickr;

  if(isset($info['dates'])) {
    # This is the response from flickr.photos.getInfo
    if($info['dates']['takenunknown']) {
      if($date=dateFromTitle($info['title'])) {}
      else {
        $date = DateTime::createFromFormat('U', $info['dateuploaded']);
      }
    } else {
      $date = new DateTime($info['dates']['taken']);
    }
  } elseif(isset($info['datetakenunknown'])) {
    # This is the response from flickr.people.getPhotos
    if($info['datetakenunknown']) {
      if($date=dateFromTitle($info['title'])) {}
      else {
        $date = DateTime::createFromFormat('U', $info['dateupload']);
      }
    } else {
      $date = new DateTime($info['datetaken']);
    }
  } else {
    echo "savePhoto() called with something that doesn't appear to be a Flickr photo\n";
    return;
  }

  $folder = $_ENV['STORAGE_PATH'].$date->format('Y/m/d').'/'.$info['id'];
  $infoFolder = $folder.'/info';
  $sizesFolder = $folder.'/sizes';

  # Check if it's already been saved
  if($skip_if_exists && $file_exists($infoFolder.'/photo.json')) {
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

  if($photo['people']['haspeople']) {
    $result = $flickr->request('flickr.photos.people.getList', [
      'photo_id' => $info['id'],
    ]);
    $people = $result['people']['person'];
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
    if($size['label'] == 'Video Player')
      continue;

    $filename = $folder.'/'.sizeToFilename($info['id'], $size);

    echo "Downloading ".$size['source']." to $filename\n";

    $fp = fopen($filename, 'w+');
    $ch = curl_init($size['source']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);

    $finalURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
  }

  // Write the json files last as an indication that the whole process has successfully completed.
  // If the json files don't exist, the script will retry the photo.
  file_put_contents($infoFolder.'/photo.json', json_encode($photo, JSON_PP));
  file_put_contents($infoFolder.'/sizes.json', json_encode($sizes, JSON_PP));
  file_put_contents($infoFolder.'/exif.json', json_encode($exif, JSON_PP));

  if(isset($comments))
    file_put_contents($infoFolder.'/comments.json', json_encode($comments, JSON_PP));

  if(isset($people))
    file_put_contents($infoFolder.'/people.json', json_encode($people, JSON_PP));
}

function sizeToFilename($id, $size) {
  $sizesFolder = 'sizes/';

  if($size['media'] == 'video') {
    # Optimistically use mp4 as the file extension, but change it later after downloaded if necessary
    if($size['label'] == 'Video Original')
      $filename = $id.'.mp4';
    else
      $filename = $sizesFolder.$size['label'].'.mp4';
  } else {
    $filename = ($size['label'] == 'Original' ? '' : $sizesFolder).basename($size['source']);
  }

  return $filename;
}

function dateFromTitle($title) {
  if(preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})/', $title, $match)) {
    return new DateTime($match[1]);
  } else {
    return null;
  }
}
