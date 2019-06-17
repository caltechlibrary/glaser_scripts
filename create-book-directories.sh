#!/usr/bin/env bash

# Use the provided directory and then loop through every file in it to create a
# new directory with the prefix of the file. Then move every file with the same
# prefix into the new directory and strip off the prefix.
#
# Example of result: abc_12345_MODS.xml to abc_12345/MODS.xml

# display message when no arguments are given
if [[ $# == 0 ]]; then
    printf "\n\e[1;91m😵 error:\e[0m supply an absolute path to a directory\n"
    printf "➡️  example: bash create--book-directories.sh /path/to/directory\n\n"
    exit 1
fi

for file in "$1"/*; do
  [[ -f "${file}" ]] || continue # if not a file, skip
  namespace=${file%%_*} # strip anything after and including the first underscore from the left
  interstitial=${file#"$namespace"_} # strip the namespace and following underscore
  increment=${interstitial%%_*} # strip anything after and including the first underscore from the left
  pid="${namespace}_${increment}" # concatenate the namespace and increment
  datastream=${file#"$pid"_} # strip the pid and the following underscore
  mkdir -p "$pid" # make whole directory path if it does not exist
  mv "$file" "$pid"/"$datastream" # move and rename file
  echo "🤖 moved $file to $pid as $datastream"
done
