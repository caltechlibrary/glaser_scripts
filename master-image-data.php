<?php

# display message when no arguments are given
if (!isset($argv[1])) {
  exit("\n😵 error: supply an absolute path to a directory\n➡️  example: php master-image-data.php /path/to/directory\n\n");
}

unlink($argv[1] . '/master-image-data.csv');
file_put_contents($argv[1] . '/master-image-data.csv', "identifier,filedir,filename,format,mimetype,geometry,resolution,units,type,bitdepth,compression,filesize\n");

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new RecursiveDirectoryIterator($argv[1], RecursiveDirectoryIterator::SKIP_DOTS);

$iterator = new RecursiveIteratorIterator($dirItem);

foreach ($iterator as $fileInfo) {

  // only act on TIFFs
  if (!($fileInfo->getExtension() == 'tiff')) {
    continue;
  }

  $filepath = $fileInfo->getPath();
  $filename = $fileInfo->getFilename();

  if (file_exists($filepath . '/MODS.xml')) {
    $mods = simplexml_load_file($filepath . '/MODS.xml');
  }

  $csv = fopen($argv[1] . '/master-image-data.csv', 'a');

  print_r($filepath . '/' . $filename . "\n");
  $image = new Imagick($filepath . '/' . $filename);

  $data = [];

  // [0] identifier
  $data[] = $mods->identifier->__toString();

  // [1] filedir
  $data[] = basename($filepath);

  // [2] filename
  $data[] = $filename;

  // [3] format
  $data[] = $image->getImageFormat();

  // [4] mime type
  $data[] = $image->getImageMimeType();

  // [5] geometry
  $geometry = $image->getImageGeometry();
  $data[] = $geometry['width'] . '×' . $geometry['height'];

  // [6] resolution
  $resolution = $image->getImageResolution();
  $data[] = $resolution['x'] . '×' . $resolution['y'];

  // [7] units
  $units = $image->getImageUnits();
  if ($units == Imagick::RESOLUTION_PIXELSPERINCH) {
    $data[] = 'PixelsPerInch';
  }
  elseif ($units == Imagick::RESOLUTION_PIXELSPERCENTIMETER) {
    $data[] = 'PixelsPerCentimeter';
  }
  else {
    $data[] = $units;
  }

  // [8] type
  $type = $image->getImageType();
  if ($type == Imagick::IMGTYPE_TRUECOLOR) {
    $data[] = 'TrueColor';
  }
  elseif ($type == Imagick::IMGTYPE_GRAYSCALE) {
    $data[] = 'Grayscale';
  }
  else {
    $data[] = $type;
  }

  // [9] depth
  $data[] = $image->getImageDepth();

  // [10] compression
  $compression = $image->getImageCompression();
  if ($compression == Imagick::COMPRESSION_NO) {
    $data[] = 'None';
  }
  elseif ($compression == Imagick::COMPRESSION_LZW) {
    $data[] = 'LZW';
  }
  else {
    $data[] = $compression;
  }

  // [11] filesize
  $data[] = $image->getImageLength();

  //debug
  print_r($data);

  fputcsv($csv, $data);

  fclose($csv);

}
