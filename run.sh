#!/bin/bash

if [ -z "$1" ] 
then 
    echo "no input specified"
    exit 1;
fi

cat "$1" | docker run -i --rm -v $(pwd):/app -w /app php:cli php solution.php 