<?php
  $pagename = 'Asetukset';
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

  // Linkitetään otsikkotietojen perusteella kaikki asetus_*.php
  // -tiedostot ja lisäksi vakiot, eli salasanan vaihto (passwd.php),
  // ryhmittelyjen muokkaus (ryhmat.php) ja liiteotsikoiden muokkaus
  // (liitteet.php)
  echo link_scripts('^(passwd|ryhmat|liitteet|asetus_.*)\.php$');

  require_once('mappi_html-body-end.php');
?>
