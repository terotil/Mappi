<?php
// data-kentän muunnos mime-tyyppien välillä

// Pomppusivu
$forwardpage = true;
// Kirjastot
require_once('mappi_headers.php');

$table   = safe_retrieve_gp('table', 'notable');
$id      = intval(safe_retrieve_gp('id', -1));
$to      = safe_retrieve_gp('to', 'nodestmime');

if ( convert_datafield($table, $id, $to) ) {
  $msg = "Sisältömuoto muunnettu.";
} else {
  $msg = "Sisältömuoto muunnettaessa tapahtui virhe.  Muotoa ei muunnettu.";
}

forward("{$table}_edit.php?{$table}id=$id", $msg);

?>