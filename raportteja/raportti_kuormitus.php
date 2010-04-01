<?php //;Palvelin;WWW-palvelimen tila
  $pagename = 'Palvelimen tila';
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

$uptime = '';
$state = array();
if (!buffered_system('uptime', $uptime)) {
  $uptime = split(',', $uptime);
  $tmp = split('up ', $uptime[0]);
  $state['K�ynniss�'] = $tmp[1];
  $tmp = split(': ', $uptime[3]);
  $state['Keskim��r�inen kuormitus'] = '';
  $state['1 min'] = $tmp[1];
  $state['5 min'] = $uptime[4];
  $state['15 min'] = $uptime[5];

  // FIXME: Lis�t��n joskus t�nne viel� muistin tila

  // Tulostetaan tilataulukko
  echo '<table border="1" cellspacing="0" cellpadding="1">';
  foreach ( $state as $key => $val ) {
    echo "<tr><td align=\"right\">$key&nbsp;</td><td>$val&nbsp;</td></tr>";
  }
  echo '</table>';
} else {
  echo 'Komennon "uptime" suoritus ep�onnistui.';
}

  require_once('mappi_html-body-end.php');
?>
