<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('nimi') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  if ( is_allowed_ryhma('write') ) {
    // Lisätään uusi ryhmä
    $ryhmaid = qry_ryhma_insert($nimi);
    // Jos jotkut oikeudet on annettu kaikkiin ryhmiin, lisätään samat
    // oikeudet myös luotuun uutteen ryhmään.
    foreach ( array('read','write') as $action ) {
      // Ryhmäid -1 toimii oikeuslistassa * -merkkinä.
      if ( in_array(-1, sessionvar_get($action)) ) {
	array_unshift(sessionvar_get($action), $ryhmaid);
      }
    }
    forward("ryhma_edit.php?ryhmaid=$ryhmaid");
  } else {
    // Ei oikeuksia, palautetaan takaisin ryhmälistalle
    forward("ryhmat.php",
	    'Sinulla ei ole oikeutta lisätä ryhmää!');
  }

?>
