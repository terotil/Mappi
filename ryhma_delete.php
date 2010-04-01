<?php
  $forwardpage = true;  
  require_once('mappi_headers.php');

  $ryhmaid = safe_retrieve('ryhmaid');

  // Jos ryhmaid on hukassa, ei voi muuta, kuin palata listalle
  if ( $ryhmaid == '' ) {
    forward('ryhmat.php',
	    'Ei voi poistaa ryhmää.  Tarvittava parametri '.
	    '<code>ryhmaid</code> puuttuu.');
    return;
  }

  if ( is_allowed_ryhma('write') ) {
    if ( qry_ryhma_exists($ryhmaid) ) {
      // Ryhmään kuuluvaa tavaraa on, ei voi hävittää
      forward("ryhma_edit.php?ryhmaid=$ryhmaid",
	      'Et voi poistaa käytössäolevaa ryhmää.');
    } else {
      // 'Orpo' ryhmä, voi hävittää
      qry_ryhma_delete($ryhmaid);
      forward("ryhmat.php");
    }
  } else {
    // Ei oikeuksia, palautetaan takaisin ryhmän tietoihin viestin
    // kera.
    forward("ryhma_edit.php?ryhmaid=$ryhmaid",
	    'Sinulla ei ole oikeutta poistaa tätä ryhmää!');
  }

?>
