<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach ( array('kohde_liiteid', 'old_pw', 'old_uname', 'new_pw', 'new_uname') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Katastrofitilanne, ei p�ivitett�v�n kohde_liitteen id:t�.
  // Pelastaudutaan kohdelistalle.
  if ( $kohde_liiteid == '' ) {
    forward('kohteet.php', 
	    'Ei voi p�ivitt�� liitetietoa.  Tarvittava '.
	    'parametri <code>kohde_liiteid</code> puuttuu.');
  }

  // Tarkistetaan muutokset
  $muuttunut = false;

  // K�ytt�j�nimi
  if ( $old_uname == $new_uname or
       $new_uname == '' ) {
    // Ei ole muuttunut
    $update_uname = $old_uname;
  } else {
    // On muuttunut
    $update_uname = $new_uname;
    $muuttunut = true;
  }

  // Salasana
  if ( $new_pw ) {
    // Uusi salasana annettuna
    $update_pw = md5($new_pw);
    $muuttunut = true;
  } else {
    // Ei ole muuttunut
    $update_pw = $old_pw;
  }

  // P�ivitet��n kohdeliitteen tiedot, jos niit� on muutettu
  if ( $muuttunut and
       is_allowed($kohde_liiteid, 'write', 'kohde_liiteid') == 1 ) {
    qry_kohde_liite_update($kohde_liiteid, 
			   addslashes(make_login($update_uname, $update_pw)),
			   'text/x-login');
  }

  // Heitet��n takaisin muokkaussivulle
  forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid",
	  $muuttunut?'Salasana muutettu':'');
?>
