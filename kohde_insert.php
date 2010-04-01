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
	    'Ei voi lisätä uutta kohdetta.  Tarvittava '.
	    'parametri <code>ryhmaid</code> puuttuu.');
    return;
  }

  // Haetaan erikoistapaukset käsittelevät lisäyssivut käymällä läpi
  // kaikki kohde_insert_*.php -tiedostot ja tekemällä niiden
  // otsikkotietojen avulla lisäyssivuista ryhmaid:n mukaan indeksoitu
  // lista
  $insert_pages = firstlines('^kohde_insert_.*\.php$');
  // Puretaan auki pilkutetut ryhmaid-listat.
  foreach (array_keys($insert_pages) as $key) {
    if ( count($rids = split(',',$insert_pages[$key][1])) > 1 ) {
      // Ryhmäkentässä on monta ryhmaid:tä pilkkuerotellussa listassa
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
// käännöksiä.
aseta_istunnon_kieli(1);

  if ( array_haskey($insert_pages, $ryhmaid) ) {
    // Erikoistapaus, jatketaan kustomoidulle lisäyssivulle FIXME:
    // Mitä tehdä silloin, kun samaan ryhmään on ilmoittautunut monta
    // lisäyssivua?  Tällä hetkellä vain otetaan tylysti ensimmäinen.
    forward($insert_pages[$ryhmaid][0][0]."?ryhmaid=$ryhmaid");
    return;
  } else {
    // Vakiokauraa.  Lisätään kohde_liite ihan tavallisesti ja
    // jatketaan muokkaussivulle, mikäli oikeudet ovat kohdallaan
    if ( is_allowed($ryhmaid, 'write', 'ryhmaid') ) {
      $kohdeid = qry_kohde_insert($ryhmaid, $nimi, $nimi2, $data, false);
      forward("kohde_edit.php?kohdeid=$kohdeid");
    } else {
      // Ei oikeuksia, palautetaan takaisin kohdelistalle viestin
      // kera.
      forward("kohteet.php?rid=$ryhmaid", 
	      'Sinulla ei ole oikeutta lisätä valitsemaasi liitetietoa.');
    }
  }
?>
