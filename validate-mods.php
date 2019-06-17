<?php

// ASSUMPTIONS
// MODS datastreams were fetched.
//
// Our structure should look like this:
//
// /path/to/items
// ├── item_12345_MODS.xml
// ├── item_12346_MODS.xml
// ├── item_12347_MODS.xml
// │   ...

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new RecursiveDirectoryIterator($argv[1], RecursiveDirectoryIterator::SKIP_DOTS);
$itItem = new RecursiveIteratorIterator($dirItem);

foreach ($itItem as $fileInfo) {

  if (strpos($fileInfo->getFilename(), 'MODS.xml') === FALSE) {
    continue;
  }

  $mods_file_path = $fileInfo->getPathname();

  $mods = simplexml_load_file($mods_file_path);
  $schemaLocation_value = $mods->attributes('xsi', true)->schemaLocation;
  $mods_xsd_url = trim(strrchr($schemaLocation_value, ' '));
  if (strpos($mods_xsd_url, '.xsd') === FALSE) {
    echo "🛑  no XSD file found in $mods_file_path ...\n";
  }

  exec("xmllint --noout --schema $mods_xsd_url $mods_file_path");

}
