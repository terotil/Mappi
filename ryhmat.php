<?php // ;Tietokanta-asetukset;Muokkaa ryhmittelyotsikoita;
  require_once('mappi_headers.php');

  $pagename = '<a href="asetukset.php">Asetukset</a>'.
    ' &gt; Ryhm�t';

  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

  // Tarkistetaan oikeudet n�hd� ryhmittelytiedot
  if ( is_allowed_ryhma('read') ) { // Lukuoikeudet on
    
    // Haetaan liitelista
    $haku     = safe_retrieve('haku',  '');
    $ryhmat   = qry_ryhma_lista($haku, DB_HUGEINT, 0);
    
    // Yl�reunan kontrollien ryhmittelytaulukon alku
    echo '<div id="tools">';
    
    // Tulostetaan uuden ryhm�n lis�ysformi
    echo html_toolform('Uusi ryhm�', 'post', 'ryhma_insert.php',
		       '<input type="text" name="nimi"><br>'.
		       '<input type="submit" value="Lis�� ryhm�">');

    /*    
    // Tulostetaan hakuformi
    echo '<form method="post" action="ryhmat.php">';
    echo '<input type="text" name="haku" size="8" value="'.$haku.'"><br>';
    echo '<input type="submit" value="Hae">';
    echo '</form>';
    */
    // Yl�reunan kontrollitaulun loppu
    echo '</div>';
    
    $hide = array('ryhmaid', 'tr_kieliid');
    $code = '';
    if ( is_allowed_ryhma('write') ) {
      $code = array('nimi' => '"<td><a href=\"ryhma_edit.php?ryhmaid=".
                            $row[\'ryhmaid\']."\">".$row[$key]."</a></td>";');
    }
    print_as_table($ryhmat, 'class="qtable"', '', $hide, $code);

  } else { // Lukuoikeuksia ei ole

    // K�ytt�j�ll� ei ole oikeuksia n�hd� ryhmittelytietoja
    print_errmsg('Sinulla ei ole oikeutta n�hd� ryhmittelytietoja!');

  }
  require_once('mappi_html-body-end.php');
?>
