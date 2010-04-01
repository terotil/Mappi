<?php //;Tulostus;Rekursiivinen kohdenäkymä;
  $pagename = 'Rekursiivinen kohdenäkymä';
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');

$piiloliitteet = join(',',$settings['piiloliite']);
$root_kohdeid = safe_retrieve('kohdeid', -1, true);
$nayta_viittaukset = safe_retrieve('viittaukset', 'kaikki', true); // kaikki|eimuualta|ei
$haluttu_ryhmaid = safe_retrieve('rid', '1', true);

if ( $root_kohdeid > 0 ) {
  // Tulostetaan kohde

  $kidl = array();
  $kidl[] = $root_kohdeid;
  $i = 0;

  // Haetaan riippuvuudet
  do {
    // Haetaan riippu
    $depends_on = qry("
      SELECT DISTINCT kohde.kohdeid AS kohdeid
      FROM kohde, kohde_liite, viittaus
      WHERE 
        kohde.kohdeid = viittaus.kohdeid AND
        viittaus.kohde_liiteid = kohde_liite.kohde_liiteid AND
        kohde_liite.liiteid NOT IN ($piiloliitteet) AND
        kohde_liite.kohdeid = {$kidl[$i]}");
    foreach ( array_keys($depends_on) as $k ) {
      $lisaa_kohdeid = true;
      foreach ( array_keys($kidl) as $j) {
	if ( $kidl[$j] == $depends_on[$k]['kohdeid'] ) {
	  $lisaa_kohdeid = false;
	}
      }
      if ( $lisaa_kohdeid ) {
	$kidl[] = $depends_on[$k]['kohdeid'];
      }
    }
    $i++;
  } while ( count($kidl) > $i  );

echo "<html><head><title>&nbsp;</title></head><body>\n";
$ryhmat = qry_ryhma_lista('',99);
$ryhmat = array_fold($ryhmat, array('ryhmaid'));

foreach ( $kidl as $kohdeid ) {
  // Haetaan kohde
  $kohde = qry_kohde($kohdeid);

  // Kohteen perustiedot
$nimi = kohde_kokonimi($kohde['nimi'], $kohde['nimi2'], '', '', $kohde['ryhmaid']);
  echo "\n\n<a name=\"kohde{$kohde['kohdeid']}\"></a>\n";
  echo "<h1>$nimi</h1>\n";

  echo "<p>".nl2br($kohde['data'])."</p>\n";

  // Haetaan liitetiedot
  $liitteet = 
    qry_kohde_liite_lista($kohdeid, 'kohde_liite.jarjestys', $piiloliitteet);
  $liitteet = array_fold($liitteet, array('liite_ryhmaid'));

  // Tulostetaan liitetiedot
  foreach ( $liitteet as $ryhmaid => $liiteryhma ) {
    echo "<h2>{$ryhmat[$ryhmaid][0]['nimi']}</h2>\n";
    foreach ( $liiteryhma as $liitetieto ) {
      // Otsikko ja data
      echo "<h3>{$liitetieto['liite_nimi']}</h3>\n";
      echo "<p>".nl2br($liitetieto['data'])."</p>\n";
      if ( $nayta_viittaukset == 'kaikki' ||
	   $nayta_viittaukset == 'eimuualta' ) {
	// Viittaukset
	$viittaukset = qry_viittaus_lista($liitetieto['kohde_liiteid']);
	$viittaukset_html = array();
	foreach ( $viittaukset as $viittaus ) {
	  $viittaukset_html[] = 'katso &quot;<a href="#kohde' . 
	    $viittaus['kohdeid'] . '">' . 
	    kohde_kokonimi($viittaus['kn'],$viittaus['kn2'],
			   $viittaus['rkn'],$viittaus['rkn2'],
			   $viittaus['kohde_ryhmaid']) . '</a>&quot;';
	}
	if ( count($viittaukset_html) > 0 ) {
	  echo "<p>";
	  echo join(', ', $viittaukset_html);
	  echo "</p>\n";
	}
      }
    }
  }

  if ( $nayta_viittaukset == 'kaikki' ) {
    // Viittaukset muualta, ei linkkejä!
    $muualta = qry("
      SELECT k.nimi, k.nimi2, k.ryhmaid
      FROM kohde k, kohde_liite kl, viittaus v
      WHERE
	k.kohdeid = kl.kohdeid AND
	kl.liiteid NOT IN ($piiloliitteet) AND
	kl.kohde_liiteid = v.kohde_liiteid AND
	v.kohdeid = $kohdeid");

    if ( count($muualta) > 0 ) {
      echo "<h2>Viittaukset muualta</h2>\n";
      echo "<p>Kohteeseen &quot;$nimi&quot; on viittaus myös seuraavista kohteista: ";
      $viittaus_html = array();
      foreach ( $muualta as $viittaus ) {
	$viittaus_html[] = 
	  kohde_kokonimi($viittaus['nimi'], $viittaus['nimi2'], 
			 '', '', $viittaus['ryhmaid']);
      }
      echo join(', ', $viittaus_html);
      echo "</p>\n";
    }
  }
}

} else {
  // Heitetään edelleenohjaus kohteen valintaan
  forward("kohteet.php?rid=$haluttu_ryhmaid&fpage=raportti_rekursiivinen.php");
}

?>
