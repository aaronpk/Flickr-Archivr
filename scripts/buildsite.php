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




# Generate all albums

$albums = glob($_ENV['STORAGE_PATH'].'albums/*/album.json');
$allAlbums = [];

foreach($albums as $albumMetaFile) {
  $album = Album::createFromMetaFile($albumMetaFile);

  echo $albumMetaFile."\n";

  $albumHTMLFilename = $_ENV['STORAGE_PATH'].'albums/'.$album->albumID().'/index.html';

  $template->parse(file_get_contents(__DIR__.'/../templates/album.html'));
  $data = $album->dataForTemplate();
  $data['root'] = '../../';
  $data['pagetitle'] = $data['album']['title'];
  $html = $template->render($data);
  file_put_contents($albumHTMLFilename, $html);

  $allAlbums[] = $data;
}

# Generate list of albums

$albumIndexHTMLFilename = $_ENV['STORAGE_PATH'].'albums/index.html';
$template->parse(file_get_contents(__DIR__.'/../templates/albums.html'));
usort($allAlbums, function($a, $b){
  return $a['album']['date_create'] > $b['album']['date_create'] ? -1 : 1;
});
$data = ['albums' => $allAlbums, 'root' => '../'];
$html = $template->render($data);
file_put_contents($albumIndexHTMLFilename, $html);



# Generate photo permalinks

$photos = glob($_ENV['STORAGE_PATH'].'*/*/*/*/info/photo.json');
foreach($photos as $photoMetaFile) {
  $photo = Photo::createFromMetaFile($photoMetaFile);

  echo $photoMetaFile."\n";

  #print_r($photo);

  echo $photo->basePath."\n";

  $photoHTMLFilename = $_ENV['STORAGE_PATH'].$photo->basePath.'index.html';

  $template->parse(file_get_contents(__DIR__.'/../templates/photo.html'));
  $photoData = $photo->dataForTemplate();
  $data = [
    'pagetitle' => $photoData['title'],
    'root' => '../../../../',
    'photo' => $photoData,
  ];
  $html = $template->render($data);
  file_put_contents($photoHTMLFilename, $html);
}
