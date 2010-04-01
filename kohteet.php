<?php
  require_once('mappi_headers.php');

  // Asetetaan muuttujat
  // Hakuehdot
  $limit  = safe_retrieve('limit',  $settings['limit'][0][0]);
  $offset = safe_retrieve('offset', '0');
  $haku   = safe_retrieve('haku',   '');
  $rid    = safe_retrieve('rid',    $settings['default_rid']);
// Varmistus, ett� rid on yksi numero eik� lista
  $tmp = split(',', $rid);
  $rid = $tmp[0];
  // Jatkotiedot
  $fpage  = safe_retrieve('fpage',  'kohde.php');
  $name   = safe_retrieve('name',   '');
  $value  = safe_retrieve('value',  '');

  // Varmistetaan, ett� oletuksena on sallittu ryhm�id
  if ( !is_allowed($rid, 'read', 'ryhmaid') ) {
    $read = sessionvar_get('read');
    $rid = (string)$read[0];
  }

  // Haetaan kaikki kohderyhm�t, jotka on oikeus n�hd�
  $ryhmat = qry_ryhma_lista('', PHP_INT_MAX);
  // Haetaan kohdelista ja sarakenimet
  $kohteet = qry_kohde_lista($rid, $haku, $limit, $offset*$limit);
  // Tarkistetaan onko kohteita viel� viimeisen n�ytett�v�n j�lkeen
  $kohdemaara = $settings['lastrowcount'];
  $sivumaara = (floor($kohdemaara/$limit)+1);
  $sivunro = $offset+1;
  $on_lisaa = ( $kohdemaara > ($offset+1)*$limit );

  // Haetaan sarakenimet
  $nimet = qry_kohde_colnames($rid);

  $ryhmat_fold = array_fold($ryhmat, array('ryhmaid'));
  $pagename = 'Tietokanta'; 
  if ( $kohdemaara > 0 ) {
    $pagename .= ', '.$ryhmat_fold[$rid][0]['nimi'].' '.
      ($offset*$limit+1).'-'.min($kohdemaara, ($offset+1)*$limit).'/'.
      $kohdemaara;
  }
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

  // Yl�reunan kontrollien ryhmittelytaulukon alku
  echo '<div id="tools">';

/*****************************************************************************/
{ // Tulostetaan hakuformi
  $tmp = sprintf('nimi <input type="text" name="haku" size="8" value="%s"><br />'."\n".
		 'ryhm�st� %s<br />n�yt� %s kerrallaan<br />'."\n".
		 '<input type="submit" value="Hae">'."\n", $haku, 
		 html_make_dropdown_tab($ryhmat, 'nimi', 'ryhmaid', 'rid', $rid),
		 html_make_limitdropdown($limit));
  echo html_toolform('Hae kohteita', 'get', 'kohteet.php', $tmp, 
		     array('fpage'=>$fpage, 'name'=>$name, 'value'=>$value, 
			   'pn' => $pn));
}

/*****************************************************************************/
{ // Tulostetaan selauslinkit
  $parr = array('limit'=>$limit, 
		'haku'=>$haku, 
		'rid'=>$rid,
		'fpage'=>$fpage,
		'name'=>$name,
		'value'=>$value,
		'pn'=>$pn);
  $tmp = '';
  echo "\n\n<!-- Selausnapit -->\n";
  if ( $offset > 1 ) {
    // pikapainike listan alkuun hypp��mist� varten
    $parr['offset'] = 0;
    $tmp .= html_make_button('kohteet.php', "&#8593;&#8593; Alkuun", $parr);
  }
  if ( $offset > 0 ) {
    // voi selata taaksep�in
    $parr['offset'] = $offset - 1;
    $tmp .= html_make_button('kohteet.php', "&#8593; Edelliset $limit", $parr);
  }
  if ( $on_lisaa ) {
    // voi selata eteenp�in
    $parr['offset'] = $offset + 1;
    $tmp .= html_make_button('kohteet.php', "&#8595; Seuraavat $limit", $parr);
  }
  if ( $offset < ($sivumaara-2) ) {
    // pikapainike listan loppuun hypp��mist� varten
    $parr['offset'] = $sivumaara-1;
    $tmp .= html_make_button('kohteet.php', "&#8595;&#8595; Loppuun", $parr);
  }
  echo html_controlgroup("Selaus, sivu $sivunro/$sivumaara",
			 $tmp ? $tmp : 'Kaikki kohteet ovat n�kyvill�.');
}

/*****************************************************************************/
{ // Tulostetaan uuden kohteen lis�ysformi
  ob_start();
  // Haetaan kaikki kohderyhm�t, joita on oikeus muokata
  $ryhmat_write = qry_ryhma_lista('', DB_HUGEINT, 0, 'write');
  // Ryhm�valintalista
  echo 'ryhm��n ';
  print_as_dropdown($ryhmat_write, 'ryhmaid', 'ryhmaid', 'nimi');
  // Lis��-nappi
  echo '<br><input type="submit" value="Lis��">';
  $tmp = ob_get_contents();
  ob_end_clean();

  echo html_toolform('Uusi kohde', 'post', 'kohde_insert.php', $tmp);
}

 
  // Yl�reunan kontrollitaulun loppu
  echo '</div>';

require_once('libseitti-php/substr_skiptags.function.php');

  // Tulostetaan kohdetaulukko
  $freeparam = '';
  if ( $name != '' ) $freeparam = "&$name=$value";
  $linkkikoodi = '"<td><a href=\"'.$fpage.'?kohdeid=".$row[\'kohdeid\']."'.$freeparam.'\">".$row[$key]."</a>&nbsp;</td>";';
  $koodi = array('nimi' => $linkkikoodi, 
		 'nimi2' => $linkkikoodi,
		 //'data' => '"<td>".htmlentities(substr(strip_tags($row[$key]),0,30)).(strlen(strip_tags($row[$key]))>30?"&hellip;":"&nbsp;")."</td>";'
		 'data' => '"<td>".substr_skiptags(convert_content($row["mime"], "text/html", $row["data"]), 0, 70, "&hellip;", 1)."</td>";'
		 );
  print_as_table($kohteet, 'class="qtable"', $nimet, array('kohdeid', 'ryhmaid', 'leima', 'leima_unix', 'tk_kieliid'), $koodi);

  require_once('mappi_html-body-end.php');
?>
