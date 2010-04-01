<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohdeid','rid','nimi','nimi2','data','mime') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Katastrofitilanne, ei p�ivitett�v�n kohteen id:t�.
  // Pelastaudutaan kohdelistalle.
  if ( $kohdeid == '' ) {
    forward('kohteet.php', 
	    errmsg('Ei voi p�ivitt�� kohteen tietoja.  Tarvittava '.
		   'parametri <code>kohdeid</code> puuttuu.'));
  }

  // P�ivitet��n kohdeliitteen tiedot
  if ( is_allowed($rid, 'write', 'ryhmaid') ) {
    qry_kohde_update($kohdeid, $rid, $nimi, $nimi2, $data, $mime);
  }

  // Heitet��n takaisin muokkaussivulle
  if ( $nimi == '' && $nimi2 == '' ) {
    // herjataan tyhj�st� nimest�
    $ryhma = qry_ryhma($rid);
    if ( $ryhma['kohde_nimi'] != '' && $ryhma['kohde_nimi'][0] != '-' ) 
      $kentat = '"'.$ryhma['kohde_nimi'].'"';
    if ( $ryhma['kohde_nimi2'] != '' && $ryhma['kohde_nimi2'][0] != '-' ) {
      if ( $kentat != '' ) $kentat .= ' tai ';
      $kentat .= '"'.$ryhma['kohde_nimi2'].'"';
    }
    forward("kohde.php?kohdeid=$kohdeid",
	    errmsg("Kohteelta puuttuu nimi.  T�yt� perustiedoista $kentat"));
  } else {
    forward("kohde.php?kohdeid=$kohdeid");
  }

?>
