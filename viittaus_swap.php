<?php
  $forwardpage = true;
  require_once('mappi_rc.php');
  require_once('mappi_headers.php');

// Haetaan parametrit
$klid = safe_retrieve_gp('kl', 0, true);
$kid1 = safe_retrieve_gp('ak', 0, true);
$kid2 = safe_retrieve_gp('bk', 0, true);

// Tarkistetaan oikeellisuus
if ( $kid1 < 1 or $kid2 < 1 ) {
  // T�h�n tilanteeseen joudutaan vain parametrien puuttuessa tai
  // ollessa virheellisi�
  forward("kohde_liite_edit.php?kohde_liiteid=$klid", 'Virheelliset parametrit vaihdettaessa viittausten j�rjestyst�.');
}

// Vaihdetaan j�rjestyst�
$success = swap_viittaus($klid, $kid1, $klid, $kid2);

// Tarkistetaan onnistuminen
if ( $success ) {
  // Liitetietoja ei l�ytynyt tai jotain muuta meni vikaan
  forward('kohteet.php', 'Viittausten j�rjestyksen vaihto ep�onnistui.');
}

// Palataan liitteen tietoihin
forward("kohde_liite_edit.php?kohde_liiteid=$klid");
?>
