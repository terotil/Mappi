<?php //;Tulostus;Pikahaku
  $pagename = 'Pikahaku';
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');
  require_once('MAPPI/html.php');

// Suorita haku, jos hakumerkkijono on annettu
if ( $haku = safe_retrieve_gp('haku', false) ) {
  $match = qry("SELECT kohdeid, ryhmaid, nimi, nimi2 ".
	       "FROM kohde ".
	       "WHERE CONCAT(nimi, ' ', nimi2) like '%$haku%'");
}

// Jos löytyi täsmälleen yksi vastaus, ponkaise suoraan kohteen
// tietoihin.
if ( count($match) == 1 ) {
  forward("kohde.php?kohdeid=".$match[0]['kohdeid']);
}

  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

// Hakulomake
?><form method="get" action="raportti_pikahaku.php">
<input type="text" name="haku" value="<?php echo htmlspecialchars($haku); ?>">
<input type="submit" value="Hae">
</form>
<?php

// Tulosta haun tulos
if ( $haku === false ) {
  // Ei hakua
  echo "<p>Kirjoita hakusana tekstikenttään ja paina 'Hae'.</p>\n";
} elseif ( count($match) > 1 ) {
  // Löytyi
  echo "<p>\n";
  foreach ( $match as $k ) {
    echo html_make_a("kohde.php?kohdeid=".$k['kohdeid'],
		     kohde_kokonimi($k['nimi'], $k['nimi2'], '', '', 
				    $k['ryhmaid'])) . "<br>\n";
  }
  echo "</p>\n";
} else {
  // Ei löytynyt
  echo "<p>Tietokannasta ei löytynyt hakua '".htmlspecialchars($haku).
    "' vastaavia kohteita.</p>\n";
}

  require_once('mappi_html-body-end.php');
?>
