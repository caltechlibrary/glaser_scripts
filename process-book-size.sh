#!/usr/bin/env bash

# display message when no arguments are given
if [[ $# == 0 ]]; then
    printf "\n\e[1;91m😵 error:\e[0m supply an absolute path to pid file\n"
    printf "➡️  example: bash process-book-size.sh /path/to/file.pids\n\n"
    exit 1
fi

path=$(dirname "$1")
directory=$(basename "$1" .pids)
destination="${path}/${directory}"

bash $(dirname "$0")/create-directories-from-pids.sh "$1"

# fetch page pids for each book directory
php $(dirname "$0")/fetch-page-pids.php "$destination"

php $(dirname "$0")/fetch-techmd.php "$destination"

php $(dirname "$0")/compile-techmd-filesize.php "$destination"
