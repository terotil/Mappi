<?php
  $forwardpage = true;  
  require_once('mappi_headers.php');

  $ryhmaid = safe_retrieve('ryhmaid');

  // Jos ryhmaid on hukassa, ei voi muuta, kuin palata listalle
  if ( $ryhmaid == '' ) {
    forward('ryhmat.php',
	    'Ei voi poistaa ryhm��.  Tarvittava parametri '.
	    '<code>ryhmaid</code> puuttuu.');
    return;
  }

  if ( is_allowed_ryhma('write') ) {
    if ( qry_ryhma_exists($ryhmaid) ) {
      // Ryhm��n kuuluvaa tavaraa on, ei voi h�vitt��
      forward("ryhma_edit.php?ryhmaid=$ryhmaid",
	      'Et voi poistaa k�yt�ss�olevaa ryhm��.');
    } else {
      // 'Orpo' ryhm�, voi h�vitt��
      qry_ryhma_delete($ryhmaid);
      forward("ryhmat.php");
    }
  } else {
    // Ei oikeuksia, palautetaan takaisin ryhm�n tietoihin viestin
    // kera.
    forward("ryhma_edit.php?ryhmaid=$ryhmaid",
	    'Sinulla ei ole oikeutta poistaa t�t� ryhm��!');
  }

?>
