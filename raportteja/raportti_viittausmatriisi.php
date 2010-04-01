<?php //;Yhteenvedot;Viittausmatriisi;
require_once('MAPPI/html.php');
  $pagename = html_make_a('raportit.php', 'Raportit');
  $pagename .= ' &gt; Viittausmatriisi';
  require_once('MAPPI/mappi_lib_raportit.php');
  require_once('MAPPI/html.php');
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

// Ryhmät
$ryhmat = qry_ryhma_lista('', PHP_INT_MAX);
// Liitteet
$liitteet = qry_liite_lista();

// viittaava ryhmä, ryhmaid
$fid = intval(safe_retrieve_gp('fid', $ryhmat[0]['ryhmaid'], true));
// viitattu ryhmä, ryhmaid
$tid = intval(safe_retrieve_gp('tid', $ryhmat[1]['ryhmaid'], true)); 
// viittaava liite, liiteid
$vid = sanitize_intlist(safe_retrieve_gp('vid', $liitteet[0]['liiteid'], true)); 
$xlim = intval(safe_retrieve_gp('xlim', '30', true));
$ylim = intval(safe_retrieve_gp('ylim', '30', true));

echo html_make_form('get', '', 
 'Viittaavat kohteet '.html_make_dropdown_tab($ryhmat, 'nimi', 'ryhmaid', 'fid', "$fid")."<br>\n".
 'Viitatut kohteet '.html_make_dropdown_tab($ryhmat, 'nimi', 'ryhmaid', 'tid', "$tid")."<br>\n".
 'Viittaavat liitteet '.html_make_dropdown_tab($liitteet, 'ryhma_liite_nimi', 'liiteid', 'vid', "$vid")."<br>\n".
 'Viittaavia max '.html_make_input('text', 'ylim', $ylim)."<br>\n".
 'Viitattuja max '.html_make_input('text', 'xlim', $xlim)."<br>\n".
 html_make_input('submit', 'submit', 'Aseta')
 );

// Haetaan listat riveiksi ja sarakkeiksi tulevista kohteista
$viittaavat    = qry_kohde_lista($fid, '', $ylim, 0); // Y
$viitatut      = qry_kohde_lista($tid, '', $xlim, 0); // X

if ( count($viittaavat)>0 && count($viitatut)>0 ) {
  // Tulostetaan viittaustaulukko
  print_as_table(viittaustaulukko($viittaavat, $viitatut, $vid), 
		 'class="qtable"');
} else {
  // Viitattavia tai viitattuja ei ole
  if ( count($viittaavat) == 0 ) {
    echo errmsg('Ei viittaavia kohteita.');
  }
  if ( count($viitatut) == 0 ) {
    echo errmsg('Ei viitattavia kohteita.');
  }
}

  require_once('mappi_html-body-end.php');
?>
