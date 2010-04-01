<?php
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');

  // Haetaan liitetieto
  $liiteid = safe_retrieve('liiteid');
  $liite   = qry_liite($liiteid);

  $pagename = '<a href="asetukset.php">Asetukset</a>'.
    ' &gt; <a href="liitteet.php">Liitteet</a> &gt; '.
    htmlspecialchars($liite['nimi']);

  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

if ( is_allowed($liiteid, 'write', 'liiteid', $liite) ) {
  // Oikeudet kunnossa

    ////////////////////
    // Käännöksen lisäys ?>
<div id="tools"><?php

  $ins = qry_trans_insertable('liite', $liiteid);
  if ( count($ins) > 0 ) {
    ob_start();
?><input type="hidden" name="action" value="insert">
<input type="hidden" name="table" value="liite">
<input type="hidden" name="id" value="<?= $liiteid ?>">
<?php
     require_once('MAPPI/html.php');
     echo html_make_dropdown($ins, 'kieliid'); ?>
<input type="submit" value="Lisää käännös">
   </form><?php {}
     $tmp = ob_get_contents();
     ob_end_clean();
     echo html_toolform('Uusi käännös', 'post', 'trans.php', $tmp);
   }
    /////////////////////
    // Liitetiedon poisto
    ob_start();
?>
   <form method="post" action="liite_delete.php">
    <input type="hidden" name="liiteid" value="<?= $liiteid ?>">
    <input type="submit" value="Poista liitetietotyyppi &quot;<?php echo htmlspecialchars($liite['nimi']); ?>&quot;">
   </form>
<?php
      $tmp = ob_get_contents();
  ob_end_clean();
  echo html_toolform('Liitetietotyypin poisto', 'post', 'liite_delete.php', $tmp);

    ////////////////////
    // Käännöksen poisto
  if ( kaannos_olemassa('liite', $liiteid) ) {
    ob_start();
?><input type="hidden" name="action" value="delete">
<input type="hidden" name="table" value="liite">
<input type="hidden" name="id" value="<?= $liiteid ?>">
<input type="hidden" name="kieliid" value="<?php echo hae_istunnon_kieliid(); ?>">
<input type="submit" value="Poista käännös &quot;<?php $lang = sessionvar_get('lang'); echo $lang['nimi']; ?>&quot;">
<?php {}
    $tmp = ob_get_contents();
    ob_end_clean();
    echo html_toolform('Käännöksen poisto', 'post', 'trans.php', $tmp);
  }

  echo "\n</div>\n\n";

  // Muokkausformi
  echo '<form method="post" action="liite_update.php">';
  echo '<input type="hidden" name="liiteid" value="'.$liite['liiteid'].'">';

  echo "\n<dl><dt>Ryhmä</dt>\n<dd>";
  // Haetaan kaikki kohderyhmät, joita on oikeus muokata
  $ryhmat_write = qry_ryhma_lista('', DB_HUGEINT, 0, 'write');
  print_as_dropdown($ryhmat_write, 'ryhmaid', 'ryhmaid', 'nimi', 
		    $liite['ryhmaid']);

  echo "\n</dd>\n<dt>Nimi</dt>\n<dd>";
  echo '<input type="text" name="nimi" size="30" value="'.
    htmlspecialchars($liite['nimi']).'">';
  echo "\n</dd></dl>\n";
  echo '<input type="submit" value="Tallenna">';
  echo "\n</form>";
} else {
  // Ei muokkausoikeuksia
  print_errmsg('Oikeutesi eivät riitä tämän liitiedon muokkaamisee!');
}

  require_once('mappi_html-body-end.php');
?>
