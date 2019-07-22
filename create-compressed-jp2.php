<?php

if ($argv[1] == NULL || $argv[2] == NULL) {
  exit("🛑  exited: source and destination directories are required\n➡️  example: php create-compressed-jp2.php /path/to/source/directory /path/to/destination/directory");
}

$source_directory = dirname($argv[1]) . '/' . basename($argv[1]);
$destination_directory = dirname($argv[2]) . '/' . basename($argv[2]);
$logfile = $destination_directory . '/logs/' . pathinfo(__FILE__, PATHINFO_FILENAME) . '.log';

// create logs directory
if (!is_dir($destination_directory . '/logs')) {
  if (!mkdir($destination_directory . '/logs', 0777, TRUE)) {
    echolog("🛑  exited: failed to create {$destination_directory}/logs directory...\n");
    exit();
  }
}

// loop over every item inside $source_directory
$dirItem = new RecursiveDirectoryIterator($source_directory, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dirItem);

$folderPaths = [];
$filePaths = [];

foreach ($iterator as $fileInfo) {

  // save MODS folder paths
  if (strpos($fileInfo->getFilename(), '_MODS.xml') !== FALSE) {
    $folderPaths[] = $fileInfo->getPath();
  }

  // save TIFF paths
  if ($fileInfo->getExtension() == 'jp2') {
    $filePaths[] = $fileInfo->getPathname();
  }

}

echolog('📂 ' . count($folderPaths) . ' folders in source directory');
echolog('🖼  ' . count($filePaths) . ' JP2s in source directory');

sort($folderPaths);

