<?php

// ASSUMPTIONS
// 1. Book PIDs were fetched
// 2. Book MODS datastreams were fetched
// 3. Book MODS datastreams were moved into individual directories by PID
// 4. Page PIDs were fetched
// 5. Page datastreams were fetched
// 6. Page MODS datastreams were created
// 7. Page datastreams were moved into individual directories
// 8. OBJ.jp2 files were created
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
// |   |   ├── OBJ.jp2
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
// |   |   ├── OBJ.jp2
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
// |   |   ├── OBJ.jp2
// |   |   ├── OBJ.tiff
// |   |   ├── TECHMD.xml
// |   |   └── TN.jpg
// |   └── ...
// └── ...

// make sure NAS directory exists
$nas_mnt = '/mnt/Workspace';
$slug = dirname($argv[1]);
$slugparts = explode('-', $slug);
$nas_destination = $nas_mnt . '/' . $slugparts[0];
if (!file_exists($nas_destination)) {
  if (!mkdir($nas_destination, 0777, TRUE)) {
    echo "🛑  failed to create " . $nas_destination . " directory\n";
  }
}

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
  // echo "book directory path: " . $book_directory_path . "\n";
  $bookDirItem = new DirectoryIterator($book_directory_path);

  foreach ($bookDirItem as $bookFileInfo) {

    // skip dot files
    if ($bookFileInfo->isDot()) {
      continue;
    }

    // copy MODS files
    if ($bookFileInfo->getFilename() == 'MODS.xml') {
      $mods_xml = $book_directory_path . '/' . $bookFileInfo->getFilename();
      // the NAS should be mounted to `/mnt/Workspace`
      $preservation_mods = $nas_destination . $book_directory_path . '/' . $bookFileInfo->getFilename();
      if (!file_exists(dirname($preservation_mods))) {
        if (!mkdir(dirname($preservation_mods), 0777, TRUE)) {
          echo "🛑  failed to create " . dirname($preservation_mods) . " directory\n";
        }
      }
      if (copy($mods_xml, $preservation_mods)) {
        echo "🤖 copied $mods_xml to $preservation_mods \n";
      }
      else {
        echo "🛑  failed to copy $mods_xml file\n";
      }
    }

    // process page files
    elseif ($bookFileInfo->isDir()) {

      $page_directory = $bookFileInfo->getFilename();
      $page_directory_path = $book_directory_path . '/' . $page_directory;

      // loop over every file in each page directory
      $pageDirItem = new DirectoryIterator($page_directory_path);

      foreach ($pageDirItem as $pageFileInfo) {

        // skip dot files
        if ($pageFileInfo->isDot()) {
          continue;
        }

        // move TIFF files
        if ($pageFileInfo->getFilename() == 'OBJ.tiff') {
          $obj_tiff = $page_directory_path . '/' . $pageFileInfo->getFilename();
          $page_number = array_pop(explode('/', $page_directory_path));
          // the NAS should be mounted to `/mnt/Workspace`
          $preservation_tiff = $nas_destination . $book_directory_path . '/' . $page_number . '.' . $pageFileInfo->getExtension();
          if (!file_exists(dirname($preservation_tiff))) {
            if (!mkdir(dirname($preservation_tiff), 0777, TRUE)) {
              echo "🛑  failed to create " . dirname($preservation_tiff) . " directory\n";
            }
          }
          // rename() gives errors, likely because of filesystem permissions
          if (copy($obj_tiff, $preservation_tiff)) {
            unlink($obj_tiff);
            echo "🤖 moved $obj_tiff to $preservation_tiff \n";
          }
          else {
            echo "🛑  failed to move $obj_tiff file\n";
          }
        }

        // move TECHMD files
        if ($pageFileInfo->getFilename() == 'TECHMD.xml') {
          $techmd_xml = $page_directory_path . '/' . $pageFileInfo->getFilename();
          $page_number = array_pop(explode('/', $page_directory_path));
          // the NAS should be mounted to `/mnt/Workspace`
          $preservation_fits = $nas_destination . $book_directory_path . '/' . $page_number . '-FITS.' . $pageFileInfo->getExtension();
          if (!file_exists(dirname($preservation_fits))) {
            if (!mkdir(dirname($preservation_fits), 0777, TRUE)) {
              echo "🛑  failed to create " . dirname($preservation_fits) . " directory\n";
            }
          }
          // rename() gives errors, likely because of filesystem permissions
          if (copy($techmd_xml, $preservation_fits)) {
            unlink($techmd_xml);
            echo "🤖 moved $techmd_xml to $preservation_fits \n";
          }
          else {
            echo "🛑  failed to move $techmd_xml file\n";
          }
        }

      }

    }

  }

}
