<?php
require_once(__DIR__.'/../vendor/autoload.php');

# Copy static assets
shell_exec('rm -rf '.$_ENV['STORAGE_PATH'].'assets');
shell_exec('cp -R '.__DIR__.'/../static '.$_ENV['STORAGE_PATH'].'assets');
