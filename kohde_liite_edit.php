<?php
  require_once('mappi_headers.php');

  $kohde_liiteid = safe_retrieve('kohde_liiteid');
// onko $kohde_liiteid ok?
if ( intval($kohde_liiteid) < 1 && $kohde_liiteid != '0' ) {
  include('mappi_html-body-begin.php');
  print_errmsg('Tarvittava parametri "kohde_liiteid" puuttuu.');
  include('mappi_html-body-end.php');
  exit;
}
  $kohde_liite = qry_kohde_liite($kohde_liiteid);

  // Haetaan erikoistapaukset käsittelevät muokkaussivut käymällä läpi
  // kaikki kohde_liite_edit_*.php -tiedostot ja tekemällä niiden
  // otsikkotietojen avulla lisäyssivuista ryhmaid:n mukaan indeksoitu
  // lista
  $edit_pages = firstlines('^kohde_liite_edit_.*\.php$');
  $edit_pages = array_fold($edit_pages, array(1));

  if ( array_haskey($edit_pages, $kohde_liite['liiteid']) ) {
    // FIXME: Mitä tehdä silloin, kun samaan ryhmään on ilmoittautunut
    // monta muokkaussivua?  Tällä hetkellä vain otetaan tylysti
    // ensimmäinen.
    forward($edit_pages[$kohde_liite['liiteid']][0][0]."?".
	    $_SERVER['QUERY_STRING']);
    return;
  }

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

// Sivun alku heitetään sisään vasta kun $pagename on tiedossa
require_once('mappi_html-head.php');
require_once('mappi_html-body-begin.php');

  // Onko oikeus muokata
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid', $kohde_liite) ) {
    // Muokkausoikeudet kunnossa

    ////////////////////
    // Käännöksen lisäys ?>
<div id="tools"><?php

  $ins = qry_trans_insertable('kohde_liite', $kohde_liiteid);
  if ( count($ins) > 0 ) {
    ob_start();
?><input type="hidden" name="action" value="insert">
<input type="hidden" name="table" value="kohde_liite">
<input type="hidden" name="id" value="<?= $kohde_liiteid ?>">
<?php
     require_once('MAPPI/html.php');
     echo html_make_dropdown($ins, 'kieliid'); ?>
<input type="submit" value="Lisää"><?php 
   $tmp = ob_get_contents();
     ob_end_clean();
     echo html_toolform('Uusi käännös', 'post', 'trans.php', $tmp);
   } 
?>
<?php
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
  echo html_toolform('Liitetiedon poisto', 'post', 'kohde_liite_delete.php', 
		     $tmp);

    ////////////////////
    // Käännöksen poisto
  if ( kaannos_olemassa('kohde_liite', $kohde_liiteid) ) {
    ob_start();
?>    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="table" value="kohde_liite">
    <input type="hidden" name="id" value="<?= $kohde_liiteid ?>">
    <input type="hidden" name="kieliid" value="<?php echo hae_istunnon_kieliid(); ?>">
    <input type="submit" value="Poista käännös &quot;<?php $lang = sessionvar_get('lang'); echo $lang['nimi']; ?>&quot;">
	 <?php {} 
    $tmp = ob_get_contents();
    ob_end_clean();
    echo html_toolform('Käännöksen poisto', 'post', 'trans.php', $tmp);
  }

  ////////////////////
  // Muunnokset (*)
  $conversion_list = get_conversion_link_list('kohde_liite', $kohde_liiteid, 
					      $kohde_liite['mime']);
  if ( count($conversion_list)>0 ) {
    echo html_controlgroup('Sisältömuoto', 
			   "Muunna tekstisisältö muotoon: ".
			   join(", \n", $conversion_list));
  }

?>
</div>

<h2><?php 
// Haetaan lista kielistä, joille on olemassa käännös                       
$kaannokset = qry_trans_avail('kohde_liite', $kohde_liiteid);
// Liitetiedon tyyppi
echo $kohde_liite['liite_nimi'];
// Tehdään kielenvaihtolinkit
echo trans_links($kaannokset, '<a href="kohde_liite_edit.php?'.
		 'kohde_liiteid='.$kohde_liiteid.'&lang=%d">%s</a>')
?></h2>
<dl>
 <dt>Vapaa teksti</dt>
 <dd>
  <form method="post" action="kohde_liite_update.php">
  <input type="hidden" name="kohde_liiteid" value="<?= $kohde_liiteid ?>">
  Sisältömuoto <?php
  if ( $kohde_liite['data'] == '' ) {
    // jos sisältö on tyhjä, annetaan vapaasti valita muoto
    echo html_make_dropdown(array_flip($settings['mimetypes']),'mime',
			    $kohde_liite['mime']);
  } else {
    // jos sisältöä on, voi muodon muuttaa vain mahdollisella
    // konversiolla, ks. Muunnokset (*) yllä
    echo $settings['mimetypes'][$kohde_liite['mime']]; 
    // säilytetään mime ?>
<input type="hidden" name="mime" value="<?php echo $kohde_liite['mime']; ?>">
<?php
  }
  echo get_editor($kohde_liite['mime'], 'kohde_liite', 'data', 
		  $kohde_liite['data']); ?><br />
  <input type="submit" value="Tallenna teksti">
  </form>
 </dd>
