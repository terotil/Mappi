<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohde_liiteid', 'kohdeid') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  if ( $kohdeid == '' ) {
    forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid",
	    'Ei voi poistaa viittausta.  Tarvittava parametri '.
	    '<code>kohdeid</code> puuttuu.');
    return;
  }
  if ( $kohde_liiteid == '' ) {
    forward('kohteet.php',
	    'Ei voi poistaa viittausta.  Tarvittava parametri '.
	    '<code>kohde_liiteid</code> puuttuu.');
    return;
  }

  // Poistetaan viittaus
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid') ) {
    qry_viittaus_delete($kohde_liiteid, $kohdeid);
  }

  // Heitetään takaisin muokkaussivulle
  forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid");
?>
