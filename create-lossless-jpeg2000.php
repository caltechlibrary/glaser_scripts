<?php

if ($argv[1] == NULL || $argv[2] == NULL) {
  exit("🛑  exited: source and destination directories are required\n➡️  example: php create-lossless-jpeg2000.php /path/to/source/directory /path/to/destination/directory");
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

//// create reconstituted directory
//if (!is_dir($destination_directory . '/reconstituted')) {
//  if (!mkdir($destination_directory . '/reconstituted', 0777, TRUE)) {
//    echolog("🛑  exited: failed to create {$destination_directory}/reconstituted directory...\n");
//    exit();
//  }
//}

// loop over every item inside directory passed as $argv[1]
$dirItem = new RecursiveDirectoryIterator($argv[1], RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dirItem);

$folderPaths = [];
$filePaths = [];

foreach ($iterator as $fileInfo) {

  // save MODS folder paths
  if ($fileInfo->getFilename() == 'MODS.xml') {
    $folderPaths[] = $fileInfo->getPath();
  }

  // save TIFF paths
  if ($fileInfo->getExtension() == 'tiff') {
    $filePaths[] = $fileInfo->getPathname();
  }

}

echolog('📂 ' . count($folderPaths) . ' folders in source directory');
echolog('🖼  ' . count($filePaths) . ' TIFFs in source directory');

sort($folderPaths);

if (!file_put_contents($argv[1] . '/folderPaths.json', json_encode($folderPaths, JSON_PRETTY_PRINT))) {
  echolog("🛑  failed to write ${argv[1]}/folderPaths.json file");
}

$remaining_folders = count($folderPaths);
$remaining_files = count($filePaths);
foreach ($folderPaths as $folderPath) {

  echolog('➡️  processing ' . $folderPath);

  // load MODS file
  if (file_exists($folderPath . '/MODS.xml')) {
    $mods = simplexml_load_file($folderPath . '/MODS.xml');
  }
  else {
    echolog("🛑  no MODS.xml in $folderPath directory\n");
    continue;
  }

  // get data
  $abstract = $mods->abstract->__toString();
  $identifierLocal = $mods->identifier->__toString();
  $relatedItemHostNote = $mods->relatedItem->note->__toString();
  $titleInfoTitle = $mods->titleInfo->title->__toString();
  if (strpos($titleInfoTitle, ' items)') !== FALSE) {
    // return the portion of the string that starts at the last open parenthesis
    $itemsSubstring = strrchr($titleInfoTitle, '(');
    $title = trim(str_replace($itemsSubstring, '', $titleInfoTitle));
  }
  else {
    $title = trim($titleInfoTitle);
  }

  // parse data
  $identifierLocalParts = explode('_', $identifierLocal);
  if ($identifierLocalParts[0] == 'DAG') {
    $collectionSlug = 'GlaserDA';
    $itemSlug = 'GlaserDA_Caltech';
    $ownership = 'Owned by the Caltech Archives.';
  }
  elseif ($identifierLocalParts[0] == 'DAGB') {
    $collectionSlug = 'GlaserDA';
    $itemSlug = 'GlaserDA_Berkeley';
    $ownership = 'Owned by The Bancroft Library, University of California, Berkeley. On indefinite loan to the California Institute of Technology Archives.';
  }
  else {
    echolog("🛑  unknown collection: $identifierLocal");
    continue;
  }
  if (is_numeric($identifierLocalParts[1])) {
    $seriesNumber = $identifierLocalParts[1];
  }
  else {
    echolog("🛑  unknown form of identifier: $identifierLocal");
    continue;
  }
  if (is_numeric($identifierLocalParts[2])) {
    $boxNumber = $identifierLocalParts[2];
  }
  else {
    echolog("🛑  unknown form of identifier: $identifierLocal");
    continue;
  }
  if (is_numeric($identifierLocalParts[3])) {
    $folderNumber = $identifierLocalParts[3];
  }
  elseif (strpos($identifierLocalParts[3], 'oversize')) {
    $folderNumber = rtrim($identifierLocalParts[3]);
  }
  else {
    echolog("🛑  unknown form of identifier: $identifierLocal");
    continue;
  }
  $relatedItemHostNoteParts = explode('; ', $relatedItemHostNote);
  $seriesParts = explode(': ', $relatedItemHostNoteParts[0]);
  $seriesName = $seriesParts[1];
  $subseriesParts = explode(': ', $relatedItemHostNoteParts[1]);
  $subseriesName = rtrim($subseriesParts[1], '.');
  $subseriesWords = explode(' ', $relatedItemHostNoteParts[1]);
  if ($subseriesWords[0] == 'Subseries') {
    $subseriesCharacter = rtrim($subseriesWords[1], ':');
  }
  else {
    echolog("🛑  unknown form of relatedItem note: $relatedItemHostNote");
    continue;
  }

  // pad the series number with zeros totaling 2 digits
  $seriesNumberPadded = str_pad($seriesNumber, 2, '0', STR_PAD_LEFT);
  // pad the subseries character with zeros totaling 2 characters
  if (!empty($subseriesCharacter)) {
    $subseriesCharacterPadded = str_pad($subseriesCharacter, 2, '0', STR_PAD_LEFT);
  }
  else {
    $subseriesCharacterPadded = '00';
  }
  // pad the box number with zeros totaling 3 digits
  $boxNumberPadded = str_pad($boxNumber, 3, '0', STR_PAD_LEFT);
  // pad the folder number with zeros totaling 2 digits
  $folderNumberPadded = str_pad($folderNumber, 2, '0', STR_PAD_LEFT);

  // set up item name prefix
  $itemName = "{$itemSlug}_{$seriesNumberPadded}_{$subseriesCharacterPadded}_{$boxNumberPadded}_{$folderNumberPadded}";

  // replace non-alphanumeric characters in series name with underscores
  $seriesNameSafe = preg_replace('/[^[:alnum:]]/', '_', $seriesName);
  // replace non-alphanumeric characters in subseries name with underscores
  if (!empty($subseriesName)) {
    $subseriesNameSafe = preg_replace('/[^[:alnum:]]/', '_', $subseriesName);
  }
  // replace non-alphanumeric characters in folder name with underscores
  $titleSafe = preg_replace('/[^[:alnum:]]/', '_', $title);

  // set up directory strings
  $folder_directory_string = "{$collectionSlug}_{$seriesNumberPadded}_{$subseriesCharacterPadded}_{$boxNumberPadded}_{$folderNumberPadded}_{$titleSafe}";
  $series_directory_string = "{$collectionSlug}_{$seriesNumberPadded}_{$seriesNameSafe}";
  if (!empty($subseriesCharacter)) {
    $subseries_directory_string = "{$collectionSlug}_{$seriesNumberPadded}_{$subseriesCharacterPadded}_{$subseriesNameSafe}";
  }

  // set up full path conditionally with subseries
  if (!empty($subseries_directory_string)) {
    $folder_directory_path = "{$collectionSlug}/{$series_directory_string}/{$subseries_directory_string}/{$folder_directory_string}";
  }
  else {
    $folder_directory_path = "{$collectionSlug}/{$series_directory_string}/{$folder_directory_string}";
  }

  // create collection directory
  if (!is_dir("{$argv[2]}/{$collectionSlug}")) {
    if (!mkdir("{$argv[2]}/{$collectionSlug}", 0777, TRUE)) {
      echolog("🛑  exited: failed to create {$argv[2]}/{$collectionSlug} directory...\n");
      exit();
    }
  }

  // create folder directory
  if (!is_dir("{$destination_directory}/{$folder_directory_path}")) {
    if (!mkdir("{$destination_directory}/{$folder_directory_path}", 0777, TRUE)) {
      echolog("🛑  exited: failed to create {$destination_directory}/{$folder_directory_path} directory...\n");
      exit();
    }
  }

  // copy MODS
  copy("{$folderPath}/MODS.xml", "{$destination_directory}/{$folder_directory_path}/{$itemName}_MODS.xml");

  // loop over files in each $folderPath
  $folderItem = new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS);

  // count TIFFs
  $remaining_tiffs = count(glob($folderPath . '/*.tiff'));
  foreach ($folderItem as $folderFile) {

    // only act on TIFFs
    if ($folderFile->getExtension() == 'tiff') {

      $sourceFilePathName = $folderFile->getPathname();
      $sourceFileBaseName = basename($sourceFilePathName, '.tiff');
      echolog("⏳ converting {$sourceFileBaseName}.tiff");

      $destinationFile = "{$destination_directory}/{$folder_directory_path}/{$itemName}_{$sourceFileBaseName}.jp2";

      // get source image data
      $image = new Imagick($sourceFilePathName);
      $geometry = $image->getImageGeometry();
      $resolution = $image->getImageResolution();
      $dimension_x = $geometry['width'] / $resolution['x'];
      $dimension_y = $geometry['height'] / $resolution['y'];
      $scannedSizeString = "Scanned Size: $dimension_x by $dimension_y inches.";

      // convert TIFF to JPEG2000
      $convert = "convert $sourceFilePathName -depth 8 -quality 0 $destinationFile >>$logfile 2>&1";
      filelog("🚀 running... "  . $convert);
      exec($convert);

      // write metadata to JPEG2000
      $identifierString = "{$itemName}_{$sourceFileBaseName}";
      $titleString = $title . ' (page ' . ltrim($sourceFileBaseName, '0') . ')';
      $write = "exiftool -description='$abstract' -identifier='$identifierString' -rights='$ownership' -format='$scannedSizeString' -title='$titleString' -overwrite_original $destinationFile";
      filelog("🚀 running... "  . $write);
      exec($write);

//      // diff identify output
//      $identify_source = "identify -verbose '{$sourceFilePathName}' > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-source.identify 2>&1";
//      filelog("🚀 running... "  . $identify_source);
//      exec($identify_source);
//      $identify_destination = "identify -verbose '{$destinationFile}' > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-destination.identify 2>&1";
//      filelog("🚀 running... "  . $identify_destination);
//      exec($identify_destination);
//      $diff_identify_converted = "diff {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-source.identify {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-destination.identify > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-tiff-jp2-identify.diff";
//      filelog("🚀 running... "  . $diff_identify_converted);
//      exec($diff_identify_converted);

//      // diff exiftool output
//      $exiftool_source = "exiftool -a -u -G1:2 '{$sourceFilePathName}' > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-source.exiftool";
//      filelog("🚀 running... "  . $exiftool_source);
//      exec($exiftool_source);
//      $exiftool_destination = "exiftool -a -u -G1:2 '{$destinationFile}' > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-destination.exiftool";
//      filelog("🚀 running... "  . $exiftool_destination);
//      exec($exiftool_destination);
//      $diff_exiftool_converted = "diff {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-source.exiftool {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-destination.exiftool > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-tiff-jp2-exiftool.diff";
//      filelog("🚀 running... "  . $diff_exiftool_converted);
//      exec($diff_exiftool_converted);

//      // reconstitute TIFF
//      $reconstitute = "convert $destinationFile +compress {$destination_directory}/reconstituted/{$itemName}_{$sourceFileBaseName}.tiff >>$logfile 2>&1";
//      filelog("🚀 running... "  . $reconstitute);
//      exec($reconstitute);

//      // diff identify output
//      $identify_reconstituted = "identify -verbose '{$destination_directory}/reconstituted/{$itemName}_{$sourceFileBaseName}.tiff' > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-reconstituted.identify 2>&1";
//      filelog("🚀 running... "  . $identify_reconstituted);
//      exec($identify_reconstituted);
//      $diff_identify_reconstituted = "diff {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-source.identify {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-reconstituted.identify > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-tiff-tiff-identify.diff";
//      filelog("🚀 running... "  . $diff_identify_reconstituted);
//      exec($diff_identify_reconstituted);

//      // diff exiftool output
//      $exiftool_reconstituted = "exiftool -a -u -G1:2 '{$destination_directory}/reconstituted/{$itemName}_{$sourceFileBaseName}.tiff' > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-reconstituted.exiftool";
//      filelog("🚀 running... "  . $exiftool_reconstituted);
//      exec($exiftool_reconstituted);
//      $diff_exiftool_reconstituted = "diff {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-source.exiftool {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-reconstituted.exiftool > {$destination_directory}/logs/{$itemName}_{$sourceFileBaseName}-tiff-tiff-exiftool.diff";
//      filelog("🚀 running... "  . $diff_exiftool_reconstituted);
//      exec($diff_exiftool_reconstituted);

//      array_map('unlink', glob("{$destination_directory}/logs/*.identify"));
//      array_map('unlink', glob("{$destination_directory}/logs/*.exiftool"));

      $remaining_tiffs--;
      echolog("✨ created {$itemName}_{$sourceFileBaseName}.jp2");
      echolog("↩️  $remaining_tiffs TIFFs remain in folder");
    }

  }

  $remaining_folders--;
  $remaining_files = $remaining_files - count(glob($folderPath . '/*.tiff'));
  echolog("⏱  finished processing $itemName $title");
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
