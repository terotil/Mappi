<?php
require_once("MAPPI/mappi_lib.php");

$type       = safe_retrieve('type', 'inline'); // 'attachment' tai 'inline'
$tiedostoid = safe_retrieve('id', -1); // tiedosto.tiedostoid
sessioncookie();
session_start();

// Ellei tiedostoid:tä ole annettu, se yritetään etsiä annetusta
// polusta.
if ( ( $tiedostoid < 1 ) and
     array_haskey($HTTP_SERVER_VARS, 'PATH_INFO') ) {
  // Pidetään huolta siitä, että polku on muotoa
  // /lataa.php/jotain1/jotain2, jossa jotain1 on tiedostoid Näin voi
  // hämätä selainta.
  $pathpar = split('/', $HTTP_SERVER_VARS['PATH_INFO']);
  if ( count($pathpar) == 3 ) {
    $tiedostoid = (integer)$pathpar[1];
  }
}

// Käyttäjän täytyy olla kirjautuneena
if ( !is_logged() ) {
  trigger_error('lataa.php : Vain kirjautuneella käyttäjällä '.
		'on oikeus ladata tietokannasta tiedostoja.');
  exit;
}

// Tiedostoid:n on oltava kunnollinen
if ( $tiedostoid < 1 ) {
  trigger_error('lataa.php : Tarvittava parametri id puuttuu '.
		'tai on negatiivinen.');
  exit;
}

// Avataan tietokantayhteys ja luetaan tiedosto kannasta.
connect_db();
$tiedosto = qry_tiedosto($tiedostoid);

// Oikeudet kohdallaan?  Tiedoston lataamiseen tarvitaan tiedoston
// sisältävän kohde_liitteen lukuoikeudet.
if ( !is_allowed($tiedosto['kohde_liiteid'], 'read', 'kohde_liiteid') ) {
  trigger_error('lataa.php : Oikeutesi eivät riitä tämän '.
		'tiedoston lataamiseen.');
  exit;
}

// Kaikki kunnossa, pistetään tiedosto menemään
// Ensiksi otsikot
header('Content-Type: '.$tiedosto['mime']);
header("Content-Disposition: $type; filename=".
       $tiedosto['nimi']."; size=".strlen($tiedosto['data']));
// Ja sitten data
print($tiedosto['data']);
?>
