<?php
require_once(__DIR__.'/../vendor/autoload.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

define('JSON_PP', JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

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
