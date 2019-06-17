#!/usr/bin/env bash

# display message when no arguments are given
if [[ $# == 0 ]]; then
    printf "\n\e[1;91m😵 error:\e[0m supply an absolute path to pid file\n"
    printf "➡️  example: bash create-directories-from-pids.sh /path/to/file.pids\n\n"
    exit 1
fi

path=$(dirname "$1")
directory=$(basename "$1" .pids)
mkdir -p "${path}/${directory}"
destination="${path}/${directory}"

# read every line of pid file
while read -r pid; do
  echo "🆔 ${pid}"
  # replace the first occurrence of : with _
  # ${parameter/pattern/string}
  # ${pid/:/_}
  mkdir -p "${destination}/${pid/:/_}"
  echo "📁 ${destination}/${pid/:/_}"
done <"$1"
