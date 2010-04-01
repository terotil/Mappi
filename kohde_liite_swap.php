<?php
  $forwardpage = true;
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');

// Haetaan parametrit
$id1 = safe_retrieve_gp('a', 0, true);
$id2 = safe_retrieve_gp('b', 0, true);

// Tarkistetaan oikeellisuus
if ( $id1 < 1 or $id2 < 1 ) {
  // T�h�n tilanteeseen joudutaan vain parametrien puuttuessa tai
  // ollessa virheellisi�
  forward('kohteet.php', 'Virheelliset parametrit vaihdettaessa liitetietojen j�rjestyst�.');
}

// Vaihdetaan j�rjestyst�
$kohdeid = swap_kohde_liite($id1, $id2);

// Tarkistetaan onnistuminen
if ( $kohdeid == false ) {
  // Liitetietoja ei l�ytynyt tai jotain muuta meni vikaan
  forward('kohteet.php', 'Liitetietojen j�rjestyksen vaihto ep�onnistui.');
}

// Palataan kohteen tietoihin
forward("kohde.php?kohdeid=$kohdeid");
?>
