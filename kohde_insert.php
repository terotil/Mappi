<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  foreach (array('ryhmaid', 'nimi', 'nimi2', 'data') as $varname ) {
    $$varname = safe_retrieve($varname);
  }
  if ( $nimi == '' ) $nimi = 'Uusi';
  if ( $ryhmaid == '' ) {
    // Kriittinen parametri puuttuu, homma ei pelaa
    forward("kohteet.php", 
	    'Ei voi lis�t� uutta kohdetta.  Tarvittava '.
	    'parametri <code>ryhmaid</code> puuttuu.');
    return;
  }

  // Haetaan erikoistapaukset k�sittelev�t lis�yssivut k�ym�ll� l�pi
  // kaikki kohde_insert_*.php -tiedostot ja tekem�ll� niiden
  // otsikkotietojen avulla lis�yssivuista ryhmaid:n mukaan indeksoitu
  // lista
  $insert_pages = firstlines('^kohde_insert_.*\.php$');
  // Puretaan auki pilkutetut ryhmaid-listat.
  foreach (array_keys($insert_pages) as $key) {
    if ( count($rids = split(',',$insert_pages[$key][1])) > 1 ) {
      // Ryhm�kent�ss� on monta ryhmaid:t� pilkkuerotellussa listassa
      foreach ( $rids as $rid ) {
        $insert_pages[$key][1] = $rid;
        $insert_pages[] = $insert_pages[$key];
      }
      unset($insert_pages[$key]);
    }
  }
  // Indeksoidaan uudelleen ryhmaid:n mukaan
  $insert_pages = array_fold($insert_pages, array(1));

// Vaihdetaan oletuskielelle, koska juuri luodulla kohteella ei ole
// k��nn�ksi�.
aseta_istunnon_kieli(1);

  if ( array_haskey($insert_pages, $ryhmaid) ) {
    // Erikoistapaus, jatketaan kustomoidulle lis�yssivulle FIXME:
    // Mit� tehd� silloin, kun samaan ryhm��n on ilmoittautunut monta
    // lis�yssivua?  T�ll� hetkell� vain otetaan tylysti ensimm�inen.
    forward($insert_pages[$ryhmaid][0][0]."?ryhmaid=$ryhmaid");
    return;
  } else {
    // Vakiokauraa.  Lis�t��n kohde_liite ihan tavallisesti ja
    // jatketaan muokkaussivulle, mik�li oikeudet ovat kohdallaan
    if ( is_allowed($ryhmaid, 'write', 'ryhmaid') ) {
      $kohdeid = qry_kohde_insert($ryhmaid, $nimi, $nimi2, $data, false);
      forward("kohde_edit.php?kohdeid=$kohdeid");
    } else {
      // Ei oikeuksia, palautetaan takaisin kohdelistalle viestin
      // kera.
      forward("kohteet.php?rid=$ryhmaid", 
	      'Sinulla ei ole oikeutta lis�t� valitsemaasi liitetietoa.');
    }
  }
?>
