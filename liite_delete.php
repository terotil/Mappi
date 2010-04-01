<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  $liiteid = safe_retrieve('liiteid');

  // Jos liiteid on hukassa, ei voi muuta, kuin palata listalle
  if ( $liiteid == '' ) {
    forward("liitteet.php", 
	    'Ei voi poistaa liitettä.  Tarvittava parametri '.
	    '<code>liiteid</code> puuttuu.');
    return;
  }

  if ( is_allowed($liiteid, 'write', 'liiteid') ) {
    if ( qry_kohde_liite_exists($liiteid) ) {
      // Liitetietoja on, ei voi hävittää
      forward("liite_edit.php?liiteid=$liiteid",
	      'Et voi poistaa käytössäolevaa liitetietoa.');
    } else {
      // 'Orpo' liitetieto, voi hävittää
      qry_liite_delete($liiteid);
      forward("liitteet.php");
    }
  } else {
    // Ei oikeuksia, palautetaan takaisin liitteen tietoihin
    forward("liite_edit.php?liiteid=$liiteid",
	    'Sinulla ei ole oikeutta poistaa liitettä.');
  }

?>
