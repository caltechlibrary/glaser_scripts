<?php

// loop over every item inside directory passed as $argv[1]
// see: http://php.net/manual/en/class.directoryiterator.php
$dirItem = new RecursiveDirectoryIterator($argv[1], RecursiveDirectoryIterator::SKIP_DOTS);

$iterator = new RecursiveIteratorIterator($dirItem);

foreach ($iterator as $fileInfo) {

  // skip everything except MODS files
  if (strpos($fileInfo->getFilename(), '_MODS.xml') === FALSE) {
    continue;
  }

  $filename = $fileInfo->getFilename();
  $file_path = $fileInfo->getPath() . '/' . $filename;
  $path_items = explode('/', $fileInfo->getPath());
//  print_r($path_items);

  echo $file_path . "\n";

  $mods = simplexml_load_file($file_path);

  // register an arbitrary namespace for use with xpath
  $mods->registerXPathNamespace('blerg', 'http://www.loc.gov/mods/v3');

  $csv = fopen($argv[1] . '/inventory-coda6.csv', 'a');

  $data = [];

  // [0] identifier
  $data[] = $mods->identifier->__toString();

  // [1] title
  $data[] = $mods->titleInfo->title->__toString();

  // [2] date
  $data[] = $mods->originInfo->dateIssued->__toString();

  // [3] note
  if (!isset($mods->note->type['ownership'])) {
    $data[] = $mods->note->__toString();
  }
  else {
    $data[] = '';
  }

  // [4] host
  $data[] = $mods->relatedItem->note->__toString();

  // [5] ownership
  if (isset($mods->note->type['ownership'])) {
    $data[] = (string) $mods->xpath('//blerg:note[@type="ownership"]/text()')[0];
  }
  else {
    $data[] = '';
  }

  // [6] coda6_pid; from filename
  $pid_slug = str_replace('_MODS.xml', '', $filename);
  $data[] = $pid = str_replace('_', ':', $pid_slug);

  // [7] coda6_content_model; from directory
  $data[] = $path_items[3];

  // [8] coda6_page_count; from idcrudfp query
  if ($path_items[3] == 'bookCModel') {
    $lines = [];
    exec("drush idcrudfp --root=/var/www/html/drupal7 --user=1 --is_member_of=${pid} --pid_file=${argv[1]}/${pid_slug}.pids");
    exec("wc -l ${argv[1]}/${pid_slug}.pids", $lines);
    $data[] = strstr($lines[0], ' ', TRUE);
  }
  else {
    $data[] = '';
  }

  //debug
  print_r($data);

  fputcsv($csv, $data);

  fclose($csv);

}
