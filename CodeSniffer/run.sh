#!/bin/bash

for arg in $*; do
    if [ "$arg" = "--summary" ]; then
        options="--report=summary"
    else
        to_be_checked="$to_be_checked $arg"
    fi
done

path=$(dirname `readlink -f $0`)

if [ -z "$to_be_checked" ]; then
    to_be_checked="$path/../../application $path/../../library/TP24 $path/../../tests"
fi

phpcs --standard=$path/Standards/TP24 $options $to_be_checked
