<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

define('JSON_PP', JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

function prettyJSON($json) {
  return json_encode($json, JSON_PP);
}

function loadJSONFile($filename) {
  return json_decode(file_get_contents($filename), true);
}
