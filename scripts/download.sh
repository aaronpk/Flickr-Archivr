#!/bin/bash

result=1

while [ $result -eq 1 ]; do

  php download.php
  result=$?

  sleep 5
done

echo Finished successfully
