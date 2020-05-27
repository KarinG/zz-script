<?php

/**
 * @file
 * Script to match old Contact Method civicrm activities to other
 * activities that were added about the same time.
*/

// Input CSV -- 7 columns...
//   AlexID,Contact Subtype,First Name,Last Name,Activity ID,Activity Type,Activity Type ID,Activity Date,Contact Method
$input_csv = 'data-to-be-fixed.csv';

//
// First pass, get Contact Method activity (zz) data...
//

$zz = [];
$fh = fopen($input_csv, 'r');
while (($row = fgetcsv($fh)) !== FALSE) {
  if (strpos($row[5], 'ZZ') === 0) {

    $alexid = $row[0];
    $activity_id = $row[4];
    $type = str_replace('ZZ - ', '', $row[5]);
    $timestamp = strtotime($row[7]);

    if (!isset($zz[$alexid])) {
      $zz[$alexid] = [];
    }
    if (!isset($zz[$alexid][$type])) {
      $zz[$alexid][$type] = [];
    }

    $zz[$alexid][$type][$timestamp] = $activity_id;
  }
}
fclose($fh);

//
// Second pass, match activities to ZZ activities...
// and out put matched activity rows with ZZ activities appended...
//

$fh = fopen($input_csv, 'r');
while (($row = fgetcsv($fh)) !== FALSE) {

  // Skip zz this time.
  if (strpos($row[5], 'ZZ') === 0) {
    continue;
  }

  $alexid = $row[0];
  $timestamp = strtotime($row[7]);

  $types = zz_find($zz, $alexid, $timestamp);

  if (!empty($types)) {
    for ($i = 8; !empty($types); $i++) {
      $row[$i] = array_shift($types);
    }
    fputcsv(STDOUT, $row);
  }
}
fclose($fh);

/**
 * Find Contact Method (zz) activities for an alex ID within 30s before
 * or after a given timestamp...
 *
 * @param $zz
 *   An array of Contact Method activity data in the format:
 *     $zz[$alexid][$type_name][$contact_method_activity_timestamp] = $cm_activity_id;
 * @param $alexid
 *   An alex ID
 * @param $timestamp
 *   Timestamp to match
 * @param $window
 *   Seconds either side of target timestamp
 *
 * @return
 *   Array of matching Contact Method types.
 */
function zz_find($zz, $alexid, $timestamp, $window = 30) {
  $types = [];

  // Loop through zz data looking for matching timestamp keys within
  // the right window.
  if (!empty($zz[$alexid])) {
    for ($t = $timestamp - $window; $t <= $timestamp + $window; $t++) {
      foreach ($zz[$alexid] as $type => $timestamps) {
        foreach (array_keys($timestamps) as $zzt) {
          if ($t == $zzt) {
            $types[] = $type;
          } 
        }
      }
    }
  }

  return $types;
}
