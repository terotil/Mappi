<?php
///////////////////////////////////////////////////////////////////////////////
// Alkuun parit funktiot

///////////////////////////////////////////////////////////////////////////////
// Tulosta kohteen liitetieto
///////////////////////////////////////////////////////////////////////////////
// $id = kyseisen liitetiedon kohde_liiteid
///////////////////////////////////////////////////////////////////////////////
function print_kohde_liite($id, $postfix) {
  $kohde_liite = qry_kohde_liite($id);

  // Onko oikeus n‰hd‰
  if ( is_allowed($id, 'read', 'kohde_liiteid', $kohde_liite) ) {
    // Haetaan lista kielist‰, joille on olemassa k‰‰nnˆs
    $kaannokset = qry_trans_avail('kohde_liite', $id);

    // Onko oikeus muokata
    $link = ''; $linkl = '';
    if ( is_allowed($id, 'write', 'kohde_liiteid', $kohde_liite) ) {
      // Muokkauslinkki valittuun kieliversioon (tai oletuskieleen,
      // mik‰li valitunkielist‰ k‰‰nnˆst‰ ei ole.
      $linkl = '</a>';
      // Haetaan valittu kieli
      $lang = sessionvar_get('lang');
      if ( array_haskey($kaannokset, $lang['kieliid']) ) {
	$link="<a href=\"kohde_liite_edit.php?kohde_liiteid=$id\">"; 
      } else {
	$link="<a href=\"kohde_liite_edit.php?kohde_liiteid=$id&amp;lang=1\">";
      }
      // Tehd‰‰n muokkauslinkit suoraan kieliversioihin
      $postfix .= trans_links($kaannokset, '<a href="kohde_liite_edit.php?'.
			      'kohde_liiteid='.$id.'&lang=%d">%s</a>');
    } else {
      // Tyydyt‰‰n listaamaan kieliversiot
      $postfix .= trans_links($kaannokset, '<!-- %d -->%s');
    }

    // Liitetiedon otsikko
    printf('<dt>%s%s%s %s</dt><dd>'.
	   ($kohde_liite['data']?'<div class="kohdeliitedata">%s</div>':'%s'),
	   $link, $kohde_liite['liite_nimi'], $linkl, $postfix, 
	   format_liite($kohde_liite['data'], $kohde_liite['liiteid'],
                   $kohde_liite['mime']));

    // Onko viittauksia?
    $viittaukset = qry_viittaus_lista($id);
    $viittauksia_on = (count($viittaukset) > 0);
    if ( $viittauksia_on ) {
      // Tulostetaan viittaukset
      $viittaukset_html = array();
      foreach ( $viittaukset as $viittaus ) {
	$viittaus_kokonimi = 
	  kohde_kokonimi($viittaus['kn'], $viittaus['kn2'],
			 $viittaus['rkn'], $viittaus['rkn2'],
			 $viittaus['kohde_ryhmaid']);
	// Onko viitatun kohteen tietoja oikeus n‰hd‰?
	if ( is_allowed($viittaus['kohde_ryhmaid'], 'read', 'ryhmaid') or
	     $viittaus['kohdeid'] == sessionvar_get('uid') ) {
	  // Oikeudet kunnossa, annetaan linkki
	  $viittaukset_html[] = 
	    sprintf('<a href="kohde.php?kohdeid=%d">%s</a>',
		    $viittaus['kohdeid'], $viittaus_kokonimi);
	} else {
	  // Ei oikeuksia.  N‰ytet‰‰n pelk‰st‰‰n nimi listassa.
	  $viittaukset_html[] = $viittaus_kokonimi;
	}
      }
      printf('<p>Viittaukset <small>(%d kpl)</small><br />%s</p>',
	     count($viittaukset_html), join($viittaukset_html, ', '));
    }

    // Onko jaksoja?
    $jaksot = qry_jakso_lista($id);
    $jaksoja_on = (count($jaksot) > 0);
    if ( $jaksoja_on ) {
      $jaksot_html = array();
      $date_format = 'j.n.Y k\lo H:i';
      foreach ( $jaksot as $jakso ) {
	$jaksot_html[] = 
	  date($date_format, $jakso['alku']).' &ndash; '.
	  date($date_format, $jakso['loppu']).' '.$jakso['type'];
      }
      printf('<p>Jaksot <small>(%s)</small><br />%s</p>',
	     (jaksot_voimassa($jaksot)?'voimassa':'ei voimassa'),
	     join($jaksot_html, '<br />'));
    }

    // Onko tiedostoja?
    $tiedostot = qry_tiedosto_lista($id);
    $tiedostoja_on = (count($tiedostot) > 0);
    if ( $tiedostoja_on ) {
      include_once('libseitti-php/hrsize.function.php');
      $tiedostot_html = array();
      foreach ( $tiedostot as $tiedosto ) {
	$tiedostot_html[] = 
	  sprintf('<a href="lataa.php/%d/%s">%s</a> <small>(%s, %s)</small>',
		  $tiedosto['tiedostoid'], urlencode($tiedosto['nimi']),
		  $tiedosto['nimi'], hrsize($tiedosto['koko']), 
		  $tiedosto['mime']);
      }
      printf('<p>Tiedostot<br />%s</p>', join($tiedostot_html, '<br />'));
    }

    echo "</dd>";
  }
}

