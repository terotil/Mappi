<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('ryhmaid', 'nimi', 'kohde_nimi', 'kohde_nimi2', 'kohde_data', 
		  'liite_nimi', 'kohde_liite_data') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Jos ryhmaid on hukassa, ei voi muuta, kuin palata listalle
  if ( $ryhmaid == '' ) {
    forward("ryhmat.php",
	    'Ei voi muokata ryhmää.  Tarvittava parametri '.
	    '<code>ryhmaid</code> puuttuu.');
    exit;
  }

  if ( is_allowed_ryhma('write') ) {
    qry_ryhma_update($ryhmaid, $nimi, $kohde_nimi, $kohde_nimi2, $kohde_data, 
		     $liite_nimi, $kohde_liite_data);
    forward("ryhmat.php");
  } else {
    // Ei oikeuksia, palautetaan takaisin ryhmälistaan
    forward('ryhmat.php',
	    'Sinulla ei ole oikeutta muokata ryhmää.');
  }

?>
