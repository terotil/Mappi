<?php 
  // ============================================================
  // Tietokannan oletusasetukset 
  // $Id: mappi_rc_defaults.php,v 1.2 2007/02/24 02:54:24 mediaseitti Exp $
  // ============================================================
require_once('MAPPI/general.php');
require_once('libseitti-php/stext2html.function.php');

// Hyv� oletus juurihakemistolle (uri:lle, jonka alla k�ytt�liittym�
// 'ulkoa' katsoen sijaitsee) on polku, jossa suoritettava skripti
// sijaitsee.  Oletus saattaa menn� pieleen, jos k�ytet��n
// esim. jotain Apachen mod_rewrite -tyyppisi� kikkoja.  Aseta silloin
// $root t�ss� tai jossain aiemmin.
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
  'user'        => 'dbuser',       // K�ytt�j�
  'pw'          => 'dbpassword',   // Salasana

  // ============================================================
  // K�ytt�liittym�
  // ============================================================
  // Lokitiedosto
  'logfile' => 'mappi.log',
  // J�rjestelm�n "juurihakemisto"
  'root'        => $root,
  // Kirjautumissivun osoite (absoluuttinen polku, ei host-nime�)
  'login_page'  => $root . 'index.php',
  // Tiedostojen latausskriptin osoite (absoluuttinen polku, ei
  // host-nime�)
  'download_page' => $root . 'download.php',
  // Tee tiedostoille automaattisia muutoksia mime-tyypin perusteella
  'autoconvert_uploads' => true,

  // ============================================================
  // K�ytt�liittym�n ulkoasu
  // ============================================================
  // Tyylisivutiedoston osoite
  'css'         => $root.'mappi.css',
  // J�rjestelm�n nimi, n�ytet��n k�ytt�liittym�n otsikkorivill�
  'name'        => 'Tietovaraston nimi',
  // Yll�pit�j�n yhteystiedot
  'admin_email' => 'admin@mydomain.invalid',
  'admin_phone' => '',
  // Virhetilanteissa tulostettava viesti.
  'emsg'        => '<br>Ota yhteytt� j�rjestelm�n yll�pit�j��n.',
  // Hakulomakkeiden m��r�rajausvaihtoehdot
  'limit'       => array(array(50,'50'), 
			 array(500,'500'), 
			 array(999999,'kaikki')),
  'default_rid' => '3',
  // Jaksotyypin valintalistan vaihtoehdot
  'jaksotyyppi' => array('in' => 'Mukaanlukeva',
			 'ex' => 'Poislukeva'),
  // N�ytet��nk� p��sivut
  'welcome' => true,
  'welcome_local' => true,

  // ============================================================
  // Muotoiluasetukset
  // ============================================================
  // P�iv�ykseksi tulkittavat kent�t
  // 1,-1=nimi, 2,-2=nimi2, >=0 kenttien j�rjestys vaihtuu
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
	'text/stext' => 'Muotoilumerkkausta sis�lt�v� teksti',
	'text/plain' => 'Pelkk� teksti'
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
  // Liitetiedot, joita ei julkisissa raporteissa n�ytet�.  Lista
  // liiteid-numeroita.  �l� poista nollaa t�st� listasta, ellet
  // tarkalleen tied� mit� teet.
  'piiloliite'  => array(0),
  // Ryhm�t, joiden kohteita ei julkisissa raporteissa n�ytet�.
  'piiloryhma'  => array(0),
  // Kohteet, joita ei anneta poistaa.  Lista kohdeid-numeroita.  �l�
  // poista nollaa t�st� listasta, ellet tarkalleen tied� mit� teet.
  'lukittu'     => array(0)

  );

?>