///////////////////////////////////////////////////////////////////////////////
// Tulosta kohteen tiedot
///////////////////////////////////////////////////////////////////////////////
function print_kohde($kohde, $ryhma) {
  $kohdeid = $kohde['kohdeid'];

  // Haetaan lista kielist‰, joille kohteesta on olemassa k‰‰nnˆs
  $kaannokset = qry_trans_avail('kohde', $kohdeid);

  // Tulostetaan perustiedot.  Otsikkoon muokkauslinkki.
  $link = ''; $linkl = ''; $tmp = '';
  if ( is_allowed($kohdeid, 'write', 'kohdeid', $kohde) ) {
    $lang = sessionvar_get('lang');
    $link = "kohde_edit.php?kohdeid=$kohdeid";
    if ( ! array_haskey($kaannokset, $lang['kieliid']) ) {
      $link .= "&amp;lang=1"; 
    }
    // Otsikon per‰‰n muokkauslinkit suoraan kieliversioihin.
    $tmp = trans_links($kaannokset, '<a href="kohde_edit.php?kohdeid='.
		       $kohdeid.'&lang=%d">%s</a>');
  }
  echo tag('h2', html_make_a($link, 'Perustiedot').$tmp);
  unset($tmp);

  global $settings;

  // T‰ydennet‰‰n erikoistpaukset
  $ryhma['kohde_mime'] = 
    'Sis‰ltˆmuoto kent‰lle "'.htmlspecialchars($ryhma['kohde_data']).'"';
  $ryhma['kohde_ryhma'] = 'Ryhm‰';
  $ryhma['kohde_leima_unix'] = 'Viimeksi muokattu';
  $kohde['ryhma'] = $ryhma['nimi'];
  $ryhma['kohde_kohde_kokonimi'] = 'Nimi raporteissa';

  // Perustiedot m‰‰ritelm‰listaksi
  foreach ( array('ryhma','nimi','nimi2','leima_unix','kohde_kokonimi',
		  'mime','data') as $k ) {
    $tmp[] = tag('dt', htmlspecialchars($ryhma["kohde_$k"]));
    $tmp[] = tag('dd', format_kohde($k, $kohde[$k], $kohde['mime']), 
		 array('class'=>'kohde'.$k));
  }
  echo tag('dl', join("\n", $tmp));

  // Haetaan kohteen liitetiedot
  $kl_lista = qry_kohde_liite_lista($kohdeid);
  // Niputetaan ryhmitt‰in
  $kl_lista = array_fold($kl_lista, array('liite_ryhmaid'));
  // Nuolet
  $uarr = '&#8593;';
  $darr = '&#8595;';
  // Tulostetaan
  foreach ( $kl_lista as $kl_ryhmaid => $kl_ryhma ) {
    $ryhma = qry_ryhma($kl_ryhmaid);
    echo "\n<h2>".$ryhma['nimi']."</h2>";
    echo "\n<dl>";
    $tmp_count = count($kl_ryhma);
    for ( $i=0 ; $i<$tmp_count ; $i++ ) {
      if ( $i > 0 ) {
	// Voidaan siirt‰‰ "ylˆs", eli vaihtaa paikkaa listalla
	// ylemm‰n kanssa.  Sama asia, kuin ylemm‰n siirt‰minen alas,
	// eli edellisen liitetiedon "alas".
	$ylos = str_replace($darr, $uarr, $alas);
      } else {
	$ylos = html_make_tag('span', $uarr, array('style'=>'color:gray',
						   'class'=>'nappi'));
      }
      if ( $i < $tmp_count-1 ) {
	// Voidaan siirt‰‰ "alas", eli vaihtaa paikkaa alemman kanssa.
	$alas = sprintf('<a class="nappi" href="kohde_liite_swap.php'.
			'?a=%d&amp;b=%d">%s</a>',
			$kl_ryhma[$i  ]['kohde_liiteid'],
			$kl_ryhma[$i+1]['kohde_liiteid'], $darr);
      } else {
	$alas = html_make_tag('span', $darr, array('style'=>'color:gray',
						   'class'=>'nappi'));
      }
      print_kohde_liite($kl_ryhma[$i]['kohde_liiteid'], "$ylos $alas");
    }
    echo '</dl>';
  }


  // Tulostetaan viittaukset muista kohteista
  $viittaukset = qry_inv_viittaus_lista($kohdeid);
  if ( count($viittaukset) > 0 ) {
    echo "<h2>Viittaukset muista kohteista</h2>\n<dl>\n";

    // K‰yd‰‰n l‰pi viittaukset ja ker‰t‰‰n $viittaukset_html
    // muuttujaan viittauksen kohteita valmiina html-koodina
    $viittaukset_html = array();
    $lid = '';
    foreach ( $viittaukset as $viittaus) {

      if ( $lid != $viittaus['liite_nimi'] ) {
	// Aina, kun viitaava liite vaihtuu, tulostetaan kertyneet
	// viittaukset ja seuraava liitetiedon nimi.  Lis‰ksi
	// tyhjennet‰‰n viittauslista.
	if ( count($viittaukset_html) > 0 ) {
	  echo "\n<dd>".join($viittaukset_html, ", \n")."</dd>";
	}
	$viittaukset_html = array();
	echo "\n<dt>".$viittaus['liite_nimi']."</dt>";
      }

      $tmp_kokonimi = kohde_kokonimi($viittaus['kn'], $viittaus['kn2'],
				     $viittaus['rkn'], $viittaus['rkn2'],
				     $viittaus['kohde_ryhmaid']);
      // Onko viittaavan kohteen tietoja oikeus n‰hd‰?
      if ( is_allowed($viittaus['kohde_ryhmaid'], 'read', 'ryhmaid') or
	   $viittaus['kohdeid'] == sessionvar_get('uid') ) {
	// Oikeudet kunnossa, annetaan linkki
	$viittaukset_html[] = 
	  "<a href=\"kohde.php?kohdeid=".$viittaus['kohdeid']."\">".
	  $tmp_kokonimi."</a>";
      } else {
	// Ei oikeuksia.  N‰ytet‰‰n pelk‰st‰‰n nimi listassa.
	$viittaukset_html[] = $tmp_kokonimi;
      }

      // Pistet‰‰n liitetiedon nimi talteen, ett‰ seuraavalla
      // kierroksella tiedet‰‰n, onko se vaihtunut
      $lid = $viittaus['liite_nimi'];
    }
    // Tulostetaan viimeisen liitteen viittaukset
    echo "\n<dd>".join($viittaukset_html, ", \n")."</dd>\n</dl>\n";
  }
}

