<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('kohdeid', 'kohde_liiteid', 'kohde_nimi') as $varname ) {
    $$varname = safe_retrieve($varname);
  }
  $ehdotettu_rid = safe_retrieve('rid', -1);

  // Pelastautumistemput sen varalta, jos parametreja puuttuu.
  if ( $kohde_liiteid == '' ) {
    forward('kohteet.php',
	    'Ei voi lisätä viittausta.  Tarvittava parametri '.
	    '<code>kohde_liiteid</code> puuttuu.');
    return;
  }

  $kohteet = array();
  if ( $ehdotettu_rid >= 0 ) {
    // Jos ehdotus on annettu, se hyväksytään
    $rid = $ehdotettu_rid;
  } else {
    // Muuten otetaan kaikki sallitut
    $rid = join(',',sessionvar_get('read'));
  }

  // Onko nimirajaus annettu?
  if ( $kohde_nimi != '' ) {
    // Etsitään viitattavaa kohdetta annetun nimen perusteella
    $kohteet = qry_kohde_lista($rid, $kohde_nimi);
    if ( count($kohteet) == 1 ) {
      // Haun perusteella löytyi yksikäsitteinen kohde
      $kohdeid = $kohteet[0]['kohdeid'];
    }
  }


  if ( $kohdeid != '' ) {
    // Tarvittavat tiedot on kasassa (koska kohde_liiteid:n
    // olemassaolo on jo tarkastettu), joten lisätään viittaus ja
    // jatketaan matkaa takaisin kohdeliitteen muokkaussivulle
    qry_viittaus_insert($kohde_liiteid, $kohdeid);
    forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid");
    return;
  }

  // Hyvästä yrityksestä huolimatta kohdeid puuttuu edelleen
  // Jatketaan kohteen valintaan listalta.
  forward("kohteet.php?fpage=viittaus_insert.php&name=kohde_liiteid".
	  "&value=$kohde_liiteid&pn=".base64_encode('Valitse viitattava kohde').
	  "&haku=".urlencode($kohde_nimi)."&rid=$rid");
?>
