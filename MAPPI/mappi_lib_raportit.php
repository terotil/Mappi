<?php
// Yhteiset kirjastofunktiot tarvitaan
require_once('MAPPI/mappi_lib.php');

///////////////////////////////////////////////////////////////////////////////
// Raportoinnissa k�ytett�vi� funktioita
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Luodaan taulukkomuotoinen esitys viittauksista
///////////////////////////////////////////////////////////////////////////////
// $liiteid = T�t� tyyppi� olevien liitetietojen viittaukset
//  n�ytet��n.
// $viittaavat = 'Y-akseli', eli lista kohteista, joiden
//  liitetiedoissa olevat viittaukset n�ytet��n.  qry_kohde_lista()
//  -muoto.
// $viitatut = 'X-akseli', eli lista kohteista, joihin osoittavat
//  viittaukset n�ytet��n.  qry_kohde_lista() -muoto.
///////////////////////////////////////////////////////////////////////////////
function &viittaustaulukko(&$viittaavat, &$viitatut, $liiteid) {
  // Haetaan lista viittauksista
  $viittaukset =& 
    qry_liiteviittaukset(array_column($viittaavat, 'kohdeid'), 
			 array_column($viitatut,   'kohdeid'),
			 $liiteid);

  // Luodaan oikein indeksoitu tyhj� mallirivi
  $empty_row = array();
  $empty_row['Nimi'] = 'tyhj�';
  foreach ($viitatut as $viitattu) {
    $empty_row[$viitattu['kohdeid']] = '';
  }

  // T�ytet��n taulukko malliriveill�
  $table = array();
  foreach ($viittaavat as $viittaava) {
    $table[$viittaava['kohdeid']] = $empty_row;
    $table[$viittaava['kohdeid']]['Nimi'] = 
      kohde_kokonimi($viittaava['nimi'], $viittaava['nimi2'], 
		     '', '', $viittaava['ryhmaid']);
  }

  // K�yd��n l�pi viittaukset ja merkit��n jokainen taulukkoon
  foreach ($viittaukset as $viittaus) {
    $table[$viittaus['from']][$viittaus['to']] = 'X';
  }

  return $table;
}


?>
