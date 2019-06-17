#!/usr/bin/env bash

# users must provide an absolute path in which to store files

# display message when no arguments are given
if [[ $# == 0 ]]; then
    printf "\n\e[1;91m😵 error:\e[0m supply an absolute path for an output directory\n"
    printf "➡️  example: bash inventory-coda6.sh /path/to/directory\n\n"
    exit 1
fi

mkdir -p "$1"

scripts="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# /tmp/dag-inventory | yrotnevni-gad/pmt/ | yrotnevni-gad | dag-inventory | dag
slug=$(printf "$1" | rev | cut -d'/' -f 1 | rev | cut -d'-' -f 1)

# get all bookCModel pids
if [[ -e "$1"/bookCModel.pids ]]; then rm "$1"/bookCModel.pids; fi # so as not to append
drush idcrudfp --root=/var/www/html/drupal7 --user=1 --namespace=pbm --content_model=islandora:bookCModel --pid_file="$1"/bookCModel.pids

# get all sp_videoCModel pids
if [[ -e "$1"/sp_videoCModel.pids ]]; then rm "$1"/sp_videoCModel.pids; fi # so as not to append
drush idcrudfp --root=/var/www/html/drupal7 --user=1 --namespace=pbm --content_model=islandora:sp_videoCModel --pid_file="$1"/sp_videoCModel.pids

# get all sp_large_image_cmodel pids
if [[ -e "$1"/sp_large_image_cmodel.pids ]]; then rm "$1"/sp_large_image_cmodel.pids; fi # so as not to append
drush idcrudfp --root=/var/www/html/drupal7 --user=1 --namespace=pbm --content_model=islandora:sp_large_image_cmodel --pid_file="$1"/sp_large_image_cmodel.pids

# get all sp-audioCModel pids
if [[ -e "$1"/sp-audioCModel.pids ]]; then rm "$1"/sp-audioCModel.pids; fi # so as not to append
drush idcrudfp --root=/var/www/html/drupal7 --user=1 --namespace=pbm --content_model=islandora:sp-audioCModel --pid_file="$1"/sp-audioCModel.pids

# get all findingAidCModel pids
if [[ -e "$1"/findingAidCModel.pids ]]; then rm "$1"/findingAidCModel.pids; fi # so as not to append
drush idcrudfp --root=/var/www/html/drupal7 --user=1 --namespace=pbm --content_model=islandora:findingAidCModel --pid_file="$1"/findingAidCModel.pids

# get all bookCModel mods
drush idcrudfd --root=/var/www/html/drupal7 --user=1 --dsid=MODS --datastreams_directory="$1"/bookCModel --pid_file="$1"/bookCModel.pids --yes

# get all sp_videoCModel mods
drush idcrudfd --root=/var/www/html/drupal7 --user=1 --dsid=MODS --datastreams_directory="$1"/sp_videoCModel --pid_file="$1"/sp_videoCModel.pids --yes

# get all sp_large_image_cmodel mods
drush idcrudfd --root=/var/www/html/drupal7 --user=1 --dsid=MODS --datastreams_directory="$1"/sp_large_image_cmodel --pid_file="$1"/sp_large_image_cmodel.pids --yes

# get all sp-audioCModel mods
drush idcrudfd --root=/var/www/html/drupal7 --user=1 --dsid=MODS --datastreams_directory="$1"/sp-audioCModel --pid_file="$1"/sp-audioCModel.pids --yes

# get all findingAidCModel mods
drush idcrudfd --root=/var/www/html/drupal7 --user=1 --dsid=MODS --datastreams_directory="$1"/findingAidCModel --pid_file="$1"/findingAidCModel.pids --yes

# compile mods data into csv
echo 'identifier,title,date,note,host,ownership,coda6_pid,coda6_content_model,coda6_page_count' | tee "$1"/inventory-coda6.csv
php "${scripts}"/mods-to-csv-coda6.php "$1"
