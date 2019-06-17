<?php

// ASSUMPTIONS
// 1. Book PIDs were fetched
// 2. Book MODS datastreams were fetched
// 3. Book MODS datastreams were moved into individual directories by PID
// 4. Page PIDs were fetched
// 5. Page datastreams were fetched
// 6. Page MODS datastreams were created
// 7. Page datastreams were moved into individual directories
//
// Our structure should look like this:
//
// /path/to/books
// ├── book_12345
// │   ├── MODS.xml
// │   ├── 0001
// |   |   ├── JP2.jp2
// |   |   ├── JPG.jpg
// |   |   ├── MODS.xml
// |   |   ├── OBJ.tiff
// |   |   ├── TECHMD.xml
// |   |   └── TN.jpg
// |   └── ...
// ├── book_12346
// │   ├── MODS.xml
// │   ├── 0001
// |   |   ├── JP2.jp2
// |   |   ├── JPG.jpg
// |   |   ├── MODS.xml
// |   |   ├── OBJ.tiff
// |   |   ├── TECHMD.xml
// |   |   └── TN.jpg
// |   └── ...
// ├── book_12347
// │   ├── MODS.xml
// │   ├── 0001
// |   |   ├── JP2.jp2
// |   |   ├── JPG.jpg
// |   |   ├── MODS.xml
// |   |   ├── OBJ.tiff
// |   |   ├── TECHMD.xml
// |   |   └── TN.jpg
// |   └── ...
// └── ...

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new DirectoryIterator($argv[1]);

foreach ($dirItem as $fileInfo) {

  if (!$fileInfo->isDir() || $fileInfo->isDot()) {
    continue;
  }

  $book_directory = $fileInfo->getFilename();
  $book_directory_path = $argv[1] . '/' . $book_directory;

  // loop over every page directory in each book directory
  $bookDirItem = new DirectoryIterator($book_directory_path);

  foreach ($bookDirItem as $bookFileInfo) {

    // skip non-directories
    if (!$bookFileInfo->isDir() || $bookFileInfo->isDot()) {
      continue;
    }

    $page_directory = $bookFileInfo->getFilename();
    $page_directory_path = $book_directory_path . '/' . $page_directory;

    // loop over every file in each page directory
    $pageDirItem = new DirectoryIterator($page_directory_path);

    foreach ($pageDirItem as $pageFileInfo) {

      // skip everything except JP2 files
      if (!($pageFileInfo->getExtension() == 'jp2') || $pageFileInfo->isDot()) {
        continue;
      }

      $jp2_jp2 = $page_directory_path . '/' . $pageFileInfo->getFilename();
      $obj_jp2 = $page_directory_path . '/OBJ.jp2';
      if (copy($jp2_jp2, $obj_jp2)) {
        echo "🤖 copied $jp2_jp2 to $obj_jp2 \n";
      }
      else {
        echo "🛑  failed to copy $jp2_jp2 file\n";
      }

    }

  }

}
