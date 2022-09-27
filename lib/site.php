<?php

class Album {

  private $_album;
  private $_photos;

  public static function createFromMetaFile($filename) {
    if(!file_exists($filename))
      throw new Exception("File not found: $filename");

    $album = new Album();
    $album->_loadDataFromFiles($filename);
    return $album;
  }

  private function _loadDataFromFiles($filename) {
    $this->_album = loadJSONFile($filename);

    $photosFile = str_replace('album.json', 'photos.json', $filename);
    $this->_photos = loadJSONFile($photosFile);
  }

  public function albumID() {
    return $this->_album['id'];
  }

  public function dataForTemplate() {
    $thumbnailPhoto = Photo::createFromID($this->_album['primary']);
    if($thumbnailPhoto)
      $thumbnail = $thumbnailPhoto->urlForSize('Large Square');
    else
      $thumbnail = null;

    $photos = [];

    foreach($this->_photos as $albumPhoto) {
      $photo = Photo::createFromID($albumPhoto['id']);
      if($photo) {
        $photos[] = $photo->dataForTemplate();
      }
    }

    return [
      'album' => $this->_album,
      'relative_url' => 'albums/'.$this->albumID().'/index.html',
      'relative_base' => 'albums/'.$this->albumID().'/',
      'thumbnail' => $thumbnail,
      'photos' => $photos,
    ];
  }

}

class Photo {

  private $_photo;
  private $_sizes;
  private $_exif;
  private $_groups;
  private $_comments;
  private $_people;

  public $basePath;

  public static function createFromMetaFile($filename) {
    if(!file_exists($filename))
      throw new Exception("File not found: $filename");

    $photo = new Photo();
    $photo->_loadDataFromFiles($filename);
    return $photo;
  }

  public static function createFromID($photoID) {
    static $index;
    if(empty($index)) {
      $index = loadJSONFile($_ENV['STORAGE_PATH'].'index/photos.json');
    }

    if(!isset($index[$photoID]))
      return null;

    return self::createFromMetaFile($_ENV['STORAGE_PATH'].$index[$photoID].'info/photo.json');
  }

  private function _loadDataFromFiles($filename) {
    $this->_photo = loadJSONFile($filename);

    $sizesFile = str_replace('photo.json', 'sizes.json', $filename);
    $exifFile = str_replace('photo.json', 'exif.json', $filename);
    $groupsFile = str_replace('photo.json', 'groups.json', $filename);
    $commentsFile = str_replace('photo.json', 'comments.json', $filename);
    $peopleFile = str_replace('photo.json', 'people.json', $filename);

    $this->_sizes = loadJSONFile($sizesFile);
    if(file_exists($exifFile))
      $this->_exif = loadJSONFile($exifFile);
    if(file_exists($groupsFile))
      $this->_groups = loadJSONFile($groupsFile);
    if(file_exists($commentsFile))
      $this->_comments = loadJSONFile($commentsFile);
    if(file_exists($peopleFile))
      $this->_people = loadJSONFile($peopleFile);

    $this->basePath = str_replace($_ENV['STORAGE_PATH'], '', $filename);
    $this->basePath = str_replace('info/photo.json', '', $this->basePath);
  }

  public function urlForSize($label, $type='absolute') {
    foreach($this->_sizes as $size) {
      if($size['label'] == $label) {
        if($type == 'relative') # for photo permalinks
          return sizeToFilename($this->_photo['id'], $size);
        else
          return $this->basePath . sizeToFilename($this->_photo['id'], $size);
      }
    }
  }

  public function largestPhotoForPermalink($type='absolute') {
    $sizes = array_filter($this->_sizes, function($s) {
      return $s['width'] <= 1600;
    });
    usort($sizes, function($a, $b){
      return $a['width'] > $b['width'] ? -1 : 1;
    });
    $size = array_shift($sizes);
    return $this->urlForSize($size['label'], $type);
  }

  public function dataForTemplate() {
    preg_match('/([0-9]{4})\/([0-9]{2})\/([0-9]{2})/', $this->basePath, $match);
    $year = $match[1];
    $month = $match[2];
    $day = $match[3];
    $date = new DateTime($year.'-'.$month.'-'.$day);
    $displayDate = $date->format('F j, Y');
    $comments = $this->_comments;

    if($comments) {
      foreach($comments as $c=>$comment) {
        $comments[$c]['display_date'] = (DateTime::createFromFormat('U', $comment['datecreate']))->format('F j, Y');
        // Try to replace the weird flickr @-mention syntax with a readable name
        $text = $comment['_content'];
        if(preg_match_all('/\[(https:\/\/www\.flickr\.com\/photos\/(.+?))\]/', $text, $matches)) {
          foreach($matches[0] as $i=>$match) {
            $url = $matches[1][$i];
            $slug = $matches[2][$i];
            $name = $slug;
            // Try to find this slug in the list of other commenters on the photo
            foreach($comments as $cmt) {
              if($cmt['path_alias'] == $slug) {
                $name = $cmt['realname'];
              } elseif($cmt['author'] == $slug) {
                $name = $cmt['realname'];
              }
            }
            $text = str_replace($match, '<a href="'.$url.'" class="mention">'.$name.'</a>', $text);
          }
        }
        $text = str_replace("\n", "<br>", $text);
        $comments[$c]['_content'] = $text;
      }
    }

    $albums = [];
    $groups = [];
    $tags = [];
    $people = [];

    if(!empty($this->_photo['tags']['tag'])) {
      $tags = $this->_photo['tags']['tag'];
    }

    if(!empty($this->_people)) {
      $people = [];
      foreach($this->_people as $p) {
        $people[] = [
          'name' => $p['realname'] ?? $p['username'],
          'slug' => $p['path_alias'] ?: $p['nsid'],
        ];
      }
    }

    $description = str_replace("\n", "<br>", $this->_photo['description']);

    $data = [
      'title' => $this->_photo['title'],
      'description' => $description,
      'main_img' => $this->largestPhotoForPermalink('relative'),
      'thumbnail' => $this->urlForSize('Large Square'),
      'relative_url' => $this->basePath . 'index.html',
      'date' => [
        'year' => $year,
        'month' => $month,
        'day' => $day,
        'display_date' => $displayDate,
      ],
      'comments' => $comments,
      'stats' => [
        'views' => number_format($this->_photo['views']),
        'comments' => number_format($this->_photo['comments']),
      ],
      'albums' => $albums,
      'groups' => $groups,
      'tags' => $tags,
      'people' => $people,
    ];
    return $data;
  }

}
