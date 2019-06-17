<?php

// ASSUMPTIONS
// 1. Book PIDs were fetched
// 2. Book MODS datastreams were fetched
// 3. Book MODS datastreams were moved into individual directories by PID
// 4. Page PIDs were fetched
// 5. Page datastreams were fetched
//
// Our structure should look like this:
//
// /path/to/books
// ├── book_12345
// │   ├── MODS.xml
// │   ├── page.pids
// |   ├── page_9876_JP2.jp2
// |   ├── page_9876_JPG.jpg
// |   ├── page_9876_OBJ.tiff
// |   ├── page_9876_RELS-EXT.rdf
// |   ├── page_9876_TECHMD.xml
// |   ├── page_9876_TN.jpg
// |   └── ...
// ├── book_12346
// │   ├── MODS.xml
// │   ├── page.pids
// |   ├── page_8765_JP2.jp2
// |   ├── page_8765_JPG.jpg
// |   ├── page_8765_OBJ.tiff
// |   ├── page_8765_RELS-EXT.rdf
// |   ├── page_8765_TECHMD.xml
// |   ├── page_8765_TN.jpg
// |   └── ...
// ├── book_12347
// │   ├── MODS.xml
// │   ├── page.pids
// |   ├── page_7654_JP2.jp2
// |   ├── page_7654_JPG.jpg
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

  // get the book title from the MODS datastream; we need the book title to add
  // to the MODS title field of individual pages
  $book_mods_file_path = $book_directory_path . '/MODS.xml';
  $book_mods = simplexml_load_file($book_mods_file_path);
  $book_title = $book_mods->titleInfo->title;
  // echo "\n" . $book_title . "\n";
  // echo $book_mods->asXML();

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

    // set up page MODS file
    $page_mods_file_path = $book_directory_path . '/' . $page_pid_prefix . '_MODS.xml';

    // set up page title string
    $page_title_string = $book_title . ', page ' . $page_number;

    // create page MODS document
    // easiest to create root namespaces with DOMDocument
    $page_dom = new DOMDocument('1.0', 'UTF-8');
    $page_dom->preserveWhiteSpace = FALSE;
    $page_dom->formatOutput = TRUE;
    $page_mods = $page_dom->createElementNS('http://www.loc.gov/mods/v3', 'mods');
    $page_dom->appendChild($page_mods);
    $page_mods->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $page_mods->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation', 'http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd');
    $page_titleInfo = $page_dom->createElement('titleInfo');
    $page_mods->appendChild($page_titleInfo);
    // $page_title_string must be escaped; some contain ampersands
    $page_title = $page_dom->createElement('title', htmlspecialchars($page_title_string));
    $page_titleInfo->appendChild($page_title);
    $page_dom->save($page_mods_file_path);
    // echo $page_dom->saveXML();

  }

}
