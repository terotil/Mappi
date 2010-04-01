<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohdeid', 'rid', 'nimi', 'nimi2', 
		 'rights_read', 'rights_write', 'ryhmat') as $varname ) {
    $$varname = safe_retrieve($varname);
    //    echo "$varname = '".$$varname."'<br>"; // DEBUG
  }
  // luku- ja kirjoitusoikeuslistojen t�ytyy olla taulukoita, muuten
  // oikeuksien s��t� kosahtaa
  foreach (array('rights_read', 'rights_write') as $varname) {
    if ( $$varname == '' ) {
      $$varname = array();
    }
  }

  // Katastrofitilanne, ei p�ivitett�v�n kohteen id:t�.
  // Pelastaudutaan kohdelistalle.
  if ( $kohdeid == '' ) {
    forward('kohteet.php', 
	    'Ei voi p�ivitt�� kohteen tietoja.  Tarvittava '.
	    'parametri <code>kohdeid</code> puuttuu.');
  }

  // Automaattisesti lukuoikeus niihin ryhmiin, joihin on my�s
  // kirjoitusoikeus
  $rights_read = array_unique(array_merge($rights_read, $rights_write));

  // Muodostetaan data
  $data = write_rights($rights_read, $rights_write, $ryhmat);

  // P�ivitet��n kohteen tiedot
  if ( is_allowed($rid, 'write', 'ryhmaid') ) {
    qry_kohde_update($kohdeid, $rid, $nimi, $nimi2, addslashes($data), 
		     'text/x-accesscontrol');
  }

  // Heitet��n takaisin kohteen tietoihin
  forward("kohde.php?kohdeid=$kohdeid");
?>
