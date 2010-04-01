<?php 
  // ============================================================
  // Tietokannan oletusasetukset 
  // $Id: mappi_rc_defaults.php,v 1.2 2007/02/24 02:54:24 mediaseitti Exp $
  // ============================================================
require_once('MAPPI/general.php');
require_once('libseitti-php/stext2html.function.php');

// Hyvä oletus juurihakemistolle (uri:lle, jonka alla käyttöliittymä
// 'ulkoa' katsoen sijaitsee) on polku, jossa suoritettava skripti
// sijaitsee.  Oletus saattaa mennä pieleen, jos käytetään
// esim. jotain Apachen mod_rewrite -tyyppisiä kikkoja.  Aseta silloin
// $root tässä tai jossain aiemmin.
if ( !isset($root) ) {
  $root = ereg_replace('/[^/]*$', '/', script_name());
}

// Globaalit perusasetukset
global $settings;

$settings = array(

  'underconstruction' => false,

  // ============================================================
  // Tietokanta
  // ============================================================
  'dbengine'    => 'mysql',        // Tyyppi
  'db'          => 'database',     // Tietokannan nimi
  'host'        => 'localhost',    // Tietokannan sijainti
  'user'        => 'dbuser',       // Käyttäjä
  'pw'          => 'dbpassword',   // Salasana

  // ============================================================
  // Käyttöliittymä
  // ============================================================
  // Lokitiedosto
  'logfile' => 'mappi.log',
  // Järjestelmän "juurihakemisto"
  'root'        => $root,
  // Kirjautumissivun osoite (absoluuttinen polku, ei host-nimeä)
  'login_page'  => $root . 'index.php',
  // Tiedostojen latausskriptin osoite (absoluuttinen polku, ei
  // host-nimeä)
  'download_page' => $root . 'download.php',
  // Tee tiedostoille automaattisia muutoksia mime-tyypin perusteella
  'autoconvert_uploads' => true,

  // ============================================================
  // Käyttöliittymän ulkoasu
  // ============================================================
  // Tyylisivutiedoston osoite
  'css'         => $root.'mappi.css',
  // Järjestelmän nimi, näytetään käyttöliittymän otsikkorivillä
  'name'        => 'Tietovaraston nimi',
  // Ylläpitäjän yhteystiedot
  'admin_email' => 'admin@mydomain.invalid',
  'admin_phone' => '',
  // Virhetilanteissa tulostettava viesti.
  'emsg'        => '<br>Ota yhteyttä järjestelmän ylläpitäjään.',
  // Hakulomakkeiden määrärajausvaihtoehdot
  'limit'       => array(array(50,'50'), 
			 array(500,'500'), 
			 array(999999,'kaikki')),
  'default_rid' => '3',
  // Jaksotyypin valintalistan vaihtoehdot
  'jaksotyyppi' => array('in' => 'Mukaanlukeva',
			 'ex' => 'Poislukeva'),
  // Näytetäänkö pääsivut
  'welcome' => true,
  'welcome_local' => true,

  // ============================================================
  // Muotoiluasetukset
  // ============================================================
  // Päiväykseksi tulkittavat kentät
  // 1,-1=nimi, 2,-2=nimi2, >=0 kenttien järjestys vaihtuu
  'paivayskentat' => array(0=>0),
  // Liitetietojen vapaan tekstin muotoilu, 
  // liiteid => 'tyyppi', vaihtoehdot tel, www, addr, email, eur, m2
  // ks. MAPPI/format_liite()
  // Standarditiedoille
  'liitetyypit' => 
  array(1=>'tel',2=>'email',3=>'addr',4=>'addr',5=>'www',6=>'tel'),

  // MIME-asetukset
  // Muokkaimet
  'editors' => 
  array(
	'text/stext' => 'textfield',
	'text/plain' => 'textfield'
	),
  // MIME-tyypit
  'mimetypes' =>
  array(
	'text/stext' => 'Muotoilumerkkausta sisältävä teksti',
	'text/plain' => 'Pelkkä teksti'
	),
  // Muunnosfunktiot
  'converters' =>
  array(
	'text/stext->text/html' => 'stext2html',
	'text/plain->text/html' => 'plain2html',
	'text/x-login->text/html' => 'nl2br',
	'text/x-accesscontrol->text/html' => 'nl2br'
	),
  // MIME-tyyppien oletusarvot
  'mime_kohde_liite' => 'text/plain',
  'mime_kohde' => 'text/stext',

  // ============================================================
  // Tietokannan lukitukset
  // ============================================================
  // Liitetiedot, joita ei julkisissa raporteissa näytetä.  Lista
  // liiteid-numeroita.  Älä poista nollaa tästä listasta, ellet
  // tarkalleen tiedä mitä teet.
  'piiloliite'  => array(0),
  // Ryhmät, joiden kohteita ei julkisissa raporteissa näytetä.
  'piiloryhma'  => array(0),
  // Kohteet, joita ei anneta poistaa.  Lista kohdeid-numeroita.  Älä
  // poista nollaa tästä listasta, ellet tarkalleen tiedä mitä teet.
  'lukittu'     => array(0)

  );

?>
