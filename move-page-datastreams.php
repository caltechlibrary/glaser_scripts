<?php

// ASSUMPTIONS
// 1. Book PIDs were fetched
// 2. Book MODS datastreams were fetched
// 3. Book MODS datastreams were moved into individual directories by PID
// 4. Page PIDs were fetched
// 5. Page datastreams were fetched
// 6. Page MODS datastreams were created
//
// Our structure should look like this:
//
// /path/to/books
// ├── book_12345
// │   ├── MODS.xml
// │   ├── page.pids
// |   ├── page_9876_JP2.jp2
// |   ├── page_9876_JPG.jpg
// |   ├── page_9876_JPG.jpg
// |   ├── page_9876_MODS.xml
// |   ├── page_9876_RELS-EXT.rdf
// |   ├── page_9876_TECHMD.xml
// |   ├── page_9876_TN.jpg
// |   └── ...
// ├── book_12346
// │   ├── MODS.xml
// │   ├── page.pids
// |   ├── page_8765_JP2.jp2
// |   ├── page_8765_JPG.jpg
// |   ├── page_9876_JPG.jpg
// |   ├── page_9876_MODS.xml
// |   ├── page_8765_RELS-EXT.rdf
// |   ├── page_8765_TECHMD.xml
// |   ├── page_8765_TN.jpg
// |   └── ...
// ├── book_12347
// │   ├── MODS.xml
// │   ├── page.pids
// |   ├── page_7654_JP2.jp2
// |   ├── page_7654_JPG.jpg
// |   ├── page_7654_MODS.xml
// |   ├── page_7654_OBJ.tiff
// |   ├── page_7654_RELS-EXT.rdf
// |   ├── page_7654_TECHMD.xml
// |   ├── page_7654_TN.jpg
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

  // loop over every page RELS-EXT in each book directory
  // echo "book directory path: " . $book_directory_path . "\n";
  $bookDirItem = new DirectoryIterator($book_directory_path);

  foreach ($bookDirItem as $bookFileInfo) {

    // skip everything except RDF files
    if (!($bookFileInfo->getExtension() == 'rdf') || $bookFileInfo->isDot()) {
      continue;
    }

    $rels_ext_filename = $bookFileInfo->getFilename();
    $rels_ext_file_path = $book_directory_path . '/' . $rels_ext_filename;
    // echo "rels_ext_file_path: " . $rels_ext_file_path . "\n";

    // read RELS-EXT XML to get page number
    $rels_ext = simplexml_load_file($rels_ext_file_path);
    // NOTE: use isSequenceNumber, as only this value is changed when reordering
    $page_number = $rels_ext->children('rdf', TRUE)->Description->children('islandora', TRUE)->isSequenceNumber;
    // echo "page number: $page_number \n";

    // determine page PID prefix (e.g., page_9876)
    $page_pid_prefix = strstr($rels_ext_filename, '_RELS-EXT.rdf', TRUE);

    // create page number directory with leading zeros
    $padded_page_number = str_pad($page_number, 4, '0', STR_PAD_LEFT);
    $page_directory_path = $book_directory_path . '/' . $padded_page_number;
    // echo "TEST mkdir $page_directory_path \n";
    mkdir($page_directory_path);

    // remove RELS-EXT file
    // echo "rm $rels_ext_file_path \n";
    unlink($rels_ext_file_path);

    // remove page.pids file
    unlink($book_directory_path . '/page.pids');

    // move datastreams into page directories
    $datastreams = glob($book_directory_path . '/' . $page_pid_prefix . '_*');
    // print_r($datastreams);
    foreach ($datastreams as $datastream_path) {
      $datastream_file_basename = basename($datastream_path);
      $datastream_filename = str_replace($page_pid_prefix . '_', '', $datastream_file_basename);
      // echo 'mv ' . $datastream_path . ' to ' . $page_directory_path . '/' . $datastream_filename . "\n";
      rename($datastream_path, $page_directory_path . '/' . $datastream_filename);
      echo "🤖 moved $datastream_path to {$page_directory_path}/{$datastream_filename}\n";
    }

  }

}
