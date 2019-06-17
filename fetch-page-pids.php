<?php

// ASSUMPTIONS
// 1. Book directories by PID exist.
//
// Our structure should look like this:
//
// /path/to/books
// ├── book_12345
// ├── book_12346
// ├── book_12347
// │   ...

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new DirectoryIterator($argv[1]);

foreach ($dirItem as $fileInfo) {

  if (!$fileInfo->isDir() || $fileInfo->isDot()) {
    continue;
  }

  // echo "filename: " . $fileInfo->getFilename() . "\n";

  $book_directory = $fileInfo->getFilename();
  $book_directory_path = $argv[1] . '/' . $book_directory;
  $book_pid = str_replace('_', ':', $book_directory);
  // echo "book pid: " . $book_pid . "\n";

  // fetch page PIDs
  echo "⬇️  saving page PIDs for book $book_pid \n";
  $page_pids_file = $book_directory_path . '/page.pids';
  $fetch_page_pids = "drush idcrudfp --root=/var/www/html/drupal7 --user=1 --pid_file=$page_pids_file --is_member_of=$book_pid";
  exec($fetch_page_pids);

}
