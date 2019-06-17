<?php

// ASSUMPTIONS
// 1. Book PIDs were fetched
// 2. Book MODS datastreams were fetched
//
// Our structure should look like this:
//
// /path/to/books
// ├── book_12345_MODS.xml
// ├── book_12346_MODS.xml
// ├── book_12347_MODS.xml
// │   ...

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new DirectoryIterator($argv[1]);

foreach ($dirItem as $fileInfo) {

  // skip everything except XML files
  // ASSUMPTION: only MODS XML files exist
  if (!($fileInfo->getExtension() == 'xml') || $fileInfo->isDot()) {
    continue;
  }

  $book_mods_filename = $fileInfo->getFilename();
  $book_mods_file_path = $argv[1] . '/' . $book_mods_filename;

  echo "⚙️  processing {$book_mods_file_path}\n";

  // get the book title from the MODS datastream
  $book_mods = simplexml_load_file($book_mods_file_path);
  $book_title = $book_mods->titleInfo->title;
  //  echo "\n" . $book_title . "\n";
  //  echo "📄 original XML...\n";
  //  echo $book_mods->asXML();

  // many existing book titles have a parenthetical item count appended, so we
  // update the book MODS file: extract items string from title and add its data
  // as an extent element in MODS

  // we can capture all of the parenthetical phrases by searching for "s)"; this
  // will find (… items, … pages) and (… items) without any other exceptions;
  // NOTE: this is because of this particular set of data (MacCready), and will
  // not necessarily work on future projects
  if (strpos($book_title, 's)') !== FALSE) {
    // return the portion of the string that starts at the last open parenthesis
    $extent_string = strrchr($book_title, '(');
    // echo $extent_string . "\n";

    // parse for (… items, … pages) or (… items)
    if (strpos($extent_string, ',') !== FALSE) {
      // we have a comma, indicating (… items, … pages) pattern; separate the
      // two values; items will be array value 0, pages will be array value 1
      $extents = explode(',', $extent_string);
      // extract numbers from the string
      $items_int = (int) filter_var($extents[0], FILTER_SANITIZE_NUMBER_INT);
      $pages_int = (int) filter_var($extents[1], FILTER_SANITIZE_NUMBER_INT);
    }
    else {
      // we only have (… items); titles with "(MISSING items)" will be ignored;
      // extract numbers from items string; we will use "pages" to describe the
      // number of items as it is more accurate most of the time
      $pages_int = (int) filter_var($extent_string, FILTER_SANITIZE_NUMBER_INT);
      // echo $pages_int . "\n";
    }

    // add physicalDescription/extent element
    if (is_numeric($pages_int)) {
      // check if physicalDescription element already exists
      if (empty($book_mods->physicalDescription)) {
        $physicalDescription = $book_mods->addChild('physicalDescription');
      }
      else {
        $physicalDescription = $book_mods->physicalDescription;
      }
      // add "pages"
      $extent = $physicalDescription->addChild('extent', $pages_int);
      $extent->addAttribute('unit', 'pages');
      // add "items" if necessary
      if (isset($items_int) && is_numeric($items_int)) {
        $extent = $physicalDescription->addChild('extent', $items_int);
        $extent->addAttribute('unit', 'items');
      }
    }

    // trim extent string from title
    $book_title_extentless = str_replace($extent_string, '', $book_title);
    // echo $book_title_extentless . "\n";

    // trim whitespace from title
    $book_title_trimmed = trim($book_title_extentless);
    // echo $book_title_trimmed . "\n";

    // replace title element in the book MODS
    $book_mods->titleInfo->title = $book_title_trimmed;

    // replace <physicalDescription><digitalOrigin> value
    $book_mods->physicalDescription->digitalOrigin = 'reformatted digital';

    // replace <accessCondition> value
    $book_mods->accessCondition = 'Copyright to unpublished works in this collection created by Paul B. MacCready is held by Caltech. If you wish to quote or reproduce them beyond the extent of fair use, please contact the Caltech Archives to request permission. Copyright to works by others, and to MacCready’s publications, may be held by their respective creators, those creators’ heirs, or their publishers. If you wish to quote or reproduce such works beyond fair use, please contact the copyright holder to request permission.';

  }

  // format and save XML
  $xml = new DOMDocument('1.0', 'UTF-8');
  $xml->preserveWhiteSpace = FALSE;
  $xml->formatOutput = TRUE;
  $xml->loadXML($book_mods->asXML());
  // var_dump($xml);
  //  echo "❇️  new XML...\n";
  //  echo $xml->saveXML();
  //  $xml->saveXML();
  $xml->save($book_mods_file_path);

}
