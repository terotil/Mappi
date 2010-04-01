<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  $jaksoid = safe_retrieve('jaksoid');

  // Parametreja puuttuu
  if ( $jaksoid == '' ) {
    forward('kohteet.php', 
	    'Ei voi poistaa jaksoa.  Tarvittava parametri'.
	    '<code>jaksoid</code> puuttuu.');
  }

  // Poistetaan jakso
  $jakso = qry_jakso($jaksoid);
  if ( is_allowed($jaksoid, 'write', 'jaksoid', $jakso) ) {
    $param = '&alku='.date('Y-m-d', $jakso['alku']).
      '&alku_t='.date('H', $jakso['alku']).
      '&alku_m='.date('i', $jakso['alku']);
    $param .= '&loppu='.date('Y-m-d', $jakso['loppu']).
      '&loppu_t='.date('H', $jakso['loppu']).
      '&loppu_m='.date('i', $jakso['loppu']);
    qry_jakso_delete($jaksoid);
  } else {
    $msg = 'Oikeutesi eivät riitä jakson poistamiseen.';
  }

  // Heitetään takaisin muokkaussivulle
  forward("kohde_liite_edit.php?kohde_liiteid=".$jakso['kohde_liiteid'].$param,
	  $msg);
?>
