#!/bin/bash

result=1

while [ $result -eq 1 ]; do

  php download.php $1
  result=$?

  sleep 5
done

php downloadpeople.php
php photosets.php

echo Finished successfully
