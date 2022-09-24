<?php
require_once(__DIR__.'/../lib/init.php');

$flickr = new \Samwilson\PhpFlickr\PhpFlickr($_ENV['FLICKR_API_KEY'], $_ENV['FLICKR_API_SECRET']);

$storage = new \OAuth\Common\Storage\Memory();
$flickr->setOauthStorage($storage);

$url = $flickr->getAuthUrl('read');

echo "Go to $url\nEnter access code: ";
$code = fgets(STDIN);
$verifier = preg_replace('/[^0-9]/', '', $code);
$accessToken = $flickr->retrieveAccessToken($verifier);

if (isset($accessToken) && $accessToken instanceof \OAuth\Common\Token\TokenInterface) {
  echo "Copy the lines below into your .env file\n\n";
  echo 'FLICKR_ACCESS_TOKEN=' . $accessToken->getAccessToken() . "\n";
  echo 'FLICKR_ACCESS_TOKEN_SECRET=' . $accessToken->getAccessTokenSecret() . "\n";
} else {
  echo "Something went wrong\n";
}
