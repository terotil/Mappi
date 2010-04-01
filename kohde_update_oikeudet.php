<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohdeid', 'rid', 'nimi', 'nimi2', 
		 'rights_read', 'rights_write', 'ryhmat') as $varname ) {
    $$varname = safe_retrieve($varname);
    //    echo "$varname = '".$$varname."'<br>"; // DEBUG
  }
  // luku- ja kirjoitusoikeuslistojen täytyy olla taulukoita, muuten
  // oikeuksien säätö kosahtaa
  foreach (array('rights_read', 'rights_write') as $varname) {
    if ( $$varname == '' ) {
      $$varname = array();
    }
  }

  // Katastrofitilanne, ei päivitettävän kohteen id:tä.
  // Pelastaudutaan kohdelistalle.
  if ( $kohdeid == '' ) {
    forward('kohteet.php', 
	    'Ei voi päivittää kohteen tietoja.  Tarvittava '.
	    'parametri <code>kohdeid</code> puuttuu.');
  }

  // Automaattisesti lukuoikeus niihin ryhmiin, joihin on myös
  // kirjoitusoikeus
  $rights_read = array_unique(array_merge($rights_read, $rights_write));

  // Muodostetaan data
  $data = write_rights($rights_read, $rights_write, $ryhmat);

  // Päivitetään kohteen tiedot
  if ( is_allowed($rid, 'write', 'ryhmaid') ) {
    qry_kohde_update($kohdeid, $rid, $nimi, $nimi2, addslashes($data), 
		     'text/x-accesscontrol');
  }

  // Heitetään takaisin kohteen tietoihin
  forward("kohde.php?kohdeid=$kohdeid");
?>
