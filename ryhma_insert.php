<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('nimi') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  if ( is_allowed_ryhma('write') ) {
    // Lis�t��n uusi ryhm�
    $ryhmaid = qry_ryhma_insert($nimi);
    // Jos jotkut oikeudet on annettu kaikkiin ryhmiin, lis�t��n samat
    // oikeudet my�s luotuun uutteen ryhm��n.
    foreach ( array('read','write') as $action ) {
      // Ryhm�id -1 toimii oikeuslistassa * -merkkin�.
      if ( in_array(-1, sessionvar_get($action)) ) {
	array_unshift(sessionvar_get($action), $ryhmaid);
      }
    }
    forward("ryhma_edit.php?ryhmaid=$ryhmaid");
  } else {
    // Ei oikeuksia, palautetaan takaisin ryhm�listalle
    forward("ryhmat.php",
	    'Sinulla ei ole oikeutta lis�t� ryhm��!');
  }

?>
