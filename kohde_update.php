<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohdeid','rid','nimi','nimi2','data','mime') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Katastrofitilanne, ei päivitettävän kohteen id:tä.
  // Pelastaudutaan kohdelistalle.
  if ( $kohdeid == '' ) {
    forward('kohteet.php', 
	    errmsg('Ei voi päivittää kohteen tietoja.  Tarvittava '.
		   'parametri <code>kohdeid</code> puuttuu.'));
  }

  // Päivitetään kohdeliitteen tiedot
  if ( is_allowed($rid, 'write', 'ryhmaid') ) {
    qry_kohde_update($kohdeid, $rid, $nimi, $nimi2, $data, $mime);
  }

  // Heitetään takaisin muokkaussivulle
  if ( $nimi == '' && $nimi2 == '' ) {
    // herjataan tyhjästä nimestä
    $ryhma = qry_ryhma($rid);
    if ( $ryhma['kohde_nimi'] != '' && $ryhma['kohde_nimi'][0] != '-' ) 
      $kentat = '"'.$ryhma['kohde_nimi'].'"';
    if ( $ryhma['kohde_nimi2'] != '' && $ryhma['kohde_nimi2'][0] != '-' ) {
      if ( $kentat != '' ) $kentat .= ' tai ';
      $kentat .= '"'.$ryhma['kohde_nimi2'].'"';
    }
    forward("kohde.php?kohdeid=$kohdeid",
	    errmsg("Kohteelta puuttuu nimi.  Täytä perustiedoista $kentat"));
  } else {
    forward("kohde.php?kohdeid=$kohdeid");
  }

?>