<?php
    // Viitaukset
    $viittaukset = qry_viittaus_lista($kohde_liiteid);
    $count_viittaukset = count($viittaukset);
    echo html_make_tag('dt', sprintf('Viittaukset (%d kpl)', 
				     $count_viittaukset)), "\n";
    // viittauksen lisäsylomake
    echo '<dd><form method="post" action="viittaus_insert.php">'."\n";
    echo '<input type="hidden" name="kohde_liiteid" value="'.
      $kohde_liiteid.'">'."\n";
    echo '<input type="text" size="30" name="kohde_nimi">'."\n";
    echo '<input type="submit" value="Lisää viittaus"></form>'."\n";
      
    $uarr = '&#8593;';
    $darr = '&#8595;';

    if ( $count_viittaukset > 0 ) {
      // Tulostetaan viittaukset
      $viittaukset_html = array();
      for ( $i=0; $i<$count_viittaukset; $i++ ) {
	//      foreach ( $viittaukset as $viittaus) {
	if ( $i > 0 ) {
	  // Voidaan siirtää "ylös", eli vaihtaa paikkaa listalla
	  // ylemmän kanssa.  Sama asia, kuin ylemmän siirtäminen alas,
	  // eli edellisen liitetiedon "alas".
	  $ylos = str_replace($darr, $uarr, $alas);
	} else {
	  $ylos = html_make_tag('span', $uarr, array('style'=>'color:gray', 
						     'class'=>'nappi'));
	}
	if ( $i < $count_viittaukset - 1 ) {
	  // Voidaan siirtää "alas", eli vaihtaa paikkaa alemman kanssa.
	  $alas = sprintf('<a class="nappi" href="viittaus_swap.php'.
			  '?kl=%d&amp;ak=%d&amp;bk=%d">%s</a>',
			  $kohde_liiteid, $viittaukset[$i]['kohdeid'],
			  $viittaukset[$i+1]['kohdeid'], $darr);
	} else {
	  $alas = html_make_tag('span', $darr, array('style'=>'color:gray', 
						     'class'=>'nappi'));
	}

	// Onko viitatun kohteen tietoja oikeus nähdä?
	if ( is_allowed($viittaukset[$i]['kohde_ryhmaid'], 'read', 'ryhmaid') or
	     $viittaukset[$i]['kohdeid'] == sessionvar_get('uid') ) {
	  // Oikeudet kunnossa, annetaan linkki
	  $viittaukset_html[] = "$ylos&nbsp;$alas&nbsp;".
	    $viittaukset[$i]['ryhma_nimi'].'&nbsp;/&nbsp;'.
	    '<a href="kohde.php?kohdeid='.$viittaukset[$i]['kohdeid'].'">'.
	    kohde_kokonimi($viittaukset[$i]['kn'], $viittaukset[$i]['kn2'],
			   $viittaukset[$i]['rkn'], $viittaukset[$i]['rkn2'],
			   $viittaukset[$i]['kohde_ryhmaid']).
	    '</a>&nbsp;<a class="nappi" href="viittaus_delete.php'.
	    '?kohdeid='.$viittaukset[$i]['kohdeid'].'&kohde_liiteid='.
	    "$kohde_liiteid\">poista&nbsp;viittaus</a>";
	} else {
	  // Ei oikeuksia.  Näytetään pelkästään nimi listassa.
	  $viittaukset_html[] = "$ylos&nbsp;$alas&nbsp;".
	    $viittaukset[$i]['ryhma_nimi'].' / '.$viittaukset[$i]['kohde_nimi'];
	}
      }
      echo join($viittaukset_html, "<br>\n")."</dd>";
    }

    // Jaksot 
    // Tulostetaan ensiksi jakson lisäyslomake  
    $goto_v = date('Y');
    $goto_k = date('n');
?>
<dt>Jaksot</dt><dd>
<form method="post" action="jakso_insert.php">
<input type="hidden" name="kohde_liiteid" value="<?php echo $kohde_liiteid;?>">
pvm <input type="text" size="10" name="alku" id="alku" value="<?php echo $_REQUEST['alku']; ?>"> 
<img src="lullacons/calendar.png" alt="kalenteri" title="Valitse päivämäärä kalenterilehdeltä" onclick="cala.toggle();" />
klo <input type="text" size="2" name="alku_t" value="<?php echo $_REQUEST['alku_t']; ?>"
  >:<input type="text" size="2" name="alku_m" value="<?php echo $_REQUEST['alku_m']; ?>"> (alkuaika)<br>
