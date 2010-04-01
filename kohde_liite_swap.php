<?php
  $forwardpage = true;
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');

// Haetaan parametrit
$id1 = safe_retrieve_gp('a', 0, true);
$id2 = safe_retrieve_gp('b', 0, true);

// Tarkistetaan oikeellisuus
if ( $id1 < 1 or $id2 < 1 ) {
  // Tähän tilanteeseen joudutaan vain parametrien puuttuessa tai
  // ollessa virheellisiä
  forward('kohteet.php', 'Virheelliset parametrit vaihdettaessa liitetietojen järjestystä.');
}

// Vaihdetaan järjestystä
$kohdeid = swap_kohde_liite($id1, $id2);

// Tarkistetaan onnistuminen
if ( $kohdeid == false ) {
  // Liitetietoja ei löytynyt tai jotain muuta meni vikaan
  forward('kohteet.php', 'Liitetietojen järjestyksen vaihto epäonnistui.');
}

// Palataan kohteen tietoihin
forward("kohde.php?kohdeid=$kohdeid");
?>
