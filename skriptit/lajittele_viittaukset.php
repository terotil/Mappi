<?php
/* Skripti viittausten j�rjest�miseen kun tietokantaan on lis�tty
   j�rjestystietueet - Tero Tilus 2002 */

  require_once('mappi_rc.php');
  require_once('mappi_headers.php');

$skip_dbchanged = true;

$viittaukset = qry('SELECT * FROM viittaus');
$cv = count($viittaukset);
for ( $i=0; $i < $cv; $i++ ) {
  qry("UPDATE viittaus SET jarjestys = $i ".
      "WHERE kohde_liiteid = ".
      $viittaukset[$i]['kohde_liiteid']." AND ".
      "kohdeid = ".
      $viittaukset[$i]['kohdeid']);
}

echo "$i viittausta j�rjestetty";
?>
