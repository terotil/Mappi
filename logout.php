<?php
  // Haetaan asetukset
  require_once('mappi_rc.php');
  require_once('MAPPI/mappi_lib.php');

  // Palautetaan sessio siivousta varten
  sessioncookie();
  session_start();

  // Lokitetaan
  mappi_log('Logout', 'Logged out.');

  // Siivotaan sessiokohtainen data
$HTTP_SESSION_VARS[$settings['db']] = array();
/*
  foreach ($HTTP_SESSION_VARS as $k => $v) {
    session_unregister($k);
  }
*/
  // Poistetaan sessiokeksi
  // FIXME: Jostain syystä tämä kosauttaa, pitää debugata
//  $p = session_get_cookie_params();
//  setcookie(session_name(), "", 0, $p["path"], $p["domain"]);

  // Lopetetaan sessio
  @session_destroy();

  // Jatketaan kirjautumissivulle
  header('Location: '.$settings['login_page']);
?>
