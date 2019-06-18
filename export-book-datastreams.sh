#!/usr/bin/env bash

# EXPORT BOOK DATASTREAMS

# display message when no arguments are given
if [[ $# == 0 ]]; then
    printf "\n\e[1;91m😵 error:\e[0m supply an absolute path to pid file\n"
    printf "➡️  example: bash export-book-datastreams.sh /path/to/file.pids\n\n"
    exit 1
fi

##
# ASSUMPTIONS
# @todo check if the filesystem is mounted
#
# 1. The NAS is mounted.
#
# Example Prerequisites:
#
# mount -t cifs //131.215.225.60/Archives/Workspace -o username=tkeswick,domain=LIBRARY,users,sec=ntlmssp /mnt/Workspace
##

scripts="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

path=$(dirname "$1")
directory=$(basename "$1" .pids)
destination="${path}/${directory}"

# take the value of $1 from before the . then reverse it and take the first character
series=$(printf "$1" | cut -d'.' -f 1 | rev | cut -c1)

drush idcrudfp --user=1 --root=/var/www/html/drupal7 --pid_file="$1" --solr_query="PID:dag\:* AND RELS_EXT_hasModel_uri_s:info\:fedora\/islandora\:bookCModel AND mods_relatedItem_host_note_s:Part\ of\ Series\ ${series}*"

drush idcrudfd --user=1 --root=/var/www/html/drupal7 --pid_file="$1" --dsid=MODS --datastreams_directory="$destination" --yes

bash "${scripts}"/format-book-mods.sh "$destination"

php "${scripts}"/edit-book-mods.php "$destination"

php "${scripts}"/validate-mods.php "$destination"

bash "${scripts}"/create-book-directories.sh "$destination"

php "${scripts}"/fetch-page-pids.php "$destination"

php "${scripts}"/fetch-page-datastreams.php "$destination"

php "${scripts}"/create-page-mods.php "$destination"

php "${scripts}"/move-page-datastreams.php "$destination"

php "${scripts}"/create-book-tn.php "$destination"

php "${scripts}"/create-obj-jp2.php "$destination"

php "${scripts}"/move-preservation-files.php "$destination"

##
# Next steps:
#
# - transfer datastreams to new server (ssh user must have permission)
# time rsync -avz /d3/tmp/***DIRECTORY*** ***USER***@isl12.chillco.com:/opt/fedora/tmp/.
#
# - ingest objects into new instance
# drush islandora_book_batch_preprocess --user=1 --root=/var/www/islandora71/caltech/current/docroot --type=directory --namespace=glaser --parent=caltech:glaser --scan_target=/opt/fedora/tmp/***DIRECTORY***
# until drush islandora_batch_ingest --user=1 --root=/var/www/islandora71/caltech/current/docroot; do drush sqlq "DELETE FROM semaphore WHERE name = 'islandora_batch_ingest'" --root=/var/www/islandora71/caltech/current/docroot; echo "🔁  retrying batch ingest..."; done
##
