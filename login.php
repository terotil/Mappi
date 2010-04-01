<?php
  $forwardpage = true;
  require_once('mappi_rc.php');
  require_once('MAPPI/mappi_lib.php');
  sessioncookie();
  session_start();
  connect_db();

$uname = safe_retrieve_gp('uname');
$goto = safe_retrieve_gp('goto', $settings['login_page'], true);
$pw = safe_retrieve_gp('pw');
  if ( login($uname, $pw) ) {
    // Heivataan takaisin sivulle, jolta tultiin (oletuksena
    // login-sivu), tällä kertaa kirjautuneena
    forward($goto);
  } else {
    // Ei onnistunut, takaisin login -sivulle virheilmoituksen kera.
    forward($settings['login_page'].'?username='.urlencode($uname).
	    '&goto='.urlencode($goto), 
	    'Virheellinen käyttäjänimi tai salasana!');
  }
?>
