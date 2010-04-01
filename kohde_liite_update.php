<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  $kohde_liiteid = safe_retrieve('kohde_liiteid');
  $data = safe_retrieve('data');
  $mime = safe_retrieve('mime');

  // Katastrofitilanne, ei p�ivitett�v�n kohde_liitteen id:t�.
  // Pelastaudutaan kohdelistalle.
  if ( $kohde_liiteid == '' ) {
    forward('kohteet.php', 
	    'Ei voi p�ivitt�� liitetietoa.  Tarvittava '.
	    'parametri <code>kohde_liiteid</code> puuttuu.');
  }

  // P�ivitet��n kohdeliitteen tiedot
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid') ) {
    qry_kohde_liite_update($kohde_liiteid, $data, $mime);
  }

  // Heitet��n takaisin muokkaussivulle
  forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid");
?>
