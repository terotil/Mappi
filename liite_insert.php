<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('ryhmaid', 'nimi') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Jos ryhm�id on hukassa, ei voi muuta, kuin palata listalle
  if ( $ryhmaid == '' ) {
    forward("liitteet.php", 
	    'Ei voi lis�t� liitett�.  Tarvittava parametri '.
	    '<code>ryhmaid</code> puuttuu.');
    return;
  }

  // Tarkistetaan, ett� oikeudet ovat kunnossa
  if ( is_allowed($ryhmaid, 'write', 'ryhmaid') ) {
    // OK, lis�t��n liite
    $liiteid = qry_liite_insert($ryhmaid, $nimi);
    forward("liitteet.php");
  } else {
    // Ei oikeuksia, palautetaan takaisin liitelistaan viestin kera.
    forward("liitteet.php",
	    'Sinulla ei ole oikeutta lis�t� liitetietoa '.
	    'valitsemaasi ryhm��n.');
  }

?>
