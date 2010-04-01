<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('tiedostoid', 'kohde_liiteid') as $varname ) {
    $$varname = safe_retrieve_gp($varname);
  }

  if ( $tiedostoid == '' ) {
    forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid",
	    'Ei voi poistaa viittausta.  Tarvittava parametri '.
	    '<code>tiedostoid</code> puuttuu.');
    return;
  }

  // Poistetaan tiedosto
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid') ) {
    qry_tiedosto_delete($tiedostoid);
  }

  // Heitetään takaisin muokkaussivulle
  forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid");
?>
