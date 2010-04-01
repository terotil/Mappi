<?php
  require_once('mappi_headers.php');
global $settings;

  $kohdeid = safe_retrieve('kohdeid');
  $kohde   = qry_kohde($kohdeid);

  // Haetaan erikoistapaukset k�sittelev�t muokkaussivut k�ym�ll� l�pi
  // kaikki kohde_edit_*.php -tiedostot ja tekem�ll� niiden
  // otsikkotietojen avulla lis�yssivuista ryhmaid:n mukaan indeksoitu
  // lista
  $edit_pages = firstlines('^kohde_edit_.*\.php$');
  $edit_pages = array_fold($edit_pages, array(1));

  if ( array_haskey($edit_pages, $kohde['ryhmaid']) ) {
    // FIXME: Mit� tehd� silloin, kun samaan ryhm��n on ilmoittautunut
    // monta muokkaussivua?  T�ll� hetkell� vain otetaan tylysti
    // ensimm�inen.
    forward($edit_pages[$kohde['ryhmaid']][0][0]."?".
	    $_SERVER['QUERY_STRING']);
    return;
  }

  $ryhma = qry_ryhma($kohde['ryhmaid']);

require_once('MAPPI/html.php');

  $pagename = 
  html_make_a('kohteet.php?rid='.$kohde['ryhmaid'], 'Tietokanta').' &gt; ' . 
  html_make_a("kohde.php?kohdeid=$kohdeid", 
	      kohde_kokonimi($kohde['nimi'], $kohde['nimi2'],'', '',
                             $kohde['ryhmaid'])) . 
  ' &gt; Muokkaa';

  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

// Oikeustarkistus
if ( is_allowed($kohdeid, 'write', 'kohdeid', $kohde) ) {
  // Muokkausoikeudet kunnossa

  // Haetaan kaikki kohderyhm�t, joita on oikeus muokata
  $ryhmat = qry_ryhma_lista('', DB_HUGEINT, 0, 'write');

  // Haetaan sarakenimet
  $nimet = qry_kohde_colnames($kohde['ryhmaid']);

  // Muunnokset (*)
  $conversion_list = get_conversion_link_list('kohde',$kohdeid,$kohde['mime']);
  echo '<div id="tools">';
  if ( count($conversion_list)>0 ) {
    echo html_controlgroup('Sis�lt�muoto', 
			   "Muunna tekstisis�lt� muotoon: ".
			   join(", \n", $conversion_list));
  }
  echo '</div>';
  
  // Tulostetaan lomake
  echo "\n".'<form method="post" action="kohde_update.php">';
  echo "\n<input type=\"hidden\" name=\"kohdeid\" value=\"$kohdeid\">";
  echo "\n<dl>";
  echo "\n<dt>Ryhm�</dt>";
  if ( !in_array($kohde['ryhmaid'], sessionvar_get('write')) ) {
    // Kohteen tiedot ovat muokattavissa vain sen vuoksi, ett� ne ovat
    // omat.  Kohteen ryhm�� ei saa vaihtaa.
    echo "\n<dd>".htmlspecialchars($kohde['ryhma_nimi'])."</dd>";
  } else {
    // Kohteen ryhm� on vaihdettavissa.
    echo "\n<dd>";
    print_as_dropdown($ryhmat, 'rid', 'ryhmaid', 'nimi', $kohde['ryhmaid']);
    echo "</dd>";
  }
  $k_to_p = array('nimi'=>1,'nimi2'=>2); // m�pp�ys kentist� p�iv�ysm��reisiin
  //  $p_to_k = array_flip($k_to_p); // ja toisin p�in
  $scripttpl = "<script type=\"text/javascript\"><!--\nvar cal;\nwindow.onload = function() {\n  cal = new Epoch('epoch_popup','popup',document.getElementById('%s'));\n  cal.selectedDates.push(new Date(%d,%d,%d));\n  cal.goToMonth(%d,%d);\n  cal.reDraw();\n}\n// --></script>";
  foreach (array('nimi','nimi2') as $k) {
    echo "\n<dt>".$nimet[$k]."</dt>";
    echo "\n".'<dd><input type="text" size="30" value="'.
      htmlspecialchars($kohde[$k]) . '" name="'.$k.'" id="text-'.$k.'">';
    if ( is_array($settings['paivayskentat']) &&
	 array_key_exists($kohde['ryhmaid'], $settings['paivayskentat']) &&
	 abs($settings['paivayskentat'][$kohde['ryhmaid']]) == $k_to_p[$k] ) {
      list($y,$m,$d) = split_ymd($kohde[$k]);
      echo "\n".'<img src="lullacons/calendar.png" alt="kalenteri" title="Valitse p�iv�m��r� kalenterilehdelt�" onclick="cal.toggle();" />'."\n";
      printf($scripttpl, "text-{$k}", $y, $m-1, $d, $y, $m-1);
    }
    echo '</dd>';
  }
  /*
  if ( array_key_exists($kohde['ryhmaid'], $settings['paivayskentat']) ) {
    // Ryhm�ll� on p�iv�yskentt�m��ritys
    $pvm_setting = $settings['paivayskentat'][$kohde['ryhmaid']];
    switch (abs($pvm_setting)) {
    case 2:
      $prefix = '2';
    case 1:
      $field = "nimi{$prefix}";
      break;
    default:
    }
  } */
  echo tag('dt',
	   'Sis�lt�muoto kent�lle "'.htmlspecialchars($nimet['data']).'"');
  if ( $kohde['data'] == '' ) {
    // jos sis�lt� on tyhj�, annetaan vapaasti valita muoto
    echo tag('dd', html_make_dropdown(array_flip($settings['mimetypes']), 
				      'mime', $kohde['mime']));
  } else {
    // jos sis�lt�� on, voi muodon muuttaa vain mahdollisella
    // konversiolla, ks. Muunnokset (*) yll�
    echo tag('dd', $settings['mimetypes'][$kohde['mime']]);
    // s�ilytet��n mime ?>
<input type="hidden" name="mime" value="<?php echo $kohde['mime']; ?>">
<?php
  }
  echo '<dt>'.htmlspecialchars($nimet['data']);
  echo '</dt>';
  echo "\n".'<dd>';
  echo get_editor($kohde['mime'], 'kohde', 'data', $kohde['data']);
  echo '</dd></dl><br><input type="submit" value="Tallenna"></form>';

} else {
  // Ei muokkausoikeuksia
  print_errmsg('Sinulla ei ole oikeutta muokata kohteen tietoja!');
}
  require_once('mappi_html-body-end.php');
?>
