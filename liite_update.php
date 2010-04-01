<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('liiteid', 'ryhmaid', 'nimi') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Jos liiteid on hukassa, ei voi muuta, kuin palata listalle
  if ( $liiteid == '' ) {
    forward("liitteet.php", 
	    'Ei voi muokata liitteen tietoja.  Tarvittava parametri '.
	    '<code>liiteid</code> puuttuu.');
    return;
  }

  if ( is_allowed($ryhmaid, 'write', 'ryhmaid') ) {
    qry_liite_update($liiteid, $ryhmaid, $nimi);
    forward("liitteet.php");
  } else {
    // Ei oikeuksia, palautetaan takaisin liitelistaan
    forward("liitteet.php",
	    'Sinulla ei ole oikeutta muokata liitteen tietoja.');
  }

?>
