<?php
  $forwardpage = true;
  require_once('mappi_headers.php');

  // Haetaan parametrit
  $kohde_liiteid = safe_retrieve_gp('kohde_liiteid');
  $confirmed     = safe_retrieve_gp('confirmed', 'no', true);

if ( $confirmed == 'yes' ) {
  // Poisto on varmistettu, poistetaan

  // Jos kohde_liiteid on hukassa, ei voida tehdä yhtikäs mitään.
  // Pelastetaan umpikuja heittämällä oletusarvoilla kohdelistalle
  if ( $kohde_liiteid == '' ) {
    forward('kohteet.php', 
	    'Ei voi poistaa liitetietoa. Tarvittava'.
	    ' parametri <code>kohde_liiteid</code> puuttuu.');
    return;
  }

  // Otetaan talteen tieto, mistä ollaan tulossa
  $kohde_liite = qry_kohde_liite($kohde_liiteid);
  if ( is_allowed($kohde_liiteid, 'write', 'kohde_liiteid', $kohde_liite) ) {
    qry_kohde_liite_delete($kohde_liiteid);
    forward("kohde.php?kohdeid=".$kohde_liite['kohdeid']);
  } else {
    forward("kohde_liite_edit.php?kohde_liiteid=$kohde_liiteid", 
	    'Sinulla ei ole oikeutta poistaa tätä liitetietoa!');
  }

} else {
  // Poistoa ei ole varmistettu
  $pagename = "liitetiedon poisto";
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
   <form action="kohde_liite_delete.php" method="post">
   <input type="hidden" name="kohde_liiteid" value="<?php echo $kohde_liiteid ?>">
   <input type="hidden" name="confirmed" value="yes">
   <input type="submit" value="Poista">
   </form>
</td>
<td>
   <form action="kohde_liite_edit.php" method="post">
   <input type="hidden" name="kohde_liiteid" value="<?php echo $kohde_liiteid ?>">
   <input type="submit" value="Älä poista">
   </form>
</td>
</tr>
</table>
<?php
  include_once('mappi_html-body-end.php');
}
?>

