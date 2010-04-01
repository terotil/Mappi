<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('ryhmaid', 'nimi') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Jos ryhmäid on hukassa, ei voi muuta, kuin palata listalle
  if ( $ryhmaid == '' ) {
    forward("liitteet.php", 
	    'Ei voi lisätä liitettä.  Tarvittava parametri '.
	    '<code>ryhmaid</code> puuttuu.');
    return;
  }

  // Tarkistetaan, että oikeudet ovat kunnossa
  if ( is_allowed($ryhmaid, 'write', 'ryhmaid') ) {
    // OK, lisätään liite
    $liiteid = qry_liite_insert($ryhmaid, $nimi);
    forward("liitteet.php");
  } else {
    // Ei oikeuksia, palautetaan takaisin liitelistaan viestin kera.
    forward("liitteet.php",
	    'Sinulla ei ole oikeutta lisätä liitetietoa '.
	    'valitsemaasi ryhmään.');
  }

?>
