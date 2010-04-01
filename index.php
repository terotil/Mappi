<?php
  ///////////////////////////////////////////////////////////////////////
  // Mappi - p��sivu
  ///////////////////////////////////////////////////////////////////////
  // Mappi on vapaaohjelmisto.  Sit� on lupa levitt�� edelleen ja
  // muokata "GNU General Public" -lisenssin (GPL) ehdoin.
  // Lisenssiehdot l�ytyv�t osoitteesta
  // http://www.gnu.org/copyleft/gpl.html
  ///////////////////////////////////////////////////////////////////////
require_once('mappi_headers.php');

if ( is_logged() ) {
  ///////////////////////////////////////////////////////////////////////
  // K�ytt�j� on onnistuneesti kirjautunut j�rjestelm��n
  ///////////////////////////////////////////////////////////////////////

  $pagename = 'P��sivu';
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');

  // Tervetuliaiset: j�rjestelm�kohtainen ja yleinen
  foreach ( array('welcome_local', 'welcome') as $welc ) {
    if ( ( (!isset($settings[$welc])) ||
	   (isset($settings[$welc]) && $settings[$welc]) ) &&
	 file_exists($welc.'.php') ) {
      include($welc.'.php');
    }
  }
} else {
  ///////////////////////////////////////////////////////////////////////
  // Ei kirjautunut, n�ytet��n login -sivu 
  ///////////////////////////////////////////////////////////////////////
  $pagename = 'Kirjautuminen';
  require_once('mappi_html-head.php');
  require_once('mappi_html-body-begin.php');
?>

	<form action="login.php" method="post">
		K�ytt�j�nimi<br>
	        <input type="hidden" name="goto" 
	           value="<?php echo safe_retrieve_gp('goto'); ?>">
		<input type="text" name="uname" 
		   value="<?php echo safe_retrieve_gp('username'); ?>"><br>
		Salasana<br>
		<input type="password" name="pw"><br>
		<input type="submit" value="Kirjaudu">
	</form>
<?php
  ///////////////////////////////////////////////////////////////////////
}
  require_once('mappi_html-body-end.php');
?>
