<?php
// Yhteiset kirjastofunktiot tarvitaan
require_once('MAPPI/mappi_lib.php');

///////////////////////////////////////////////////////////////////////////////
// Raportoinnissa käytettäviä funktioita
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Luodaan taulukkomuotoinen esitys viittauksista
///////////////////////////////////////////////////////////////////////////////
// $liiteid = Tätä tyyppiä olevien liitetietojen viittaukset
//  näytetään.
// $viittaavat = 'Y-akseli', eli lista kohteista, joiden
//  liitetiedoissa olevat viittaukset näytetään.  qry_kohde_lista()
//  -muoto.
// $viitatut = 'X-akseli', eli lista kohteista, joihin osoittavat
//  viittaukset näytetään.  qry_kohde_lista() -muoto.
///////////////////////////////////////////////////////////////////////////////
function &viittaustaulukko(&$viittaavat, &$viitatut, $liiteid) {
  // Haetaan lista viittauksista
  $viittaukset =& 
    qry_liiteviittaukset(array_column($viittaavat, 'kohdeid'), 
			 array_column($viitatut,   'kohdeid'),
			 $liiteid);

  // Luodaan oikein indeksoitu tyhjä mallirivi
  $empty_row = array();
  $empty_row['Nimi'] = 'tyhjä';
  foreach ($viitatut as $viitattu) {
    $empty_row[$viitattu['kohdeid']] = '';
  }

  // Täytetään taulukko malliriveillä
  $table = array();
  foreach ($viittaavat as $viittaava) {
    $table[$viittaava['kohdeid']] = $empty_row;
    $table[$viittaava['kohdeid']]['Nimi'] = 
      kohde_kokonimi($viittaava['nimi'], $viittaava['nimi2'], 
		     '', '', $viittaava['ryhmaid']);
  }

  // Käydään läpi viittaukset ja merkitään jokainen taulukkoon
  foreach ($viittaukset as $viittaus) {
    $table[$viittaus['from']][$viittaus['to']] = 'X';
  }

  return $table;
}


?>
