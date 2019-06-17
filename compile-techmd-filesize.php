<?php

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new RecursiveDirectoryIterator($argv[1], RecursiveDirectoryIterator::SKIP_DOTS);

$iterator = new RecursiveIteratorIterator($dirItem);

$bytes = 0;
$filecount = 0;

foreach ($iterator as $fileInfo) {

  // skip everything except TECHMD files
  if (strpos($fileInfo->getFilename(), '_TECHMD.xml') === FALSE) {
    continue;
  }

  $filename = $fileInfo->getFilename();
  $file_path = $fileInfo->getPath() . '/' . $filename;

  echo $file_path . "\n";

  $fits = simplexml_load_file($file_path);
  $size = $fits->fileinfo->size;
  echo $size . "\n";

  $bytes = $bytes + $size;
  $filecount++;

}

echo "\nTOTAL SIZE: " . human_filesize($bytes) . " ($bytes bytes)";
echo "\nTOTAL TECHMD FILE COUNT: $filecount\n";

// adapted from http://php.net/manual/en/function.filesize.php#116205
function human_filesize($bytes, $decimals = 2) {
  $prefixes = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  if ($bytes > 0 && $factor > 0) {
    $prefix = substr($prefixes, $factor, 1);
  }
  elseif ($bytes > 0 && $factor == 0) {
    $prefix = '';
  }
  else {
    throw new Exception('invalid file size (not greater than zero)');
  }
  return number_format($bytes / pow(1000, $factor), $decimals) . " {$prefix}B";
}
