<?php  
  // Asetukset ja funktiokirjasto tarvitaan.
  require_once('mappi_rc.php');
  require_once('MAPPI/mappi_lib.php');

  // Palautetaan sessio
  sessioncookie();
  session_start();

  // Yhdistetään tietokantaan
  connect_db();

  // Jos käyttäjä ei ole kirjautuneena, heitetään suoraan
  // login -sivulle.  Paitsi tietysti silloin, kun satutaan olemaan
  // kyseisellä sivulla.
  if ( !( is_logged() || ($settings['login_page'] == script_name()) ) ) {
    forward($settings['login_page'].
	    '?goto='.urlencode($_SERVER['REQUEST_URI']));
    exit;
  }

  // Komennetaan, ettei saa kakuttaa, ellei sitä ole erikseen sallittu
  if ( !isset($maycache) ) { 
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
  }

// Vaihdetaan näytettävää kieltä jos kieliparametri on annettuna
$lang = intval(safe_retrieve_gp('lang', 0));
if ( $lang > 0 ) {
  aseta_istunnon_kieli($lang);
}

// echo '<pre>';
// print_r($HTTP_SESSION_VARS);
// echo '</pre>';

?>
