<?php  
  // Asetukset ja funktiokirjasto tarvitaan.
  require_once('mappi_rc.php');
  require_once('MAPPI/mappi_lib.php');

  // Palautetaan sessio
  sessioncookie();
  session_start();

  // Yhdistet��n tietokantaan
  connect_db();

  // Jos k�ytt�j� ei ole kirjautuneena, heitet��n suoraan
  // login -sivulle.  Paitsi tietysti silloin, kun satutaan olemaan
  // kyseisell� sivulla.
  if ( !( is_logged() || ($settings['login_page'] == script_name()) ) ) {
    forward($settings['login_page'].
	    '?goto='.urlencode($_SERVER['REQUEST_URI']));
    exit;
  }

  // Komennetaan, ettei saa kakuttaa, ellei sit� ole erikseen sallittu
  if ( !isset($maycache) ) { 
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
  }

// Vaihdetaan n�ytett�v�� kielt� jos kieliparametri on annettuna
$lang = intval(safe_retrieve_gp('lang', 0));
if ( $lang > 0 ) {
  aseta_istunnon_kieli($lang);
}

// echo '<pre>';
// print_r($HTTP_SESSION_VARS);
// echo '</pre>';

?>
