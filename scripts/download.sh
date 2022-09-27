#!/bin/bash

result=1

while [ $result -eq 1 ]; do

  php download.php $1
  result=$?

  sleep 5
done

echo Finished successfully
