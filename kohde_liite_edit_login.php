<?php // ;0;
  require_once('mappi_headers.php');

  $kohde_liiteid = safe_retrieve('kohde_liiteid');
// onko $kohde_liiteid ok?
if ( intval($kohde_liiteid) < 1 && $kohde_liiteid != '0' ) {
  include('mappi_html-head.php');
  include('mappi_html-body-begin.php');
  print_errmsg('Tarvittava parametri "kohde_liiteid" puuttuu.');
  include('mappi_html-body-end.php');
  exit;
}
  $kohde_liite = qry_kohde_liite($kohde_liiteid);

  $viittaukset = qry_viittaus_lista($kohde_liiteid);
//  $jaksot = qry_jakso_lista($kohde_liiteid);

$kohde = qry_kohde($kohde_liite['kohdeid']);
$ryhma = qry_ryhma($kohde['ryhmaid']);
$kohde['kohde_kokonimi'] = 
  kohde_kokonimi($kohde['nimi'],$kohde['nimi2'],
		 $ryhma['kohde_nimi'],$ryhma['kohde_nimi2'],
		 $kohde['ryhmaid']);

require_once('MAPPI/html.php');

$pagename = html_make_a('kohteet.php', 'Tietokanta').' &gt; '.
html_make_a("kohde.php?kohdeid=".$kohde_liite['kohdeid'], 
	    $kohde['kohde_kokonimi']).' &gt; '.
$kohde_liite['liite_nimi'];

  // Sivun alku heitet‰‰n sis‰‰n vasta kun $pagename on tiedossa
  include('mappi_html-head.php');
  include('mappi_html-body-begin.php');

  // Onko oikeus muokata
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid', $kohde_liite) == 1 ) {
    // On ihan oikeasti oikeudet, eik‰ vain siksi, ett‰ tiedot ovat omat

    /////////////////////
    // Liitetiedon poisto
    ob_start();
?>
   <form method="post" action="kohde_liite_delete.php">
    <input type="hidden" name="kohde_liiteid" value="<?= $kohde_liiteid ?>">
    Liitetieto: &quot;<?= $kohde_liite['liite_nimi'] ?>&quot; 
    <input type="submit" value="Poista">
   </form>
<?php
      $tmp = ob_get_contents();
  ob_end_clean();
  echo '<div id="tools">';
  echo html_toolform('Liitetiedon poisto', 'post', 'kohde_liite_delete.php', 
		     $tmp);
  echo '</div>';

    $old_pw = html_extract_tag_contents($kohde_liite['data'], 'pw');
    $old_uname = html_extract_tag_contents($kohde_liite['data'], 'uname');

    echo '<form method="post" action="kohde_liite_update_login.php">'."\n";
    echo '<input type="hidden" name="kohde_liiteid" value="'.$kohde_liiteid.'">'."\n";
    echo '<input type="hidden" name="old_pw" value="'.$old_pw.'">'."\n";
    echo '<input type="hidden" name="old_uname" value="'.$old_uname.'">'."\n";

    echo "\n<h2>".$kohde_liite['liite_nimi']."</h2>";
    echo "\n<dl>\n<dt>K‰ytt‰j‰nimi</dt>\n<dd>\n";
    echo '<input type="text" name="new_uname" size="30" value="'.$old_uname."\">\n</dd>\n";
    echo "\n<dt>Salasana</dt>\n<dd>\n";
    echo '<input type="password" name="new_pw" size="30" value="">'."\n</dd></dl>\n";

    echo '<input type="submit" value="Tallenna muutokset">'."\n";
    echo '</form>';

    // Viitaukset oikeusprofiileihin
    echo "\n<dt>Oikeusprofiilit</dt>\n<dd>\n";
    echo '<form method="post" action="viittaus_insert.php">'."\n";
    echo '<input type="hidden" name="rid" value="0">'."\n";
    echo '<input type="hidden" name="kohde_liiteid" value="'.
      $kohde_liiteid.'">'."\n";
    echo '<input type="text" size="30" name="kohde_nimi">'."\n";
    echo '<input type="submit" value="Lis‰‰ oikeusprofiili"></form>'."\n";

    // Onko jo viittauksia?
    $viittaukset = qry_viittaus_lista($kohde_liiteid);
    if ( count($viittaukset) > 0 ) {
      // Tulostetaan viittaukset
      $viittaukset_html = array();
      foreach ( $viittaukset as $viittaus) {
        // Onko viitatun kohteen tietoja oikeus n‰hd‰?
        if ( is_allowed($viittaus['kohde_ryhmaid'], 'read', 'ryhmaid') or
             $viittaus['kohdeid'] == sessionvar_get('uid') ) {
          // Oikeudet kunnossa, annetaan linkki
          $viittaukset_html[] =
            $viittaus['ryhma_nimi'].'&nbsp;/&nbsp;'.
            '<a href="kohde.php?kohdeid='.$viittaus['kohdeid'].'">'.
	    kohde_kokonimi($viittaus['kn'], $viittaus['kn2'],
			   $viittaus['rkn'], $viittaus['rkn2'], 
			   $viittaus['kohde_ryhmaid']).
            '</a>&nbsp;<a class="nappi" href="viittaus_delete.php'.
            '?kohdeid='.$viittaus['kohdeid'].'&kohde_liiteid='.
            "$kohde_liiteid\">poista&nbsp;viittaus</a>";
        } else {
          // Ei oikeuksia.  N‰ytet‰‰n pelk‰st‰‰n nimi listassa.
          $viittaukset_html[] = $viittaus['kohde_nimi'];
        }
      }
      echo join($viittaukset_html, "<br>\n")."</dd>";
    }


  } else {
    print_errmsg('Sinulla ei ole oikeutta muokata liitteen tietoja!');
  }

  require_once('mappi_html-body-end.php');

?>
