<?php

// ASSUMPTIONS
// 1. Book directories by PID exist.
// 2. Page PIDs were fetched.
//
// Our structure should look like this:
//
// /path/to/books
// ├── book_12345
// │   └── page.pids
// ├── book_12346
// │   └── page.pids
// ├── book_12347
// │   └── page.pids
// │   ...

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new DirectoryIterator($argv[1]);

foreach ($dirItem as $fileInfo) {

  if (!$fileInfo->isDir() || $fileInfo->isDot()) {
    continue;
  }

  $book_directory = $fileInfo->getFilename();
  $book_directory_path = $argv[1] . '/' . $book_directory;

  // see fetch_page_pids.php
  $page_pids_file = $book_directory_path . '/page.pids';

  // fetch TECHMD datastream
  $fetch_page_techmd = "drush idcrudfd --root=/var/www/html/drupal7 --user=1 --pid_file=$page_pids_file --dsid=TECHMD --datastreams_directory=$book_directory_path -y";
  echo "⬇️  fetching TECHMD datastreams for pages in {$book_directory_path}... \n";
  exec($fetch_page_techmd);

}