// Kirjastot
require_once('mappi_headers.php');

// Haetaan parametri ja tsekataan, ett‰ homma on OK
$kohdeid = safe_retrieve('kohdeid', -1);
if ( $kohdeid < 0 or
     $kohdeid == '' ) {
  forward('kohteet.php');
  return;
}

// Haetaan kohteen tiedot
$kohde = qry_kohde($kohdeid);
if ( !$kohde ) { 
  print_errmsg_standalone('Kohdetta ei lˆydy.', $settings, 'Tietokanta'); 
  exit; 
}
$ryhma = qry_ryhma($kohde['ryhmaid']);
$kohde['kohde_kokonimi'] = 
  kohde_kokonimi($kohde['nimi'],$kohde['nimi2'],
		 $ryhma['kohde_nimi'],$ryhma['kohde_nimi2'],
		 $kohde['ryhmaid']);

require_once('MAPPI/html.php');
$pagename = html_make_a('kohteet.php?rid='.$kohde['ryhmaid'], 'Tietokanta').
  " &gt; {$kohde['kohde_kokonimi']}";

// Sivun otsikko
require_once('mappi_html-head.php');
require_once('mappi_html-body-begin.php');


// Oikeustarkistus
if ( is_allowed($kohdeid, 'read', 'kohdeid', $kohde) ) {
  // Katseluoikeudet kunnossa

  // Tulostetaan muokkauskomennot taulukkoon sivun yl‰laitaan
  echo '<div id="tools">';

  // Liitetiedon lis‰ys
  // Haetaan kaikki liitteet, joita on oikeus muokata
  $liitteet = qry_liite_lista('', 'write', 'r.nimi, l.nimi');
  // Jos liitteit‰ ei ole oikeutta lis‰t‰, ei suotta tulosteta koko lomaketta
  if ( count($liitteet) > 0 ) {
    ob_start();
    echo '  <input type="hidden" name="kohdeid" value="'."$kohdeid\">\n";
    echo html_make_dropdown_tab($liitteet, 'nimi', 'liiteid', 'liiteid', 
				false, false, 'ryhma_nimi');
    echo '<br><textarea name="pikateksti" style="width:100%"></textarea>'."\n";
    echo '  <br><input type="submit" value="Lis‰‰">'."\n";
    $tmp = ob_get_contents();
    ob_end_clean();
    echo html_toolform('Uusi liitetieto', 'post', 'kohde_liite_insert.php',
		       $tmp);
  }
  // Kohteen poisto
  ob_start();
  echo '  <input type="hidden" name="kohdeid" value="'."$kohdeid\">\n";
  echo "  Kohde: &quot;{$kohde['kohde_kokonimi']}&quot;\n";
  echo '  <input type="submit" value="Poista">'."\n";
  $tmp = ob_get_contents();
  ob_end_clean();
  echo html_toolform('Kohteen poisto', 'post', 'kohde_delete.php', $tmp);

  // K‰‰nnˆksen lis‰ys
  $ins = qry_trans_insertable('kohde', $kohdeid);
  if ( count($ins) > 0 ) {
    ob_start();
?><input type="hidden" name="action" value="insert">
<input type="hidden" name="table" value="kohde">
<input type="hidden" name="id" value="<?= $kohdeid ?>">
kieli <?php
     require_once('MAPPI/html.php');
     echo html_make_dropdown($ins, 'kieliid'); ?><br>
<input type="submit" value="Lis‰‰">
<?php {}
     $tmp = ob_get_contents();
     ob_end_clean();
     echo html_toolform('Uusi k‰‰nnˆs', 'post', 'trans.php', $tmp);
  }

  // K‰‰nnˆksen poisto
  if ( kaannos_olemassa('kohde', $kohdeid) ) {
    ob_start();
?><input type="hidden" name="action" value="delete">
<input type="hidden" name="table" value="kohde">
<input type="hidden" name="id" value="<?= $kohdeid ?>">
<input type="hidden" name="kieliid" value="<?php  
  $lang = sessionvar_get('lang');
  echo $lang['kieliid'];
?>">
<input type="submit" value="Poista k‰‰nnˆs &quot;<?php echo $lang['nimi'] ?>&quot;">
<?php {}
    $tmp = ob_get_contents();
    ob_end_clean();
    echo html_toolform('K‰‰nnˆksen poisto', 'post', 'trans.php', $tmp);
  }

  echo "\n</div>\n";

  print_kohde($kohde, $ryhma);

} else {
  // Kohteen tietoihin ei ole oikeutta.
  print_errmsg('Sinulla ei ole oikeutta n‰hd‰ kohteen tietoja!');
}

  require_once('mappi_html-body-end.php');
?>
