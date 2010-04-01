<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  $kohdeid = safe_retrieve('kohdeid');
  $confirmed     = safe_retrieve_gp('confirmed', 'no', true);

  // Jos kohde on lukittu, palataan suoraan kohden�kym��n ja
  // ilmoitetaan, ett� poisto ei ole sallittu.
  global $settings;
  if ( array_haskey($settings, 'lukittu') and
       in_array($kohdeid, $settings['lukittu']) ) {
    forward("kohde.php?kohdeid=$kohdeid", 
	    'Ei voida poistaa.  Kohde on lukittu.');
  }

if ( $confirmed == 'yes' ) {
  // Poisto on varmistettu, poistetaan

  // Jos kohdeid puuttu, ei voida tehd� oikein yhtik�s mit��n.
  // Pelastaudutaan kohdelistalle oletusarvoilla.
  if ( $kohdeid == '' ) {
    forward("kohteet.php", 
	    'Ei voida poistaa kohdetta.  Tarvittava parametri'.
	    ' <code>kohdeid</code> puuttuu.');
    return;
  }

  $kohde = qry_kohde($kohdeid);

  if ( is_allowed($kohdeid, 'write', 'kohdeid', $kohde) ) {
    // Oikeudet ovat kunnossa, yritet��n poistaa kohde
    if ( qry_kohde_delete($kohdeid) ) {
      // Poisto onnistui, jatketaan kohdelistalle.
      forward("kohteet.php?rid=".$kohde['ryhmaid']);
    } else {
      // Poisto ep�onnistui, palataan kohdesivulle ja ilmoitetaan
      // ep�onnistumisesta.  Todenn�k�isesti kohteeseen on
      // viittauksia, eik� sit� siksi voi poistaa.
      forward("kohde.php?kohdeid=$kohdeid", 
	      'Kohteen poisto ei onnistunut. Kohteeseen saattaa '.
	      'olla viittauksia muista kohteista.');
    }
  } else {
    // Oikeudet eiv�t ole kunnossa.  Palataan kohteen sivulle ja
    // ilmoitetaan esteest�.
    forward("kohde.php?kohdeid=$kohdeid", 
	    'Sinulla ei ole oikeutta poistaa kohdetta!');
  }

} else {
  // Poistoa ei ole varmistettu
  $pagename = "kohteen poisto";
  include_once('mappi_html-head.php');
  include_once('mappi_html-body-begin.php');
?>
<font color="red"><p><strong>
Poisto tietokannasta on peruuttamaton toiminto.  
Kerran poistettua ei voi palauttaa!
</strong></p></font>

<table border="0">
<tr>
<td>
   <form action="kohde_delete.php" method="post">
   <input type="hidden" name="kohdeid" value="<?php echo $kohdeid ?>">
   <input type="hidden" name="confirmed" value="yes">
   <input type="submit" value="Poista">
   </form>
</td>
<td>
   <form action="kohde.php" method="post">
   <input type="hidden" name="kohdeid" value="<?php echo $kohdeid ?>">
   <input type="submit" value="�l� poista">
   </form>
</td>
</tr>
</table>
<?php
  include_once('mappi_html-body-end.php');
}
?>
