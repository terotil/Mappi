<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohdeid', 'liiteid', 'pikateksti') as $varname ) {
    $$varname = safe_retrieve($varname);
  }

  // Jos kohdeid on hukassa, ei voida tehd� yhtik�s mit��n.
  // Pelastetaan umpikuja heitt�m�ll� oletusarvoilla kohdelistalle
  if ( $kohdeid == '' ) {
    forward("kohteet.php", 
	    'Ei voi lis�t� liitetietoa.  Tarvittava '.
	    'parametri <code>kohdeid</code> puuttuu.');
    return;
  }

  // Erikoistapaukset
  $insert_pages = array(-1=>'mallitapaus.php');

  if ( array_haskey($insert_pages, $liiteid) ) {
    // Erikoistapaus, jatketaan kustomoidulle lis�yssivulle
    forward($insert_pages[$liiteid].
	    "?kohdeid=$kohdeid&liiteid=$liiteid&pikateksti=".
	    urlencode($pikateksti));
    return;
  } else {
    // Vakiokauraa.  Lis�t��n kohde_liite ihan tavallisesti ja
    // jatketaan muokkaussivulle, mik�li oikeudet ovat kohdallaan
    if ( is_allowed($kohdeid, 'write', 'kohdeid') and
	 is_allowed($liiteid, 'write', 'liiteid') ) {
      $kohde_liiteid = 
	qry_kohde_liite_insert($kohdeid, $liiteid, $pikateksti, false);
      if ( $pikateksti == '' ) {
	forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid");
      } else {
	forward("kohde.php?kohdeid=$kohdeid");
      }
    } else {
      // Ei oikeuksia, palautetaan takaisin kohdesivulle
      forward("kohde.php?kohdeid=$kohdeid",
	      'Sinulla ei ole oikeutta lis�t� valitsemaasi liitetietoa!');
    }
  }
?>
