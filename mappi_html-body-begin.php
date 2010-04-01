<?php /*
	Ylläpitokäyttöliittymän sivun alkuosa.  Olettaa, että
	mappi_lib.php ja mappi_rc.php on ladattu, sessio on alustettu
	ja tietokantayhteys avattu (kunnossa, kun mappi_headers.php on
	ladattu).  */
require_once('MAPPI/html.php');

///////////////////////////////////////////////////////////////////////////////
// Valikkohanikan tulostus
///////////////////////////////////////////////////////////////////////////////
function print_button($txt, $url, $sep=false) {
  echo sprintf('   <li%s><a href="%s">%s</a></li>', $sep?' class="sep-after"':'', $url, $txt), "\n";
}

?><body>
<?php if ( $settings['underconstruction'] ) { ?>
 <div class="error" style="margin-bottom: 1em">
   Järjestelmässä tehdään parasta aikaa muutostöitä.  
   Toimivuutta ei taata.  Ongelmatapauksessa ota yhteyttä ylläpitoon: 
   <?php echo $settings['admin_email'] ?>, 
   <?php echo $settings['admin_phone'] ?>.</div><?php } ?>
 <div id="menu">
  <ul>
<?php 
if ( is_logged() ) {
  $r = $settings['root'];
  // tätä ei toivottavasti jatkossa tarvita
  //  print_button('takaisin',    back_url(), true);
  print_button('pääsivu',    $r, true);
  print_button('tietokanta', $r.'kohteet.php');
  print_button('raportit',   $r.'raportit.php');
  print_button('asetukset',  $r.'asetukset.php');
  print_button('ohjeet',     $r.'help.php', true);
  if ( $settings['supportlink'] ) {
    print_button('apua!', $settings['supportlink'], true);
  }
  print_button('lopeta',     $r.'logout.php');
} else {
  print_button('paluu',      $r.'../');
}?>  </ul>
 </div>
 <div id="titlebar">
  <div id="path"><?php 
echo html_make_a('.', $settings['name']).' &gt; '.$pagename; 
if ( $pn = safe_retrieve('pn') ) {
  echo ' &gt; '.base64_decode($pn);
}
?></div>
 </div>
 <div id="messages"><?php 
echo base64_decode(safe_retrieve('msg')); 
echo $message;
?></div>
 <div id="view">
