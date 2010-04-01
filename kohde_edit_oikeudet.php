<?php // ;0;
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
require_once('MAPPI/html.php');

  $kohdeid = safe_retrieve('kohdeid');
  $kohde   = qry_kohde($kohdeid);

  $pagename = 
  html_make_a('kohteet.php?rid='.$kohde['ryhmaid'], 'Tietokanta').' &gt; ' .
  html_make_a("kohde.php?kohdeid=$kohdeid",
              kohde_kokonimi($kohde['nimi'], $kohde['nimi2'],'', '', 
			     $kohde['ryhmaid'])) . 
  ' &gt; Muokkaa';

  require_once('mappi_html-body-begin.php');

// Oikeustarkistus
if ( is_allowed($kohdeid, 'write', 'kohdeid', $kohde) ) {
  // Muokkausoikeudet kunnossa

  // Haetaan kohderyhm‰t: kirjoitettavat, luettavat ja kaikki
  $wryhmat = qry_ryhma_lista('', DB_HUGEINT, 0, 'write');
  $rryhmat = qry_ryhma_lista('', DB_HUGEINT, 0, 'read');
  $aryhmat = qry_ryhma_lista_all();

  // Haetaan sarakenimet
  $nimet = qry_kohde_colnames($kohde['ryhmaid']);

  // Tulostetaan lomake
  echo "\n".'<form method="post" action="kohde_update_oikeudet.php">';
  echo "\n<input type=\"hidden\" name=\"kohdeid\" value=\"$kohdeid\">";
  echo "\n<dl>";
  echo "\n<dt>Ryhm‰</dt>";
  if ( !in_array($kohde['ryhmaid'], sessionvar_get('write')) ) {
    // Kohteen tiedot ovat muokattavissa vain sen vuoksi, ett‰ ne ovat
    // omat.  Kohteen ryhm‰‰ ei saa vaihtaa.
    echo "\n<dd>".htmlspecialchars($kohde['ryhma_nimi'])."</dd>";
  } else {
    // Kohteen ryhm‰ on vaihdettavissa.
    echo "\n<dd>";
    echo html_make_dropdown_tab($wryhmat, 'nimi', 'ryhmaid', 'rid', $kohde['ryhmaid']);
    echo "</dd>";
  }
  foreach (array('nimi','nimi2') as $k) {
    echo "\n<dt>".$nimet[$k]."</dt>";
    echo "\n".'<dd><input type="text" size="30" value="'.
      htmlspecialchars($kohde[$k]) . '" name="'.$k.'"></dd>';
  }
  echo '<dt>'.htmlspecialchars($nimet['data']).'</dt>';

  // Parsitaan datakent‰st‰ esiin nykyiset oikeusasetukset
  list($rights_read, $rights_write, $ryhmaoik) = parse_rights($kohde['data']);

  // Lis‰t‰‰n kaikkien ryhmien listaan viel‰ 'kaikki' -ryhm‰.
  array_unshift($aryhmat, array('ryhmaid'=>-1, 'nimi'=>'Kaikki'));

  echo "\n<dd>\n<table border=\"0\">\n<tr>\n<td valign=\"top\">\nLuku<br>";

  // Lukuoikeuksien valintalista
  echo html_make_dropdown_tab($aryhmat, 'nimi', 'ryhmaid', 'rights_read[]', 
			      $rights_read, array('multiple'=>null), false);
  echo "\n</td>\n<td valign=\"top\">\nKirjoitus<br>";

  // Kirjoitusoikeuksien valintalista
  echo html_make_dropdown_tab($aryhmat, 'nimi', 'ryhmaid', 'rights_write[]',
			      $rights_write, array('multiple'=>null), false);
  echo "\n</td>\n<td valign=\"top\">\nRyhm‰taulu<br>";

  // Ryhm‰taulun oikeuksien dropdown
  echo html_make_dropdown_tab(array(array('val'=>'read','name'=>'Luku'),
				   array('val'=>'write','name'=>'Kirjoitus')), 
			      'name', 'val', 'ryhmat', $ryhmaoik);
  echo "\n</td>\n</tr>\n</table></dd>\n";
  echo '</dl><br><input type="submit" value="Tallenna"></form>';
} else {
  // Ei muokkausoikeuksia
  print_errmsg('Sinulla ei ole oikeutta muokata kohteen tietoja!');
}
  require_once('mappi_html-body-end.php');
?>
