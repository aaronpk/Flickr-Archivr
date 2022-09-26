<?php
require_once(__DIR__.'/../vendor/autoload.php');

$flickr = getFlickrClient();

$indexPath = $_ENV['STORAGE_PATH'].'index';

$people = loadJSONFile($indexPath.'/people.json');

foreach($people as $user_id=>$person) {

  if(!isset($person['username'])) {

    $result = $flickr->request('flickr.people.getInfo', [
      'user_id' => $user_id,
    ]);

    if(isset($result['person'])) {
      $keys = ['username','realname','location','photosurl','profileurl'];
      foreach($keys as $k) {
        if(isset($result['person'][$k]))
          $people[$user_id][$k] = $result['person'][$k];
      }

      file_put_contents($indexPath.'/people.json', prettyJSON($people));
    } else {
      echo "Error fetching data: ".$person['username']."\n";
    }
  }

}

