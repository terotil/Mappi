<?php
  $pagename = 'Raportit';
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

  // Linkitetän otsikkotietojen perusteella kaikki raportti_*.php
  // -tiedostot.
  echo link_scripts('^raportti_.*\.php$');

  require_once('mappi_html-body-end.php');
?>
