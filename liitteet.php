<?php // ;Tietokanta-asetukset;Muokkaa liitetietotyyppej‰;
  $pagename = '<a href="asetukset.php">Asetukset</a> &gt; Liitteet';
  require_once('mappi_headers.php');
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

  // Haetaan liitelista
  $haku     = safe_retrieve('haku',  '');
  $liitteet = qry_liite_lista($haku);

  // Yl‰reunan kontrollien ryhmittelytaulukon alku
  echo '<div id="tools">';

  // Tulostetaan uuden liitetiedon lis‰ysformi
ob_start();
  // Liitetiedon nimi
  echo 'nimi<br><input type="text" name="nimi" size="20"><br>ryhm‰‰n<br>';
  // Ryhm‰n valinta.  Haetaan kaikki kohderyhm‰t, joita on oikeus
  // muokata
  $ryhmat_write = qry_ryhma_lista('', DB_HUGEINT, 0, 'write');
  print_as_dropdown($ryhmat_write, 'ryhmaid', 'ryhmaid', 'nimi');
  echo      '  <br><input type="submit" value="Lis‰‰">'."\n";
$tmp = ob_get_contents();
ob_end_clean();
echo html_toolform('Uusi liitetietotyyppi', 'post', 'liite_insert.php', $tmp);

  // Tulostetaan hakuformi
/*
  echo '<form method="post" action="liitteet.php">';
  echo '<input type="text" name="haku" size="8" value="'.$haku.'">';
  echo '<input type="submit" value="Hae">';
  echo '</form>';
*/
  echo '</div>';

  $nimet = array('ryhma_nimi' => 'Ryhm‰');
  $hide = array('liiteid', 'ryhmaid', 'ryhma_liite_nimi', 'tl_kieliid', 'tr_kieliid');
  $code = array('nimi' => '"<td><a href=\"liite_edit.php?liiteid=".
                          $row[\'liiteid\']."\">".$row[$key]."</a></td>";');
  print_as_table($liitteet, 'class="qtable"', $nimet, $hide, $code);

  require_once('mappi_html-body-end.php');
?>