$remaining_folders = count($folderPaths);
$remaining_files = count($filePaths);
foreach ($folderPaths as $folderPath) {

  echolog('➡️  processing ' . $folderPath);

  // // load MODS file
  // if (file_exists($folderPath . '/MODS.xml')) {
  //   $mods = simplexml_load_file($folderPath . '/MODS.xml');
  // }
  // else {
  //   echolog("🛑  no MODS.xml in $folderPath directory\n");
  //   continue;
  // }
  // 
  // // get data
  // $abstract = $mods->abstract->__toString();
  // $identifierLocal = $mods->identifier->__toString();
  // $relatedItemHostNote = $mods->relatedItem->note->__toString();
  // $titleInfoTitle = $mods->titleInfo->title->__toString();
  // if (strpos($titleInfoTitle, ' items)') !== FALSE) {
  //   // return the portion of the string that starts at the last open parenthesis
  //   $itemsSubstring = strrchr($titleInfoTitle, '(');
  //   $title = trim(str_replace($itemsSubstring, '', $titleInfoTitle));
  // }
  // else {
  //   $title = trim($titleInfoTitle);
  // }
  // 
  // // parse data
  // $identifierLocalParts = explode('_', $identifierLocal);
  // if ($identifierLocalParts[0] == 'DAG') {
  //   $collectionSlug = 'GlaserDA';
  //   $itemSlug = 'GlaserDA_Caltech';
  //   $ownership = 'Owned by the Caltech Archives.';
  // }
  // elseif ($identifierLocalParts[0] == 'DAGB') {
  //   $collectionSlug = 'GlaserDA';
  //   $itemSlug = 'GlaserDA_Berkeley';
  //   $ownership = 'Owned by The Bancroft Library, University of California, Berkeley. On indefinite loan to the California Institute of Technology Archives.';
  // }
  // else {
  //   echolog("🛑  unknown collection: $identifierLocal");
  //   continue;
  // }
  // if (is_numeric($identifierLocalParts[1])) {
  //   $seriesNumber = $identifierLocalParts[1];
  // }
  // else {
  //   echolog("🛑  unknown form of identifier: $identifierLocal");
  //   continue;
  // }
  // if (is_numeric($identifierLocalParts[2])) {
  //   $boxNumber = $identifierLocalParts[2];
  // }
  // else {
  //   echolog("🛑  unknown form of identifier: $identifierLocal");
  //   continue;
  // }
  // if (is_numeric($identifierLocalParts[3])) {
  //   $folderNumber = $identifierLocalParts[3];
  // }
  // elseif (strpos($identifierLocalParts[3], 'oversize')) {
  //   $folderNumber = rtrim($identifierLocalParts[3]);
  // }
  // else {
  //   echolog("🛑  unknown form of identifier: $identifierLocal");
  //   continue;
  // }
  // $relatedItemHostNoteParts = explode('; ', $relatedItemHostNote);
  // $seriesParts = explode(': ', $relatedItemHostNoteParts[0]);
  // $seriesName = $seriesParts[1];
  // $subseriesParts = explode(': ', $relatedItemHostNoteParts[1]);
  // $subseriesName = rtrim($subseriesParts[1], '.');
  // $subseriesWords = explode(' ', $relatedItemHostNoteParts[1]);
  // if ($subseriesWords[0] == 'Subseries') {
  //   $subseriesCharacter = rtrim($subseriesWords[1], ':');
  // }
  // else {
  //   echolog("🛑  unknown form of relatedItem note: $relatedItemHostNote");
  //   continue;
  // }
  // 
  // // pad the series number with zeros totaling 2 digits
  // $seriesNumberPadded = str_pad($seriesNumber, 2, '0', STR_PAD_LEFT);
  // // pad the subseries character with zeros totaling 2 characters
  // if (!empty($subseriesCharacter)) {
  //   $subseriesCharacterPadded = str_pad($subseriesCharacter, 2, '0', STR_PAD_LEFT);
  // }
  // else {
  //   $subseriesCharacterPadded = '00';
  // }
  // // pad the box number with zeros totaling 3 digits
  // $boxNumberPadded = str_pad($boxNumber, 3, '0', STR_PAD_LEFT);
  // // pad the folder number with zeros totaling 2 digits
  // $folderNumberPadded = str_pad($folderNumber, 2, '0', STR_PAD_LEFT);
  // 
  // // set up item name prefix
  // $itemName = "{$itemSlug}_{$seriesNumberPadded}_{$subseriesCharacterPadded}_{$boxNumberPadded}_{$folderNumberPadded}";
  // 
  // // replace non-alphanumeric characters in series name with underscores
  // $seriesNameSafe = preg_replace('/[^[:alnum:]]/', '_', $seriesName);
  // // replace non-alphanumeric characters in subseries name with underscores
  // if (!empty($subseriesName)) {
  //   $subseriesNameSafe = preg_replace('/[^[:alnum:]]/', '_', $subseriesName);
  // }
  // // replace non-alphanumeric characters in folder name with underscores
  // $titleSafe = preg_replace('/[^[:alnum:]]/', '_', $title);
  // 
  // // set up directory strings
  // $folder_directory_string = "{$collectionSlug}_{$seriesNumberPadded}_{$subseriesCharacterPadded}_{$boxNumberPadded}_{$folderNumberPadded}_{$titleSafe}";
  // $series_directory_string = "{$collectionSlug}_{$seriesNumberPadded}_{$seriesNameSafe}";
  // if (!empty($subseriesCharacter)) {
  //   $subseries_directory_string = "{$collectionSlug}_{$seriesNumberPadded}_{$subseriesCharacterPadded}_{$subseriesNameSafe}";
  // }
  // 
  // // set up full path conditionally with subseries
  // if (!empty($subseries_directory_string)) {
  //   $folder_directory_path = "{$collectionSlug}/{$series_directory_string}/{$subseries_directory_string}/{$folder_directory_string}";
  // }
  // else {
  //   $folder_directory_path = "{$collectionSlug}/{$series_directory_string}/{$folder_directory_string}";
  // }

  // // create collection directory
  // if (!is_dir("{$argv[2]}/{$collectionSlug}")) {
  //   if (!mkdir("{$argv[2]}/{$collectionSlug}", 0777, TRUE)) {
  //     echolog("🛑  exited: failed to create {$argv[2]}/{$collectionSlug} directory...\n");
  //     exit();
  //   }
  // }

  // create destination folder directory
  if (!is_dir("{$destination_directory}{$folderPath}")) {
    if (!mkdir("{$destination_directory}{$folderPath}", 0777, TRUE)) {
      echolog("🛑  exited: failed to create {$destination_directory}{$folderPath} directory...\n");
      exit();
    }
  }

  // copy MODS
  foreach (glob("{$folderPath}/*_MODS.xml") as $modsFile) {
    copy($modsFile, "{$destination_directory}{$folderPath}/MODS.xml");
  }

  // loop over files in each $folderPath
  $folderItem = new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS);

  // count JP2s
  $remaining_tiffs = count(glob($folderPath . '/*.jp2'));
  foreach ($folderItem as $folderFile) {

    // only act on JP2s
    if ($folderFile->getExtension() == 'jp2') {

      $sourceFilePathName = $folderFile->getPathname();
      $sourceFileBaseName = basename($sourceFilePathName, '.jp2');
      echolog("⏳ converting {$sourceFileBaseName}.jp2");

      // make page directory
      $pageNumber = substr($sourceFileBaseName, -4);
      if (!is_dir($destination_directory . $folderPath . "/{$pageNumber}")) {
        if (!mkdir($destination_directory . $folderPath . "/{$pageNumber}", 0777, TRUE)) {
          echolog("🛑  exited: failed to create {$destination_directory}{$folderPath}/{$pageNumber} directory...\n");
          exit();
        }
      }

      // create page MODS file
      $book_mods = simplexml_load_file("{$destination_directory}{$folderPath}/MODS.xml");
      $book_title = $book_mods->titleInfo->title;
      $page_mods_file_path = $destination_directory . $folderPath . "/{$pageNumber}/MODS.xml";
      // set up page title string
      $page_number = ltrim($pageNumber, '0');
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

      // // create compressed jp2 file
      // // annoyingly, kdu_compress cannot take a lossless jp2 as input
      // // first we must decompress the file into a tiff
      // $destinationDirectory = "{$destination_directory}{$folderPath}/{$pageNumber}";
      // $reconstitute = "convert $sourceFilePathName +compress {$destinationDirectory}/{$pageNumber}_TIFF.tiff";
      // exec($reconstitute);
      // $create_jp2 = "kdu_compress -i {$destinationDirectory}/{$pageNumber}_TIFF.tiff -o {$destinationDirectory}/OBJ.jp2 -rate 0.5 Clayers=1 Clevels=7 Cprecincts='{256,256},{256,256},{256,256},{128,128},{128,128},{64,64},{64,64},{32,32},{16,16}' Corder=RPCL ORGgen_plt=yes ORGtparts=R Cblk='{32,32}' Cuse_sop=yes";
      // exec($create_jp2);

      unlink("{$destinationDirectory}/{$pageNumber}_TIFF.tiff");

      $remaining_tiffs--;
      // echolog("✨ created {$itemName}_{$sourceFileBaseName}.jp2");
      echolog("↩️  $remaining_tiffs JP2s remain in folder");
    }

  }

  $remaining_folders--;
  $remaining_files = $remaining_files - count(glob($folderPath . '/*.jp2'));
  // echolog("⏱  finished processing $itemName $title");
  echolog("🔁 $remaining_folders folders remain, $remaining_files total files remain");
}

function filelog($message) {
  global $logfile;
  file_put_contents($logfile, microtime(TRUE) . "\t" . $message . PHP_EOL, FILE_APPEND);
}

function echolog($message) {
  echo $message . PHP_EOL;
  filelog($message);
}
