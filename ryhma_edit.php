<?php
  require_once('mappi_headers.php');

  // Haetaan ryhm�
  $ryhmaid = safe_retrieve('ryhmaid');
  $ryhma   = qry_ryhma($ryhmaid);

  $pagename = '<a href="asetukset.php">Asetukset</a>'.
    ' &gt; <a href="ryhmat.php">Ryhm�t</a>'.
    ' &gt; '.$ryhma['nimi'];

  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

if ( is_allowed_ryhma('write') ) {
  // Oikeudet kunnossa

  echo '<div id="tools">';
  echo 
    html_toolform('Ryhm�n poisto', 'post', 'ryhma_delete.php', 
		  '<input type="hidden" name="ryhmaid" value="'.$ryhmaid.'">'.
		  '<input type="submit" value="Poista ryhm� &quot;'.
		  $ryhma['nimi'].'&quot;">');
 
  ////////////////////
  // K��nn�ksen lis�ys
  $ins = qry_trans_insertable('ryhma', $ryhmaid);
  if ( count($ins) > 0 ) {
    ob_start();
?><input type="hidden" name="action" value="insert">
<input type="hidden" name="table" value="ryhma">
<input type="hidden" name="id" value="<?= $ryhmaid ?>">
<?php {}
    require_once('MAPPI/html.php');
    echo html_make_dropdown($ins, 'kieliid'); ?>
<br><input type="submit" value="Lis�� k��nn�s">
<?php {}
    $tmp = ob_get_contents();
    ob_end_clean();
    echo html_toolform('Uusi k��nn�s', 'post', 'trans.php', $tmp);
  } 
  ////////////////////
  // K��nn�ksen poisto
  if ( kaannos_olemassa('ryhma', $ryhmaid) ) {
    ob_start();
?><input type="hidden" name="action" value="delete">
<input type="hidden" name="table" value="ryhma">
<input type="hidden" name="id" value="<?= $ryhmaid ?>">
<input type="hidden" name="kieliid" value="<?php echo hae_istunnon_kieliid(); ?>">
<input type="submit" value="Poista k��nn�s &quot;<?php $lang = sessionvar_get('lang'); echo $lang['nimi']; ?>&quot;">
<?php
    $tmp = ob_get_contents();
    ob_end_clean();
    echo html_toolform('K��nn�ksen poisto', 'post', 'trans.php', $tmp);
  }
?>
</div>

<?php
  // Muokkausformi
  echo '<form method="post" action="ryhma_update.php">';
  echo '<input type="hidden" name="ryhmaid" value="'.$ryhmaid.'">';
  echo '<dl>';
  foreach ( array('nimi', 'kohde_nimi', 'kohde_nimi2', 'kohde_data', 
		  'liite_nimi', 'kohde_liite_data') as $key) {
    if ( $key != 'nimi' ) {
      list($taulu, $kentta) = split('_', $key, 2);
      echo "\n<dt>'$taulu' taulun '$kentta' kentt�</dt>\n";
    } else {
      echo "\n<dt>Nimi</dt>\n";
    }
    echo '<dd><input type="text" name="'.$key.'" size="30" value="'.
      htmlspecialchars($ryhma[$key]).'"></dd>';
  }
  echo "\n</dl>\n";
  echo '<input type="submit" value="Tallenna">';
  echo "\n</form>";

} else {
  // Ei muokkausoikeuksia
  print_errmsg('Sinulla ei ole oikeuksia muokata t�m�n ryhm�n tietoja!');
}

  require_once('mappi_html-body-end.php');
?>