pvm <input type="text" size="10" name="loppu" id="loppu" value="<?php echo $_REQUEST['loppu']; ?>">
<img src="lullacons/calendar.png" alt="kalenteri" title="Valitse päivämäärä kalenterilehdeltä" onclick="call.toggle();" />
klo <input type="text" size="2" name="loppu_t" value="<?php echo $_REQUEST['loppu_t']; ?>"
  >:<input type="text" size="2" name="loppu_m" value="<?php echo $_REQUEST['loppu_m']; ?>"> (loppuaika)<br>
<script type="text/javascript"><!--
var cala;
var call;
window.onload = function() {
  cala = new Epoch('epoch_popup','popup',document.getElementById('alku'));
  // Date counts months from 0 to 11!
<?php
  if ( $_REQUEST['alku'] ) {
    list($v,$k,$p) = split_ymd($_REQUEST['alku']);
    $goto_v = $v;
    $goto_k = $k;
    echo "  cala.selectedDates.push(new Date($v, $k-1, $p));\n";
  }
?>
  cala.goToMonth(<?php echo $goto_v; ?>, <?php echo $goto_k-1; ?>);
  cala.reDraw();

  call = new Epoch('epoch_popup','popup',document.getElementById('loppu'));
  // Date counts months from 0 to 11!
<?php
  if ( $_REQUEST['loppu'] ) {
    list($v,$k,$p) = split_ymd($_REQUEST['loppu']);
    $goto_v = $v;
    $goto_k = $k;
    echo "  call.selectedDates.push(new Date($v, $k-1, $p));\n";
  }
?>
  call.goToMonth(<?php echo $goto_v; ?>, <?php echo $goto_k-1; ?>);
  call.reDraw();
}
// --></script>
<select name="repeat">
<option value="no" selected>Ei toistu</option>
<option value="daily">Toistuu päivittäin</option>
<option value="weekly">Toistuu viikoittain</option>
</select>
<input type="text" size="2" name="times"> kertaa<br>
<select name="type">
<option value="in" selected>Mukaanlukeva</option>
<option value="ex">Poislukeva</option>
</select>
<input type="submit" value="Lisää jakso">

<?php  
    // Onko jo jaksoja?
   $jaksot = qry_jakso_lista($kohde_liiteid);
    if ( count($jaksot) > 0 ) {
      $jaksot_html = array();
      $date_format = 'd.m.Y k\lo H:i';
      foreach ( $jaksot as $jakso ) {
	$jaksot_html[] = 
	  date($date_format, $jakso['alku']).' &mdash; '.
	  date($date_format, $jakso['loppu']).' '.
	  $settings['jaksotyyppi'][$jakso['type']].
	  ' <a class="nappi" href="jakso_delete.php?jaksoid='.
	  $jakso['jaksoid'].'">poista jakso</a>';
      }
      echo '<div style="margin-top: 1ex">', 
	join($jaksot_html, "<br>\n"), '</div>';
    }
?></form></dd>

<dt>Tiedostot</dt>
<dd>
<form method="post" action="tiedosto_insert.php" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="6291456"><!-- 6 MB -->
<input type="hidden" name="kohde_liiteid" value="<?php echo $kohde_liiteid ?>">
Tiedosto <input name="tiedosto" type="file">
<input type="submit" value="Lisää">
</form>
<?php
   // HUOM!
   // Kieliversioitujen tiedostojen hallintaa ei ole toteutettu
   // käyttöliittymään.

    // Onko tiedostoja?
    $tiedostot = qry_tiedosto_lista($kohde_liiteid);
    if ( count($tiedostot) > 0 ) {
      include('libseitti-php/hrsize.function.php');
      $tiedostot_html = array();
      foreach ( $tiedostot as $tiedosto ) {
	$tiedostot_html[] = 
	  '<a href="lataa.php?id='.$tiedosto['tiedostoid'].'">'.
	  $tiedosto['nimi'].'</a> '.
	  " (".hrsize($tiedosto['koko']).", {$tiedosto['mime']}) ".
	  //	  '<a href="tiedosto_edit.php?id='.$tiedosto['tiedostoid'].
	  //	  '">[muuta tietoja]</a> '.
	  '<a class="nappi" href="tiedosto_delete.php?tiedostoid='.
	  $tiedosto['tiedostoid'].
	  "&kohde_liiteid=$kohde_liiteid\">poista tiedosto</a>";
      }
	echo join($tiedostot_html, "<br>\n"), "</dd>";
    }
    echo "\n</dl>";

  } else {
    print_errmsg('Sinulla ei ole oikeutta muokata liitteen tietoja!');
  }

?>

<?php
  require_once('mappi_html-body-end.php');
?>
