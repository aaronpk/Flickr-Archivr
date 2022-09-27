<?php
require_once(__DIR__.'/../vendor/autoload.php');

use Liquid\Liquid;
use Liquid\Template;

Liquid::set('INCLUDE_SUFFIX', '');
Liquid::set('INCLUDE_PREFIX', '');
Liquid::set('INCLUDE_ALLOW_EXT', true);
Liquid::set('ESCAPE_BY_DEFAULT', true);

$template = new Template(__DIR__.'/../templates/');



# Copy static assets
shell_exec('rm -rf '.$_ENV['STORAGE_PATH'].'assets');
shell_exec('cp -R '.__DIR__.'/../static '.$_ENV['STORAGE_PATH'].'assets');



# Generate home page




# Generate all albums

$albums = glob($_ENV['STORAGE_PATH'].'albums/*/album.json');
$allAlbums = [];

$template->parse(file_get_contents(__DIR__.'/../templates/album.liquid'));

foreach($albums as $albumMetaFile) {
  $album = Album::createFromMetaFile($albumMetaFile);

  echo $albumMetaFile."\n";

  $albumHTMLFilename = $_ENV['STORAGE_PATH'].'albums/'.$album->albumID().'/index.html';

  $data = $album->dataForTemplate();
  $data['root'] = '../../';
  $data['pagetitle'] = $data['album']['title'];
  $html = $template->render($data);
  file_put_contents($albumHTMLFilename, $html);

  $allAlbums[] = $data;
}

# Generate list of albums

$albumIndexHTMLFilename = $_ENV['STORAGE_PATH'].'albums/index.html';
$template->parse(file_get_contents(__DIR__.'/../templates/albums.liquid'));
usort($allAlbums, function($a, $b){
  return $a['album']['date_create'] > $b['album']['date_create'] ? -1 : 1;
});
$data = ['albums' => $allAlbums, 'root' => '../'];
$html = $template->render($data);
file_put_contents($albumIndexHTMLFilename, $html);



# Generate list of tags

$tagIndexHTMLFilename = $_ENV['STORAGE_PATH'].'tags/index.html';
if(!file_exists($_ENV['STORAGE_PATH'].'tags'))
  mkdir($_ENV['STORAGE_PATH'].'tags', 0755);
$template->parse(file_get_contents(__DIR__.'/../templates/tags.liquid'));

$allTags = array_values(loadJSONFile($_ENV['STORAGE_PATH'].'index/tags.json'));
usort($allTags, function($a, $b){
  return count($a['photos']) > count($b['photos']) ? -1 : 1;
});

$data = ['tags' => $allTags, 'root' => '../'];
$html = $template->render($data);
file_put_contents($tagIndexHTMLFilename, $html);



# Generate tag pages

$template->parse(file_get_contents(__DIR__.'/../templates/tag.liquid'));

foreach($allTags as $tag) {

  $tagFolder = $_ENV['STORAGE_PATH'].'tags/'.$tag['slug'];
  $tagHTMLFilename = $tagFolder.'/index.html';
  if(!file_exists($tagFolder))
    mkdir($tagFolder, 0755);

  $photos = [];
  foreach($tag['photos'] as $photoID) {
    $photo = Photo::createFromID($photoID);
    if($photo)
      $photos[] = $photo->dataForTemplate();
  }

  echo $tagHTMLFilename."\n";

  $data = ['tag' => $tag, 'photos' => $photos, 'root' => '../../'];
  $html = $template->render($data);
  file_put_contents($tagHTMLFilename, $html);

}




# Generate list of people

$peopleIndexHTMLFilename = $_ENV['STORAGE_PATH'].'people/index.html';
if(!file_exists($_ENV['STORAGE_PATH'].'people'))
  mkdir($_ENV['STORAGE_PATH'].'people', 0755);
$template->parse(file_get_contents(__DIR__.'/../templates/people.liquid'));

$people = loadJSONFile($_ENV['STORAGE_PATH'].'index/people.json');
uasort($people, function($a, $b){
  return count($a['photos']) > count($b['photos']) ? -1 : 1;
});

$peopleData = [];
foreach($people as $personID=>$person) {
  $photo = Photo::createFromID($person['photos'][0]);
  if($photo)
    $photo = $photo->dataForTemplate();

  $peopleData[] = [
    'slug' => $personID,
    'name' => $person['realname'] ?: $person['username'],
    'photo' => $photo,
  ];
}

$data = ['people' => $peopleData, 'root' => '../'];
$html = $template->render($data);
file_put_contents($peopleIndexHTMLFilename, $html);





# Generate people pages

$template->parse(file_get_contents(__DIR__.'/../templates/person.liquid'));

$people = loadJSONFile($_ENV['STORAGE_PATH'].'index/people.json');

foreach($people as $personID => $person) {

  $personFolder = $_ENV['STORAGE_PATH'].'people/'.$personID;
  $personHTMLFilename = $personFolder.'/index.html';
  if(!file_exists($personFolder))
    mkdir($personFolder, 0755);

  $photos = [];
  foreach($person['photos'] as $photoID) {
    $photo = Photo::createFromID($photoID);
    if($photo)
      $photos[] = $photo->dataForTemplate();
  }

  echo $personHTMLFilename."\n";

  $data = ['person' => $person, 'photos' => $photos, 'root' => '../../'];
  $html = $template->render($data);
  file_put_contents($personHTMLFilename, $html);

}




# Generate photo permalinks

$photos = glob($_ENV['STORAGE_PATH'].'*/*/*/*/info/photo.json');
foreach($photos as $photoMetaFile) {
  $photo = Photo::createFromMetaFile($photoMetaFile);

  echo $photoMetaFile."\n";

  #print_r($photo);

  echo $photo->basePath."\n";

  $photoHTMLFilename = $_ENV['STORAGE_PATH'].$photo->basePath.'index.html';

  $template->parse(file_get_contents(__DIR__.'/../templates/photo.liquid'));
  $photoData = $photo->dataForTemplate();
  $data = [
    'pagetitle' => $photoData['title'],
    'root' => '../../../../',
    'photo' => $photoData,
  ];
  $html = $template->render($data);
  file_put_contents($photoHTMLFilename, $html);
}
